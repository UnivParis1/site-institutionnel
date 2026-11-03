<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Encart « Publications HAL » des pages personnelles.
 *
 * L'affichage ne fait que lire l'encart enregistré, sans date d'expiration :
 * il n'appelle jamais HAL. Le cron le rafraîchit, une page à la fois, et ne
 * le remplace que si HAL a répondu une liste valide et différente.
 */
final class HalPublications {

  public const QUEUE = 'up1_pages_hal_refresh';

  public const UPDATED = 'updated';

  public const UNCHANGED = 'unchanged';

  public const KEPT = 'kept';

  public const SKIPPED = 'skipped';

  public const METHODS = ['idhal', 'nomprenom'];

  /**
   * Encarts enregistrés, par nid : html, hash, updated, checked.
   */
  private const STORE = 'up1_pages.hal_publications';

  /**
   * Pages déjà en file, pour ne pas les y mettre deux fois.
   */
  private const PENDING = 'up1_pages.hal_pending';

  private const STATE_LAST_ENQUEUE = 'up1_pages.hal.last_enqueue';

  public function __construct(
    private readonly HalManager $hal,
    private readonly ComptexManager $comptex,
    private readonly KeyValueFactoryInterface $keyValue,
    private readonly KeyValueExpirableFactoryInterface $keyValueExpirable,
    private readonly QueueFactory $queueFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    private readonly StateInterface $state,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Étiquette de cache de l'encart d'une page, invalidée à chaque mise à jour.
   */
  public static function cacheTag(int|string $nid): string {
    return 'up1_pages_hal:' . $nid;
  }

  /**
   * Encart à afficher ; NULL si aucune publication n'est connue.
   *
   * Si la page n'a jamais été rafraîchie, elle est mise en file : l'encart
   * apparaîtra au prochain passage du cron.
   */
  public function get(NodeInterface $node): ?string {
    if (!in_array($this->method($node), self::METHODS, TRUE)) {
      return NULL;
    }
    $entry = $this->store()->get($node->id());
    if (!is_array($entry)) {
      $this->enqueue((int) $node->id());
      return NULL;
    }

    return $entry['html'] !== '' ? $entry['html'] : NULL;
  }

  /**
   * Met une page en file de rafraîchissement, une seule fois.
   */
  public function enqueue(int $nid): void {
    if ($this->pending()->get($nid)) {
      return;
    }
    $this->pending()->setWithExpire($nid, TRUE, 86400);
    $this->queueFactory->get(self::QUEUE)->createItem(['nid' => $nid]);
  }

  /**
   * Toutes les pages concernées en file, au plus une fois par hal.refresh_interval.
   *
   * @return int|null
   *   Le nombre de pages mises en file, NULL si rien n'a été fait.
   */
  public function enqueueIfDue(bool $force = FALSE): ?int {
    $interval = (int) $this->configFactory->get('up1_pages.settings')->get('hal.refresh_interval');
    if (!$force && $this->time->getCurrentTime() - (int) $this->state->get(self::STATE_LAST_ENQUEUE, 0) < $interval) {
      return NULL;
    }
    if (!$this->hal->isAvailable()) {
      $this->logger->warning('Publications HAL : rafraîchissement reporté, HAL indisponible ou désactivé. Les encarts actuels sont conservés.');
      return NULL;
    }
    $this->state->set(self::STATE_LAST_ENQUEUE, $this->time->getCurrentTime());

    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', PagePersoManager::BUNDLE)
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_my_hal_publications', self::METHODS, 'IN')
      ->execute();
    foreach ($nids as $nid) {
      $this->enqueue((int) $nid);
    }
    $this->logger->notice('Publications HAL : @nb pages mises en file de rafraîchissement.', ['@nb' => count($nids)]);

    return count($nids);
  }

  /**
   * Rafraîchit l'encart d'une page.
   *
   * @return string
   *   self::UPDATED, UNCHANGED, KEPT (liste vide reçue, ancienne conservée)
   *   ou SKIPPED (page sans recherche HAL, ou recherche incomplète).
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si HAL ne répond pas : l'encart enregistré n'est pas modifié.
   */
  public function refresh(int $nid): string {
    $this->pending()->delete($nid);

    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    $method = $node ? $this->method($node) : NULL;
    if (!in_array($method, self::METHODS, TRUE)) {
      $this->forget($nid);
      return self::SKIPPED;
    }

    // Paramètres de la recherche : [prénom, nom, idHAL].
    [$firstname, $lastname, $id_hal] = match ($method) {
      // L'identifiant HAL saisi sur la page suffit : pas besoin de wsgroups.
      'idhal' => ['', '', $node->hasField('field_id_hal') ? (string) $node->get('field_id_hal')->value : ''],
      // Nom et prénom de la fiche wsgroups (en cache ; dernière fiche connue
      // si wsgroups est en panne).
      'nomprenom' => $this->namesOf($node),
    };

    try {
      // Recherche incomplète (idHAL ou nom manquant) : rien n'est modifié.
      $html = $this->hal->fetchPublications($method, $firstname, $lastname, $id_hal);
    }
    catch (\InvalidArgumentException) {
      return self::SKIPPED;
    }

    $now = $this->time->getCurrentTime();
    $entry = $this->store()->get($nid);
    $hash = hash('sha256', $html);

    if (is_array($entry) && $entry['hash'] === $hash) {
      $this->store()->set($nid, ['checked' => $now] + $entry);
      return self::UNCHANGED;
    }
    // Une liste vide après une liste non vide ressemble plus à un incident
    // HAL qu'à des publications supprimées : on garde l'encart.
    if ($html === '' && is_array($entry) && $entry['html'] !== '') {
      $this->logger->warning('Publications HAL de la page @nid : liste vide reçue, encart précédent conservé.', ['@nid' => $nid]);
      $this->store()->set($nid, ['checked' => $now] + $entry);
      return self::KEPT;
    }

    $this->store()->set($nid, ['html' => $html, 'hash' => $hash, 'updated' => $now, 'checked' => $now]);
    $this->cacheTagsInvalidator->invalidateTags([self::cacheTag($nid)]);

    return self::UPDATED;
  }

  /**
   * Oublie l'encart d'une page (supprimée, ou recherche HAL modifiée).
   */
  public function forget(int $nid): void {
    if ($this->store()->has($nid)) {
      $this->store()->delete($nid);
      $this->cacheTagsInvalidator->invalidateTags([self::cacheTag($nid)]);
    }
  }

  /**
   * Comptes des encarts, pour l'administration. Ne fait aucun appel à HAL.
   *
   * @return array
   *   with_search (pages avec une recherche HAL), stored, empty (encarts sans
   *   publication), missing (pages jamais traitées), queued, last_enqueue.
   */
  public function statistics(): array {
    $with_search = (int) $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', PagePersoManager::BUNDLE)
      ->condition('field_my_hal_publications', self::METHODS, 'IN')
      ->count()
      ->execute();
    $entries = $this->store()->getAll();
    $empty = count(array_filter($entries, static fn($entry) => is_array($entry) && $entry['html'] === ''));

    return [
      'with_search' => $with_search,
      'stored' => count($entries),
      'empty' => $empty,
      'missing' => max(0, $with_search - count($entries)),
      'queued' => $this->queueFactory->get(self::QUEUE)->numberOfItems(),
      'last_enqueue' => (int) $this->state->get(self::STATE_LAST_ENQUEUE, 0),
    ];
  }

  /**
   * Méthode de recherche HAL choisie sur la page : idhal, nomprenom ou nul.
   */
  public function method(NodeInterface $node): ?string {
    return $node->hasField('field_my_hal_publications') ? $node->get('field_my_hal_publications')->value : NULL;
  }

  /**
   * [prénom, nom, NULL] de l'auteur de la page, d'après wsgroups.
   */
  private function namesOf(NodeInterface $node): array {
    $person = $this->comptex->getUserInformation((string) $node->getOwner()?->getAccountName());

    return [(string) ($person['givenName'] ?? ''), (string) ($person['sn'] ?? ''), NULL];
  }

  private function store(): KeyValueStoreInterface {
    return $this->keyValue->get(self::STORE);
  }

  private function pending(): KeyValueStoreExpirableInterface {
    return $this->keyValueExpirable->get(self::PENDING);
  }

}
