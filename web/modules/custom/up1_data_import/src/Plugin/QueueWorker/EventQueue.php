<?php

namespace Drupal\up1_data_import\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\up1_data_import\Service\DataService;

/**
 * executes events import from old website.
 *
 * @QueueWorker(
 *   id = "up1_event_import_queue",
 *   title = @Translation("Import event nodes from old website"),
 *   cron = {"time" = 30}
 *  )
 */
class EventQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * The data service.
   *
   * @var \Drupal\up1_data_import\Service\DataService
   */
  protected $eventService;

  /**
   * NewsQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etmi
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $lcfi
   * @param \Drupal\up1_data_import\Service\DataService $event_service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $etmi, LoggerChannelFactoryInterface $lcfi,
                              DataService $event_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $etmi;
    $this->loggerChannelFactory = $lcfi;
    $this->eventService = $event_service;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('data.service')
    );
  }

  /**
   * @inheritDoc
   */
  public function processItem($item) {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $node = $storage->create(
        $new_nodes[] = [
          'title' => $item['title'],
          'type' => 'event',
          'langcode' => $item['langcode'],
          'uid' => $item['uid'],
          'status' => 1,
          'field_event_address' => $item['field_event_address'],
          'field_address_map' => $item['field_address_map'],
          'field_event_date' => $item['field_event_date'],
          'field_subscription_link' => $item['field_subscription_link'],
          'body' => $item['body'],
          'created' => $item['created'],
        ]);

      if (isset($item['field_media'])) {
        $node->set('field_media', [$item['field_media']]);
      }
      $node->set('field_event_type', $item['field_event_type']);
      $node->set('field_categories', $item['field_categories']);
      $node->set('moderation_state', 'published');

      $node->save();

      if ($node) {
        $this->eventService->populateImportTable($item['nid'], $item['uuid'],
          $node->id(), $node->getCreatedTime());
      }

    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')->warning('Exception thrown for queue $error',
        ['@error' => $e->getMessage()]);
    }
  }
}
