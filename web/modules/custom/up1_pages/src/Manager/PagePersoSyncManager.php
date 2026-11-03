<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\cas\Service\CasUserManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\up1_pages\Exception\SyncAbortedException;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Gateway\WsGroupsDirectoryGateway;
use Drupal\up1_pages\Http\CircuitBreaker;
use Drupal\up1_pages\Http\WsClient;
use Psr\Log\LoggerInterface;

/**
 * Aligne les comptes et les pages personnelles sur wsgroups.
 *
 * Le travail se fait en deux temps :
 * - buildPlan() compare wsgroups et Drupal et liste les personnes à traiter,
 *   sans rien écrire. Si wsgroups ne répond pas, ou répond quelque chose de
 *   suspect, rien n'est planifié ;
 * - apply() traite une personne, sans appeler wsgroups. Il revérifie l'état
 *   réel avant d'écrire : le rejouer ne crée ni compte ni page en double.
 */
final class PagePersoSyncManager {

  public const QUEUE = 'up1_pages_sync';

  public const ACTION_ACTIVATE = 'activate';

  public const ACTION_DEACTIVATE = 'deactivate';

  /**
   * Résultats de apply().
   */
  public const RESULT_ACTIVATED = 'activated';

  public const RESULT_DEACTIVATED = 'deactivated';

  public const RESULT_KEPT = 'kept';

  public const RESULT_DUPLICATES = 'duplicates';

  private const STATE_LAST_PLAN = 'up1_pages.sync.last_plan';

  private const STATE_STATISTICS = 'up1_pages.sync.statistics';

  private const STATE_LAST_RESULT = 'up1_pages.sync.last_result';

  /**
   * Garde-fou : au-delà de ce nombre de désactivations ET de cette part des
   * comptes ayant le rôle, on considère que la réponse de wsgroups est
   * suspecte et on ne fait rien (sauf si la synchro est forcée).
   */
  private const DEACTIVATION_MIN = 20;

  private const DEACTIVATION_MAX_RATIO = 0.1;

  public function __construct(
    private readonly WsGroupsDirectoryGateway $wsGroupsDirectory,
    private readonly CircuitBreaker $breaker,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database,
    private readonly QueueFactory $queueFactory,
    private readonly CasUserManager $casUserManager,
    private readonly StateInterface $state,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Liste les personnes dont le compte ou la page est à mettre à jour.
   *
   * @param bool $force
   *   TRUE pour passer outre le garde-fou sur le nombre de désactivations.
   *
   * @return array[]
   *   Un élément par personne : action, login, title, mail, reasons.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   * @throws \Drupal\up1_pages\Exception\SyncAbortedException
   */
  public function buildPlan(bool $force = FALSE): array {
    try {
      $plan = $this->compare($force);
    }
    catch (WsUnavailableException | SyncAbortedException $e) {
      $this->recordResult('aborted', $e->getMessage());
      throw $e;
    }
    $this->recordResult('ok', count($plan) . ' personnes à traiter (' . $this->summarize($plan) . ').');

    return $plan;
  }

  /**
   * Comparaison de wsgroups et de Drupal (voir buildPlan()).
   */
  private function compare(bool $force): array {
    $people = $this->wsGroupsDirectory->getAllPagePersoUsers();

    // Lecture directe des tables : charger des milliers d'entités user et
    // node pour une simple comparaison serait bien trop lourd.
    $accounts = [];
    $names = [];
    $rows = $this->database->select('users_field_data', 'u')
      ->fields('u', ['uid', 'name'])
      ->condition('u.uid', 0, '>')
      ->condition('u.default_langcode', 1)
      ->execute();
    foreach ($rows as $row) {
      // Quelques comptes hérités n'ont pas de nom : on les ignore.
      if ((string) $row->name === '') {
        continue;
      }
      $accounts[mb_strtolower($row->name)] = (int) $row->uid;
      $names[(int) $row->uid] = $row->name;
    }

    $role_holders = $this->database->select('user__roles', 'r')
      ->fields('r', ['entity_id'])
      ->condition('r.roles_target_id', PagePersoManager::ROLE)
      ->execute()
      ->fetchCol();

    // Une ligne par traduction : une page n'est considérée comme publiée que
    // si toutes ses traductions le sont. Titre et auteur : version d'origine.
    $by_nid = [];
    $rows = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'uid', 'status', 'title', 'default_langcode'])
      ->condition('n.type', PagePersoManager::BUNDLE)
      ->execute();
    foreach ($rows as $row) {
      $page = $by_nid[(int) $row->nid] ??= (object) ['nid' => (int) $row->nid, 'uid' => NULL, 'status' => 1, 'title' => ''];
      $page->status = min($page->status, (int) $row->status);
      if ($row->default_langcode) {
        $page->uid = (int) $row->uid;
        $page->title = (string) $row->title;
      }
    }
    $pages = [];
    foreach ($by_nid as $page) {
      if ($page->uid !== NULL) {
        $pages[$page->uid][] = $page;
      }
    }

