<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Manager\HalPublications;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rafraîchit l'encart « Publications HAL » d'une page par élément de file.
 */
#[QueueWorker(
  id: HalPublications::QUEUE,
  title: new TranslatableMarkup('Rafraîchissement des publications HAL des pages personnelles'),
  cron: ['time' => 120],
)]
final class HalRefreshQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly HalPublications $halPublications,
    private readonly LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(HalPublications::class),
      $container->get('logger.channel.up1_pages'),
    );
  }

  public function processItem($data): void {
    try {
      $result = $this->halPublications->refresh((int) $data['nid']);
    }
    catch (WsUnavailableException $e) {
      // HAL ne répond pas : encart conservé, élément gardé en file et file
      // suspendue jusqu'au prochain cron.
      throw new SuspendQueueException('HAL indisponible : ' . $e->getMessage(), 0, $e);
    }
    catch (\Throwable $e) {
      $this->logger->error('Publications HAL de la page @nid non rafraîchies : @message', [
        '@nid' => $data['nid'] ?? '?',
        '@message' => $e->getMessage(),
      ]);
      return;
    }

    if ($result === HalPublications::UPDATED) {
      $this->logger->info('Publications HAL de la page @nid mises à jour.', ['@nid' => $data['nid']]);
    }
  }

}
