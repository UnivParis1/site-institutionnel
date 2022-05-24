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
 * Executes up1_theses_updates_queue.
 *
 * @QueueWorker(
 *   id = "up1_theses_updates_queue",
 *   title = @Translation("Update existing vivas from web service"),
 *   cron = {"time" = 30}
 *  )
 */
class UpdateThesesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
          $node = Node::load($item[0]);
          $node->set('title', $item['title']);
          $node->set('type', 'viva');
          $node->set('langcode', 'fr');
          $node->set('uid', $item['uid']);
          $node->set('status', 1);
          $node->set('site_id', NULL);
          $node->set('field_subtitle', $item['field_subtitle']);
          $node->set('field_thesis_supervisor', $item['field_thesis_supervisor']);
          $node->set('field_co_director', $item['field_co_director']);
          $node->set('field_board', $item['field_board']);
          $node->set('field_event_address', $item['field_event_address']);
          $node->set('field_viva_date', $item['field_viva_date']);
          $node->set('field_hdr', $item['field_hdr']);
          $node->set('field_edo_code', $item['cod_edo']);
          $node->set('field_ths_code', $item['cod_ths']);
          $node->set('field_edo_label', $item['lib_edo']);

          $node->save();
      }

    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')->warning('Exception thrown for queue $error',
        ['@error' => $e->getMessage()]);
    }
  }

}