    $plan = [];
    $has_role = array_flip($role_holders);

    foreach ($people as $login => $person) {
      $title = $this->buildTitle($person);
      $reasons = [];
      $uid = $accounts[$login] ?? NULL;

      if ($uid === NULL) {
        $reasons[] = 'account';
      }
      else {
        if (!isset($has_role[$uid])) {
          $reasons[] = 'role';
        }
        $user_pages = $pages[$uid] ?? [];
        if (count($user_pages) === 0) {
          $reasons[] = 'page';
        }
        elseif (count($user_pages) > 1) {
          // Héritage d'anciennes erreurs : on signale, on ne touche à rien.
          $this->logger->warning('@login a @nb pages personnelles (nid @nids).', [
            '@login' => $login,
            '@nb' => count($user_pages),
            '@nids' => implode(', ', array_column($user_pages, 'nid')),
          ]);
        }
        else {
          $page = reset($user_pages);
          if (!$page->status) {
            $reasons[] = 'publish';
          }
          if ($title !== '' && trim($page->title) !== $title) {
            $reasons[] = 'title';
          }
        }
      }

      if ($reasons) {
        $plan[] = [
          'action' => self::ACTION_ACTIVATE,
          'login' => $login,
          'title' => $title,
          'mail' => is_string($person['mail'] ?? NULL) ? $person['mail'] : '',
          'reasons' => $reasons,
        ];
      }
    }

    $deactivations = [];
    foreach ($role_holders as $uid) {
      $name = $names[(int) $uid] ?? NULL;
      if ($name !== NULL && !isset($people[mb_strtolower($name)])) {
        $deactivations[] = [
          'action' => self::ACTION_DEACTIVATE,
          'login' => $name,
          'reasons' => ['gone'],
        ];
      }
    }

    $nb = count($deactivations);
    if (!$force && $nb > self::DEACTIVATION_MIN && $nb > count($role_holders) * self::DEACTIVATION_MAX_RATIO) {
      throw new SyncAbortedException(sprintf(
        '%d désactivations demandées pour %d comptes ayant le rôle %s : réponse wsgroups suspecte, rien n\'a été modifié.',
        $nb, count($role_holders), PagePersoManager::ROLE
      ));
    }

    $plan = array_merge($plan, $deactivations);
    $this->state->set(self::STATE_STATISTICS, $this->statistics($people, $names, $role_holders, $pages, $plan));

