<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Date de dépublication et suppression des pages personnelles abandonnées.
 *
 * Drupal ne garde pas la date de dépublication d'un contenu : ce service la
 * note à chaque dépublication (hooks du module). Pour les pages dépubliées
 * avant, elle est retrouvée dans les révisions (voir estimate()).
 *
 * Une page est purgeable si aucune de ses traductions n'est publiée, si elle
 * est dépubliée depuis la date limite, et si son auteur n'a plus le rôle
 * enseignant_doctorant (sinon la synchro la republierait). N'appelle aucun
 * web service.
 */
final class PagePersoPurger {

  public const SOURCE_TRACKED = 'suivie';

  public const SOURCE_ESTIMATED = 'estimée';

  /**
   * Date de dépublication, par nid.
   */
  private const STORE = 'up1_pages.unpublished_since';

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly MemoryCacheInterface $entityMemoryCache,
    private readonly KeyValueFactoryInterface $keyValue,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Note la date de dépublication d'une page (insertion ou mise à jour).
   *
   * @param \Drupal\node\NodeInterface|null $original
   *   La page avant enregistrement ; NULL pour une création.
   */
  public function trackStatus(NodeInterface $node, ?NodeInterface $original): void {
    if ($node->bundle() !== PagePersoManager::BUNDLE) {
      return;
    }
    if (self::isPublished($node)) {
      $this->store()->delete($node->id());
    }
    elseif ($original === NULL || self::isPublished($original)) {
      $this->store()->set($node->id(), $this->time->getCurrentTime());
    }
  }

  public function forget(int $nid): void {
    $this->store()->delete($nid);
  }

  /**
   * Pages purgeables, dépubliées depuis le plus longtemps d'abord.
   *
   * @param int $cutoff
   *   Timestamp : pages dépubliées avant cette date.
   *
   * @return object[]
   *   nid, title, login, since (date de dépublication), source (suivie ou
   *   estimée).
   */
  public function findPurgeable(int $cutoff): array {
    $role_holders = $this->database->select('user__roles', 'r')
      ->fields('r', ['entity_id'])
      ->condition('r.roles_target_id', PagePersoManager::ROLE);

    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('users_field_data', 'u', 'u.uid = n.uid AND u.default_langcode = 1');
    $query->addField('n', 'nid');
    $query->addExpression('MAX(n.changed)', 'changed');
    $query->addExpression("MAX(CASE WHEN n.default_langcode = 1 THEN n.title ELSE '' END)", 'title');
    $query->addExpression("MAX(CASE WHEN n.default_langcode = 1 THEN u.name ELSE '' END)", 'login');
    $query->condition('n.type', PagePersoManager::BUNDLE)
      ->condition('n.uid', $role_holders, 'NOT IN')
      ->groupBy('n.nid')
      ->having('MAX(n.status) = 0');
    $pages = $query->execute()->fetchAllAssoc('nid');
    if (!$pages) {
      return [];
    }

    $tracked = $this->store()->getMultiple(array_keys($pages));
    $estimated = $this->estimate(array_diff(array_keys($pages), array_keys($tracked)));

    $purgeable = [];
    foreach ($pages as $nid => $page) {
      if (isset($tracked[$nid])) {
        $page->since = (int) $tracked[$nid];
        $page->source = self::SOURCE_TRACKED;
      }
      else {
        // Sans révision exploitable : dernière modification, qui n'est jamais
        // antérieure à la dépublication.
        $page->since = $estimated[$nid] ?? (int) $page->changed;
        $page->source = self::SOURCE_ESTIMATED;
      }
      unset($page->changed);
      if ($page->since < $cutoff) {
        $purgeable[] = $page;
      }
    }
    usort($purgeable, static fn($a, $b) => [$a->since, $a->nid] <=> [$b->since, $b->nid]);

    return $purgeable;
  }

  /**
   * Supprime des pages, par lots.
   *
   * @param int[] $nids
   * @param callable|null $progress
   *   Appelé avec le nombre de pages traitées dans chaque lot.
   *
   * @return int
   *   Le nombre de pages supprimées.
   */
  public function purge(array $nids, ?callable $progress = NULL): int {
    $storage = $this->entityTypeManager->getStorage('node');
    $deleted = 0;
    foreach (array_chunk($nids, 50) as $chunk) {
      $pages = array_filter(
        $storage->loadMultiple($chunk),
        // Revérifié au moment de supprimer : la page a pu être republiée.
        static fn($page) => $page->bundle() === PagePersoManager::BUNDLE && !self::isPublished($page),
      );
      $storage->delete($pages);
      $deleted += count($pages);
      $this->entityMemoryCache->deleteAll();
      if ($progress) {
        $progress(count($chunk));
      }
    }
    $this->logger->notice('Purge des pages personnelles : @nb pages dépubliées depuis longtemps supprimées.', ['@nb' => $deleted]);

    return $deleted;
  }

  /**
   * Date de dépublication retrouvée dans les révisions.
   *
   * Date de modification de la première révision non publiée qui suit la
   * dernière révision publiée (version d'origine). Elle n'est jamais
   * antérieure à la vraie dépublication, même quand une page a été dépubliée
   * sans nouvelle révision : on ne purge pas trop tôt.
   *
   * @param int[] $nids
   *
   * @return int[]
   *   Par nid ; absent si les révisions ne permettent pas de conclure.
   */
  private function estimate(array $nids): array {
    $since = [];
    foreach (array_chunk(array_values($nids), 500) as $chunk) {
      $rows = $this->database->select('node_field_revision', 'r')
        ->fields('r', ['nid', 'status', 'changed'])
        ->condition('r.nid', $chunk, 'IN')
        ->condition('r.default_langcode', 1)
        ->orderBy('r.nid')
        ->orderBy('r.vid')
        ->execute();
      $current = [];
      foreach ($rows as $row) {
        $nid = (int) $row->nid;
        if ((int) $row->status === 1) {
          $current[$nid] = NULL;
        }
        elseif (!isset($current[$nid]) && $row->changed) {
          $current[$nid] = (int) $row->changed;
        }
      }
      $since += array_filter($current, static fn($date) => $date !== NULL);
    }

    return $since;
  }

  private function store(): KeyValueStoreInterface {
    return $this->keyValue->get(self::STORE);
  }

  private static function isPublished($page): bool {
    foreach (array_keys($page->getTranslationLanguages()) as $langcode) {
      if ($page->getTranslation($langcode)->isPublished()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
