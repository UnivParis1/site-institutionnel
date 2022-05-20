<?php

namespace Drupal\up1_pages_personnelles\Plugin\QueueWorker;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\up1_pages_personnelles\WsGroupsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\user\Entity\User;

/**
 * Executes users (enseignants & doctorants) import from web service.
 *
 * @QueueWorker(
 *   id = "up1_page_perso_node_creation_queue",
 *   title = @Translation("User already exists. Only create his Page Perso"),
 *   cron = {"time" = 30}
 *  )
 */
class PagePersoQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
   * {@inheritDoc}
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
  public function processItem($data) {
    $user = $data['user'];
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->create([
      'title' => $data['supannCivilite'] . ' ' . $data['displayName'],
      'type' => 'page_personnelle',
      'langcode' => 'fr',
      'uid' => $user->id(),
      'status' => 1,
      'field_uid_ldap' => $data['uid'],
      'site_id' => NULL,
    ]);

    $node->save();
  }
}
