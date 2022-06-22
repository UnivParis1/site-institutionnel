<?php

namespace Drupal\up1_theses\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\up1_theses\Service\ThesesService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Executes up1_theses_import_queue.
 *
 * @QueueWorker(
 *   id = "up1_theses_import_queue",
 *   title = @Translation("Import Thèses from web service"),
 *   cron = {"time" = 30}
 *  )
 */
class NewThesesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;
  /**
   * The theses service.
   *
   * @var ThesesService
   */
  protected $thesesService;

  /**
   * thesesQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param LoggerChannelFactoryInterface $lcfi
   * @param EntityTypeManagerInterface $etmi
   * @param ThesesService $ts
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $etmi, LoggerChannelFactoryInterface $lcfi, ThesesService $ts) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $etmi;
    $this->loggerChannelFactory = $lcfi;
    $this->thesesService = $ts;

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
        'field_co_director' => $item['field_co_director'],
        'field_board' => $item['field_board'],
        'field_event_address' => $item['field_event_address'],
        'field_viva_date' => $item['field_viva_date'],
        'field_hdr' => $item['field_hdr'],
        'field_edo_code' => $item['cod_edo'],
        'field_ths_code' => $item['cod_ths'],
        'field_edo_label' => $item['lib_edo'],
      ]);
      $node->set('field_categories', [$item['field_categories']]);
      $node->set('moderation_state', 'published');

      $node->save();

      if ($node) {
        $this->thesesService->populateImportTable($item['cod_ths'],
          $node->id(), $node->getCreatedTime());
      }
    } catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')->warning('Exception thrown for queue $error',
        ['@error' => $e->getMessage()]);
    }
  }
}
