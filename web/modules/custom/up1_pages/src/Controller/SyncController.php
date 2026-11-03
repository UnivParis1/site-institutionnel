<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Anciennes adresses de synchro, encore appelées par la crontab du serveur.
 *
 * Elles ne font plus le travail elles-mêmes : elles demandent la même
 * comparaison que le cron (avec ses garde-fous et un intervalle minimal entre
 * deux demandes), dont le résultat est traité par la file up1_pages_sync. À
 * supprimer quand la crontab appellera « drush up1-pages:sync ».
 */
final class SyncController extends ControllerBase {

  public function __construct(
    private readonly PagePersoSyncManager $syncManager,
  ) {
  }

  public function legacyTrigger(): JsonResponse {
    $queued = $this->syncManager->enqueueThrottled();

    return new JsonResponse([
      'data' => [
        'queued' => $queued,
        'message' => $queued === NULL
          ? 'Synchro non lancée (trop récente, file encore en cours, wsgroups indisponible ou réponse suspecte) : voir les journaux.'
          : "$queued personnes mises en file d'attente.",
      ],
      'method' => 'GET',
      'status' => 200,
    ]);
  }

}
