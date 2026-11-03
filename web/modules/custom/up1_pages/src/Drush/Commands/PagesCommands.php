<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Drush\Commands;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\up1_pages\Exception\SyncAbortedException;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Http\CircuitBreaker;
use Drupal\up1_pages\Http\WsClient;
use Drupal\up1_pages\Manager\HalPublications;
use Drupal\up1_pages\Manager\PagePersoManager;
use Drupal\up1_pages\Manager\PagePersoPurger;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Drupal\up1_pages\StatusReport;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commandes Drush des pages personnelles.
 */
final class PagesCommands extends DrushCommands {

  public function __construct(
    private readonly PagePersoSyncManager $syncManager,
    private readonly CircuitBreaker $breaker,
    private readonly HalPublications $halPublications,
    private readonly QueueFactory $queueFactory,
    private readonly MemoryCacheInterface $entityMemoryCache,
    private readonly LoggerInterface $moduleLogger,
    private readonly PagePersoPurger $purger,
  ) {
    parent::__construct();
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get(PagePersoSyncManager::class),
      $container->get(CircuitBreaker::class),
      $container->get(HalPublications::class),
      $container->get('queue'),
      $container->get('entity.memory_cache'),
      $container->get('logger.channel.up1_pages'),
      $container->get(PagePersoPurger::class),
    );
  }

  /**
   * Aligne les comptes et les pages personnelles sur wsgroups.
   */
  #[CLI\Command(name: 'up1-pages:sync', aliases: ['pp-sync'])]
  #[CLI\Option(name: 'dry-run', description: 'Affiche les écarts sans rien modifier.')]
  #[CLI\Option(name: 'force', description: 'Passe outre le garde-fou sur le nombre de désactivations (mise à jour massive).')]
  #[CLI\Option(name: 'no-confirm', description: 'Ne revérifie pas chaque désactivation auprès de wsgroups (2 appels par personne). Plus rapide : à réserver à une liste vérifiée avec --dry-run.')]
  #[CLI\Usage(name: 'drush up1-pages:sync --dry-run', description: 'Liste les personnes qui seraient traitées.')]
  #[CLI\Usage(name: 'drush up1-pages:sync --force', description: 'Mise à jour massive (ex. préprod en retard) : résumé, confirmation, puis traitement.')]
  #[CLI\Usage(name: 'drush up1-pages:sync --force --no-confirm -y', description: 'Idem, sans revérification individuelle ni question.')]
  public function sync(array $options = ['dry-run' => FALSE, 'force' => FALSE, 'no-confirm' => FALSE]): int {
    try {
      $plan = $this->syncManager->buildPlan((bool) $options['force']);
    }
    catch (WsUnavailableException | SyncAbortedException $e) {
      $this->logger()->error($e->getMessage());
      if ($e instanceof SyncAbortedException) {
        $this->logger()->notice('Si ces désactivations sont attendues, vérifiez-les avec --dry-run --force, puis relancez avec --force.');
      }
      return self::EXIT_FAILURE;
    }

    $actions = array_count_values(array_column($plan, 'action'));
    $activations = $actions[PagePersoSyncManager::ACTION_ACTIVATE] ?? 0;
    $deactivations = $actions[PagePersoSyncManager::ACTION_DEACTIVATE] ?? 0;
    $this->io()->writeln(sprintf(
      '%d personnes à traiter : %d à activer ou mettre à jour, %d à désactiver (%s).',
      count($plan), $activations, $deactivations, $this->syncManager->summarize($plan)
    ));

    if ($options['dry-run']) {
      $rows = [];
      foreach ($plan as $item) {
        $rows[] = [$item['action'], $item['login'], implode(', ', $item['reasons'])];
      }
      $this->io()->table(['Action', 'Login', 'Raisons'], $rows);
      return self::EXIT_SUCCESS;
    }
    if (!$plan) {
      return self::EXIT_SUCCESS;
    }

    $confirm = !$options['no-confirm'];
    if ($deactivations && $confirm) {
      $this->io()->note(sprintf('Chaque désactivation est revérifiée auprès de wsgroups (%d appels environ). --no-confirm évite ces appels.', 2 * $deactivations));
    }
    if (!$this->io()->confirm(sprintf('Appliquer %d activations et %d désactivations ?', $activations, $deactivations), FALSE)) {
      return self::EXIT_SUCCESS;
    }

    // Les éléments laissés en file par le cron viennent d'un plan plus ancien.
    $this->queueFactory->get(PagePersoSyncManager::QUEUE)->deleteQueue();

    $results = [];
    $errors = 0;
    $this->io()->progressStart(count($plan));
    foreach ($plan as $index => $item) {
      try {
        $result = $this->syncManager->apply($item, $confirm);
        $results[$result] = ($results[$result] ?? 0) + 1;
      }
      catch (WsUnavailableException $e) {
        $this->io()->progressFinish();
        $this->logger()->error(sprintf('wsgroups indisponible, synchro interrompue sans rien dépublier de plus : %s', $e->getMessage()));
        $this->report($results, $errors, count($plan) - $index);
        return self::EXIT_FAILURE;
      }
      catch (\Throwable $e) {
        $errors++;
        $this->logger()->error(sprintf('%s : %s', $item['login'], $e->getMessage()));
      }
      $this->io()->progressAdvance();
      // Des milliers de comptes et de pages chargés : on vide le cache
      // d'entités en mémoire régulièrement.
      if ($index % 50 === 49) {
        $this->entityMemoryCache->deleteAll();
      }
    }
    $this->io()->progressFinish();
    $this->report($results, $errors, 0);

    return $errors ? self::EXIT_FAILURE : self::EXIT_SUCCESS;
  }

  /**
   * Supprime les pages personnelles dépubliées depuis au moins N ans.
   *
   * La date de dépublication est notée par le module depuis son installation ;
   * pour les pages dépubliées avant, elle est retrouvée dans les révisions
   * (estimation qui n'est jamais antérieure à la vraie date).
   */
  #[CLI\Command(name: 'up1-pages:purge', aliases: ['pp-purge'])]
  #[CLI\Option(name: 'years', description: 'Ancienneté minimale de la dépublication, en années.')]
  #[CLI\Option(name: 'dry-run', description: 'Liste les pages qui seraient supprimées, sans rien supprimer.')]
  #[CLI\Option(name: 'limit', description: 'Nombre maximal de pages à supprimer (0 : toutes), les plus anciennes d\'abord.')]
  #[CLI\Usage(name: 'drush up1-pages:purge --dry-run', description: 'Pages dépubliées depuis au moins 2 ans.')]
  #[CLI\Usage(name: 'drush up1-pages:purge', description: 'Les supprime, après confirmation.')]
  public function purge(array $options = ['years' => 2, 'dry-run' => FALSE, 'limit' => 0]): int {
    $years = max(1, (int) $options['years']);
    $cutoff = strtotime("-$years years");
    $pages = $this->purger->findPurgeable($cutoff);
    if ((int) $options['limit'] > 0) {
      $pages = array_slice($pages, 0, (int) $options['limit']);
    }

    $this->io()->writeln(sprintf(
      '%d pages personnelles dépubliées avant le %s (%d an(s)), dont l\'auteur n\'a plus le rôle %s.',
      count($pages), date('d/m/Y', $cutoff), $years, PagePersoManager::ROLE
    ));
    if (!$pages) {
      return self::EXIT_SUCCESS;
    }

    if ($options['dry-run']) {
      $rows = [];
      foreach ($pages as $page) {
        $rows[] = [$page->nid, $page->login, $page->title, date('d/m/Y', $page->since), $page->source];
      }
      $this->io()->table(['nid', 'Login', 'Titre', 'Dépubliée depuis le', 'Date'], $rows);
      return self::EXIT_SUCCESS;
    }

    $this->io()->warning('La suppression est définitive (révisions et alias compris). Faites une sauvegarde de la base avant.');
    if (!$this->io()->confirm(sprintf('Supprimer %d pages personnelles ?', count($pages)), FALSE)) {
      return self::EXIT_SUCCESS;
    }

    $this->io()->progressStart(count($pages));
    $deleted = $this->purger->purge(
      array_map(static fn($page) => (int) $page->nid, $pages),
      fn(int $step) => $this->io()->progressAdvance($step),
    );
    $this->io()->progressFinish();
    $this->io()->writeln(sprintf('%d pages supprimées.', $deleted));

    return self::EXIT_SUCCESS;
  }

  /**
   * Bilan d'une synchro, à l'écran et dans le journal up1_pages.
   */
  private function report(array $results, int $errors, int $remaining): void {
    $message = sprintf(
      'Synchro par Drush : %d activées ou mises à jour, %d désactivées, %d désactivations annulées (toujours dans wsgroups), %d ignorées (plusieurs pages), %d erreurs, %d non traitées.',
      $results[PagePersoSyncManager::RESULT_ACTIVATED] ?? 0,
      $results[PagePersoSyncManager::RESULT_DEACTIVATED] ?? 0,
      $results[PagePersoSyncManager::RESULT_KEPT] ?? 0,
      $results[PagePersoSyncManager::RESULT_DUPLICATES] ?? 0,
      $errors,
      $remaining,
    );
    $this->io()->writeln($message);
    $this->moduleLogger->notice($message);
  }

  /**
   * Rafraîchit tout de suite les encarts « Publications HAL ».
   */
  #[CLI\Command(name: 'up1-pages:hal-refresh', aliases: ['pp-hal'])]
  #[CLI\Option(name: 'limit', description: 'Nombre maximal de pages à traiter (0 : toutes).')]
  #[CLI\Usage(name: 'drush up1-pages:hal-refresh', description: 'Premier remplissage après installation, ou mise à jour immédiate.')]
  public function halRefresh(array $options = ['limit' => 0]): int {
    if ($this->halPublications->enqueueIfDue(TRUE) === NULL) {
      $this->logger()->error('HAL indisponible ou désactivé : aucun encart modifié.');
      return self::EXIT_FAILURE;
    }

    $queue = $this->queueFactory->get(HalPublications::QUEUE);
    $limit = (int) $options['limit'];
    $counts = [];
    $done = 0;
    while ((!$limit || $done < $limit) && ($item = $queue->claimItem())) {
      try {
        $result = $this->halPublications->refresh((int) $item->data['nid']);
        $counts[$result] = ($counts[$result] ?? 0) + 1;
        $queue->deleteItem($item);
      }
      catch (WsUnavailableException $e) {
        $queue->releaseItem($item);
        $this->logger()->error('HAL ne répond plus, arrêt (encarts conservés) : ' . $e->getMessage());
        break;
      }
      catch (\Throwable $e) {
        $queue->deleteItem($item);
        $counts['error'] = ($counts['error'] ?? 0) + 1;
        $this->logger()->error(sprintf('Page %s : %s', $item->data['nid'], $e->getMessage()));
      }
      $done++;
    }

    $this->io()->writeln(sprintf(
      '%d pages traitées : %d mises à jour, %d inchangées, %d conservées (liste vide reçue), %d ignorées, %d erreurs. %d restent en file pour le cron.',
      $done,
      $counts[HalPublications::UPDATED] ?? 0,
      $counts[HalPublications::UNCHANGED] ?? 0,
      $counts[HalPublications::KEPT] ?? 0,
      $counts[HalPublications::SKIPPED] ?? 0,
      $counts['error'] ?? 0,
      $queue->numberOfItems(),
    ));

    return self::EXIT_SUCCESS;
  }

  /**
   * État des web services (disjoncteur).
   */
  #[CLI\Command(name: 'up1-pages:ws-status', aliases: ['pp-ws'])]
  public function wsStatus(): int {
    $rows = [];
    foreach ([WsClient::WSGROUPS, WsClient::HAL] as $service) {
      $status = $this->breaker->status($service);
      $rows[] = [
        $service,
        !$status['enabled'] ? 'désactivé' : ($status['open'] ? 'suspendu jusqu’à ' . date('H:i:s', $status['open_until']) : 'disponible'),
        $status['failures'],
        $status['reason'],
      ];
    }
    $this->io()->table(['Service', 'État', 'Échecs', 'Dernière erreur'], $rows);

    // Comptes enregistrés à la dernière synchro : aucun appel aux web services.
    $sections = StatusReport::sections(
      $this->syncManager->getStatistics(),
      $this->halPublications->statistics(),
      static fn(int $timestamp) => date('d/m/Y H:i', $timestamp),
    );
    foreach ($sections as $title => $section_rows) {
      $this->io()->section($title);
      $this->io()->table([], $section_rows);
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * Réactive immédiatement les appels aux web services.
   */
  #[CLI\Command(name: 'up1-pages:ws-reset', aliases: ['pp-ws-reset'])]
  public function wsReset(): int {
    foreach ([WsClient::WSGROUPS, WsClient::HAL] as $service) {
      $this->breaker->reset($service);
    }
    $this->logger()->success('Appels aux web services réactivés.');

    return self::EXIT_SUCCESS;
  }

}
