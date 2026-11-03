<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Applique la synchro wsgroups, une personne par élément de file.
 *
 * N'appelle aucun web service : tout ce qu'il faut est dans l'élément.
 */
#[QueueWorker(
  id: PagePersoSyncManager::QUEUE,
  title: new TranslatableMarkup('Synchronisation des pages personnelles avec wsgroups'),
  cron: ['time' => 60],
)]
final class PagePersoSyncQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly PagePersoSyncManager $syncManager,
    private readonly LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(PagePersoSyncManager::class),
      $container->get('logger.channel.up1_pages'),
    );
  }

  public function processItem($data): void {
    try {
      $this->syncManager->apply($data);
    }
    catch (WsUnavailableException $e) {
      // wsgroups ne peut pas confirmer une désactivation : on ne dépublie
      // rien, l'élément reste en file et la file est suspendue jusqu'au
      // prochain cron.
      throw new SuspendQueueException('wsgroups indisponible : ' . $e->getMessage(), 0, $e);
    }
    catch (\Throwable $e) {
      // On n'arrête pas la file pour une personne : l'écart sera de nouveau
      // détecté à la prochaine comparaison.
      $this->logger->error('Synchro de la page personnelle de @login impossible : @message', [
        '@login' => $data['login'] ?? '?',
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
