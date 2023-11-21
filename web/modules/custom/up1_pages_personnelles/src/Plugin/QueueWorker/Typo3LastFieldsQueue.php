<?php

namespace Drupal\up1_pages_personnelles\Plugin\QueueWorker;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
use Drupal\up1_pages_personnelles\WsGroupsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity;
use Drupal\Core\Url;

/**
 * Executes English resume & education fields import from web service.
 *
 * @QueueWorker(
 *   id = "up1_typo3_last_fields_queue",
 *   title = @Translation("Page Perso import english resume & education fields."),
 *   cron = {"time" = 60}
 *  )
 */
class Typo3LastFieldsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
   * @var WsGroupsService;
   */
  protected $wsGroups;

  /**
   * thesesQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param LoggerChannelFactoryInterface $logger
   * @param EntityTypeManagerInterface $entity_type
   * @param WsGroupsService $ws_groups
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type,
                              WsGroupsService $ws_groups,
                              LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type;
    $this->loggerChannelFactory = $logger;
    $this->wsGroups = $ws_groups;
  }

  /**
   * {@
   */
  public static function create(ContainerInterface $container,
                                array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('up1_pages_personnelles.wsgroups'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($item) {
    $user = user_load_by_name($item->username);

    if ($user) {
      $author = $user->id();
      $ids = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $author)
        ->accessCheck(FALSE)
        ->execute();
      $pages = Node::loadMultiple($ids);
      if (!empty($pages)) {
        foreach ($pages as $node) {
          try {
            if (isset($item->tx_oxcspagepersonnel_anglais) && !empty($item->tx_oxcspagepersonnel_anglais)) {
              $node->field_english_resume = [
                'value' => "<div>" . $item->tx_oxcspagepersonnel_anglais . "</div>",
                'format' => 'full_html'
              ];
            }
            if (isset($item->tx_oxcspagepersonnel_epi) && !empty($item->tx_oxcspagepersonnel_epi)) {
              $node->field_education = [
                'value' => "<div>" . $item->tx_oxcspagepersonnel_epi . "</div>",
                'format' => 'full_html'
              ];
            }
            $node->site_id = NULL;
            $node->save();
          } catch (\Exception $e) {
            \Drupal::logger('up1_typo3_last_fields_queue')->error($this->t('La page personnelle de @username n\'a pas pu être créée.',
              ['@username' => $item->username] ));
            \Drupal::logger('up1_typo3_last_fields_queue')->error("@code : @Message" , [$e->getCode(), $e->getMessage()]);
          }
        }
      }
    }
  }
}