    return $plan;
  }

  /**
   * Comptes de la dernière comparaison réussie et résultat de la dernière
   * synchro, pour l'administration. Ne fait aucun appel à wsgroups.
   *
   * @return array
   *   « statistics » (NULL si aucune comparaison n'a encore réussi) et
   *   « last_result » (date, status ok/aborted, message ; NULL si jamais lancée).
   */
  public function getStatistics(): array {
    return [
      'statistics' => $this->state->get(self::STATE_STATISTICS),
      'last_result' => $this->state->get(self::STATE_LAST_RESULT),
    ];
  }

  /**
   * Comptes wsgroups, Drupal et écarts d'une comparaison.
   */
  private function statistics(array $people, array $names, array $role_holders, array $pages, array $plan): array {
    $affiliations = array_map(static fn(array $person) => $person['affiliations'] ?? [], $people);
    $is = static fn(array $list, string $affiliation) => in_array($affiliation, $list, TRUE);

    $drupal = [
      'role' => count($role_holders),
      'published' => 0,
      'published_faculty' => 0,
      'published_student' => 0,
      'published_both' => 0,
      'published_absent' => 0,
      'unpublished' => 0,
      'duplicates' => 0,
    ];
    foreach ($pages as $uid => $user_pages) {
      if (count($user_pages) > 1) {
        $drupal['duplicates']++;
      }
      $person = $affiliations[mb_strtolower((string) ($names[$uid] ?? ''))] ?? NULL;
      foreach ($user_pages as $page) {
        if (!$page->status) {
          $drupal['unpublished']++;
          continue;
        }
        $drupal['published']++;
        $key = match (TRUE) {
          $person === NULL => 'published_absent',
          $is($person, 'faculty') && $is($person, 'student') => 'published_both',
          $is($person, 'faculty') => 'published_faculty',
          default => 'published_student',
        };
        $drupal[$key]++;
      }
    }

    $reasons = array_count_values(array_merge([], ...array_column($plan, 'reasons')));
    ksort($reasons);

    return [
      'date' => $this->time->getCurrentTime(),
      'wsgroups' => [
        'faculty' => count(array_filter($affiliations, static fn($list) => $is($list, 'faculty'))),
        'student' => count(array_filter($affiliations, static fn($list) => $is($list, 'student'))),
        'both' => count(array_filter($affiliations, static fn($list) => $is($list, 'faculty') && $is($list, 'student'))),
        'total' => count($people),
      ],
      'drupal' => $drupal,
      'plan' => $reasons,
    ];
  }

  private function recordResult(string $status, string $message): void {
    $this->state->set(self::STATE_LAST_RESULT, [
      'date' => $this->time->getCurrentTime(),
      'status' => $status,
      'message' => $message,
    ]);
  }

  /**
   * Synchro par le cron, au plus une fois par sync.interval.
   */
  public function enqueueIfDue(): ?int {
    return $this->isDue('sync.interval') ? $this->enqueue() : NULL;
  }

  /**
   * Synchro demandée par une URL, au plus une fois par sync.min_interval.
   */
  public function enqueueThrottled(): ?int {
    if (!$this->isDue('sync.min_interval')) {
      $this->logger->notice('Synchro des pages personnelles demandée trop tôt après la précédente : ignorée.');
      return NULL;
    }

    return $this->enqueue();
  }

  /**
   * Met la synchro en file d'attente.
   *
   * @return int|null
   *   Le nombre de personnes mises en file, NULL si rien n'a été fait.
   */
  public function enqueue(): ?int {
    if (!$this->breaker->isAvailable(WsClient::WSGROUPS)) {
      $this->logger->warning('Synchro des pages personnelles reportée : wsgroups indisponible ou désactivé.');
      $this->recordResult('aborted', 'wsgroups indisponible ou désactivé : synchro reportée.');
      return NULL;
    }

    $queue = $this->queueFactory->get(self::QUEUE);
    if ($queue->numberOfItems() > 0) {
      $this->logger->notice('Synchro des pages personnelles : la file contient encore @nb éléments, nouvelle comparaison reportée.', [
        '@nb' => $queue->numberOfItems(),
      ]);
      return NULL;
    }

    // Noté avant l'appel : une panne ne doit pas faire retenter la synchro à
    // chaque passage du cron.
    $this->state->set(self::STATE_LAST_PLAN, $this->time->getCurrentTime());

    try {
      $plan = $this->buildPlan();
    }
    catch (WsUnavailableException | SyncAbortedException $e) {
      $this->logger->error('Synchro des pages personnelles annulée : @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    foreach ($plan as $item) {
      $queue->createItem($item);
    }
    // « publish: N » dans le détail = nombre de pages à republier.
    $this->logger->notice('Synchro des pages personnelles : @nb personnes à traiter (@detail).', [
      '@nb' => count($plan),
      '@detail' => $this->summarize($plan),
    ]);

    return count($plan);
  }

  /**
   * Traite une personne du plan.
   *
   * Une désactivation n'est appliquée que si wsgroups confirme, au moment
   * même, que la personne n'a plus droit à une page (sauf $confirm à FALSE).
   *
   * @return string
   *   self::RESULT_ACTIVATED, RESULT_DEACTIVATED, RESULT_KEPT (désactivation
   *   annulée, personne toujours dans wsgroups) ou RESULT_DUPLICATES
   *   (plusieurs pages, rien n'est fait).
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si wsgroups ne répond pas pour confirmer une désactivation : rien n'est
   *   modifié.
   */
  public function apply(array $item, bool $confirm = TRUE): string {
    if ($item['action'] === self::ACTION_DEACTIVATE) {
      // $confirm à FALSE : réservé à drush up1-pages:sync --no-confirm, quand
      // une personne a vérifié la liste des désactivations.
      if ($confirm && $this->wsGroupsDirectory->isPagePersoUser($item['login'])) {
        $this->logger->notice('@login toujours présent dans wsgroups : désactivation annulée.', ['@login' => $item['login']]);
        return self::RESULT_KEPT;
      }
      $this->deactivate($item['login']);
      return self::RESULT_DEACTIVATED;
    }

    return $this->activate($item['login'], $item['title'], $item['mail']) === NULL
      ? self::RESULT_DUPLICATES
      : self::RESULT_ACTIVATED;
  }

  /**
   * Résume un plan : « account: 3, title: 12, gone: 5 ».
   */
  public function summarize(array $plan): string {
    $counts = array_count_values(array_merge([], ...array_column($plan, 'reasons')));
    ksort($counts);
    $parts = [];
    foreach ($counts as $reason => $count) {
      $parts[] = "$reason: $count";
    }

    return $parts ? implode(', ', $parts) : 'rien à faire';
  }

  /**
   * Compte, rôle, page créée, publiée et titrée pour une personne présente
   * dans wsgroups.
   *
   * @return int|null
   *   Le nid de la page personnelle, NULL si la personne en a plusieurs
   *   (cas hérité qu'on ne tranche pas automatiquement).
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Si le compte n'existe pas et ne peut pas être créé.
   */
  public function activate(string $login, string $title, string $mail): ?int {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $login]);
    /** @var \Drupal\user\UserInterface|false $account */
    $account = reset($accounts);

    if (!$account) {
      $properties = ['roles' => [PagePersoManager::ROLE]];
      if ($mail !== '') {
        $properties['mail'] = $mail;
      }
      // Lève une CasLoginException si le compte ne peut pas être créé.
      $account = $this->casUserManager->register($login, $login, $properties);
    }
    elseif (!$account->hasRole(PagePersoManager::ROLE)) {
      $account->addRole(PagePersoManager::ROLE);
      $account->save();
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', PagePersoManager::BUNDLE)
      ->condition('uid', $account->id())
      ->execute();

    if (count($nids) === 0) {
      $page = $node_storage->create([
        'type' => PagePersoManager::BUNDLE,
        'title' => $title !== '' ? $title : $login,
        'langcode' => 'fr',
        'uid' => $account->id(),
        'status' => 1,
        PagePersoManager::FIELD_UID_LDAP => $login,
        'site_id' => NULL,
      ]);
      $page->save();
      return (int) $page->id();
    }
    if (count($nids) > 1) {
      return NULL;
    }

    /** @var \Drupal\node\NodeInterface $page */
    $page = $node_storage->load(reset($nids));
    $changed = FALSE;
    // Toutes les traductions, symétrie de deactivate().
    foreach (array_keys($page->getTranslationLanguages()) as $langcode) {
      $translation = $page->getTranslation($langcode);
      if (!$translation->isPublished()) {
        $translation->setPublished();
        $changed = TRUE;
        $this->logger->notice('Page personnelle de @login republiée (nid @nid, @langcode).', [
          '@login' => $login,
          '@nid' => $page->id(),
          '@langcode' => $langcode,
        ]);
      }
    }
    if ($title !== '' && trim((string) $page->getTitle()) !== $title) {
      $page->setTitle($title);
      $changed = TRUE;
    }
    if ($changed) {
      $page->save();
    }

    return (int) $page->id();
  }

  /**
   * Retire le rôle et dépublie les pages d'une personne sortie de wsgroups.
   */
  private function deactivate(string $login): void {
    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $login]);
    /** @var \Drupal\user\UserInterface|false $account */
    $account = reset($accounts);
    if (!$account) {
      return;
    }

    if ($account->hasRole(PagePersoManager::ROLE)) {
      $account->removeRole(PagePersoManager::ROLE);
      $account->save();
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', PagePersoManager::BUNDLE)
      ->condition('uid', $account->id())
      ->execute();

    /** @var \Drupal\node\NodeInterface $page */
    foreach ($node_storage->loadMultiple($nids) as $page) {
      // Toutes les traductions : une version anglaise ne doit pas rester
      // en ligne.
      foreach (array_keys($page->getTranslationLanguages()) as $langcode) {
        $translation = $page->getTranslation($langcode);
        if ($translation->isPublished()) {
          $translation->setUnpublished();
          $translation->save();
        }
      }
    }
  }

  /**
   * « Mme Prénom Nom », comme le titre historique des pages.
   */
  public function buildTitle(array $person): string {
    $civility = $person['supannCivilite'] ?? '';
    $name = $person['displayName'] ?? '';
    // wsgroups renvoie parfois un attribut sous forme de tableau.
    $civility = is_array($civility) ? (string) reset($civility) : (string) $civility;
    $name = is_array($name) ? (string) reset($name) : (string) $name;

    return trim($name) === '' ? '' : trim($civility . ' ' . $name);
  }

  private function isDue(string $interval_key): bool {
    $interval = (int) $this->configFactory->get('up1_pages.settings')->get($interval_key);

    return $this->time->getCurrentTime() - (int) $this->state->get(self::STATE_LAST_PLAN, 0) >= $interval;
  }

}
