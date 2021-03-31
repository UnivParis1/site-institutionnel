<?php

namespace Drupal\up1_theses\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\up1_theses\Service\ThesesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes theses import from web service.
 *
 * @QueueWorker(
 *   id = "up1_theses_queue_import",
 *   title = @Translation("Import Thèses from web service"),
 *   cron = {"time" = 30}
 *  )
 */
class ThesesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
   * The theses service.
   *
   * @var \Drupal\up1_theses\Service\ThesesService
   */
  protected $thesesService;

  /**
   * thesesQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $lcfi
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etmi
   * @param \Drupal\up1_theses\Service\ThesesService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
      EntityTypeManagerInterface $etmi, LoggerChannelFactoryInterface $lcfi, ThesesService $thesesService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $etmi;
    $this->loggerChannelFactory = $lcfi;
    $this->thesesService = $thesesService;

  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('theses.service')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($item) {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $node = $storage->create([
        'title' => $item['title'],
        'type' => 'viva',
        'langcode' => 'fr',
        'uid' => $item['uid'],
        'status' => 1,
        'site_id' => NULL,
        'field_subtitle' => $item['field_subtitle'],
        'field_thesis_supervisor' => $item['field_thesis_supervisor'],
        'field_event_address' => $item['field_event_address'],
        'field_event_date' => $item['field_event_date'],
        'field_address_map' => $item['field_address_map'],
        'field_edo_code' => $item['cod_edo'],
        'field_edo_label' => $item['lib_edo'],
      ]);
      $node->set('field_categories', [$item['field_categories']]);
      $node->set('field_ecole_doctorale', [$item['field_ecole_doctorale']]);
      $node->set('moderation_state', 'published');

      $node->save();

      if ($node) {
        $this->thesesService->populateImportTable($item['cod_ths'],
          $node->id(), $node->getCreatedTime());
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')->warning('Exception thrown for queue $error',
        ['@error' => $e->getMessage()]);
    }
  }

}
