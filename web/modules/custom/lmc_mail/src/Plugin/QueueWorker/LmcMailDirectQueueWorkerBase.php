<?php

namespace Drupal\lmc_mail\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LmcMailDirectQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $langcode = $item->params['language'] ?? 'fr';

    $mailManager = \Drupal::service('plugin.manager.mail');
    $mailManager->mail('lmc_mail', $item->key, $item->mail, $langcode, $item->params, NULL, TRUE);
  }

}
