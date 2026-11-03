<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Pages personnelles côté Drupal (aucun appel à un web service).
 */
final class PagePersoManager {

  public const BUNDLE = 'page_personnelle';

  public const ROLE = 'enseignant_doctorant';

  public const FIELD_UID_LDAP = 'field_uid_ldap';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  public function loadAccount(string $username): ?UserInterface {
    if ($username === '') {
      return NULL;
    }
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);

    return $accounts ? reset($accounts) : NULL;
  }

  /**
   * Pages personnelles dont la personne est l'auteur.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function loadPages(string $username): array {
    $account = $this->loadAccount($username);
    if (!$account) {
      return [];
    }

    return $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => self::BUNDLE,
      'uid' => $account->id(),
    ]);
  }

  /**
   * Page personnelle de chaque personne, en deux requêtes.
   *
   * @param string[] $usernames
   *
   * @return \Drupal\node\NodeInterface[]
   *   Indexées par login en minuscules ; absentes pour qui n'a pas de page.
   */
  public function loadPagesByUsernames(array $usernames): array {
    $usernames = array_values(array_filter(array_map('strval', $usernames)));
    if (!$usernames) {
      return [];
    }

    $logins = [];
    foreach ($this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $usernames]) as $account) {
      $logins[(int) $account->id()] = mb_strtolower((string) $account->getAccountName());
    }
    if (!$logins) {
      return [];
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', self::BUNDLE)
      ->condition('uid', array_keys($logins), 'IN')
      ->sort('nid')
      ->execute();

    $pages = [];
    /** @var \Drupal\node\NodeInterface $page */
    foreach ($node_storage->loadMultiple($nids) as $page) {
      // Première page seulement, comme l'ancien affichage.
      $pages[$logins[(int) $page->getOwnerId()]] ??= $page;
    }

    return $pages;
  }

  /**
   * Pages publiées dont le titre contient la saisie.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function autocomplete(string $input): array {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', self::BUNDLE)
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('title', $input, 'CONTAINS')
      ->sort('field_name')
      ->range(0, 16)
      ->execute();

    return $nids ? $node_storage->loadMultiple($nids) : [];
  }

  /**
   * La personne a-t-elle le rôle des enseignants-chercheurs et doctorants ?
   */
  public function hasRole(string $username): bool {
    $account = is_numeric($username)
      ? $this->entityTypeManager->getStorage('user')->load($username)
      : $this->loadAccount($username);

    return $account instanceof UserInterface && $account->hasRole(self::ROLE);
  }

}
