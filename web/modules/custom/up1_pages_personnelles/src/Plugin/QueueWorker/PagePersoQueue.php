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
 *   id = "up1_page_perso_queue",
 *   title = @Translation("Page Perso creation"),
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
  public function processItem($item) {
    switch ($item['case']) {
      case 'new-ecd' :
        $cas_settings = \Drupal::config('cas.settings');
        $cas_user_manager = \Drupal::service('cas.user_manager');

        $user_properties = [
          'roles' => ['enseignant_doctorant'],
        ];
        $email_assignment_strategy = $cas_settings->get('user_accounts.email_assignment_strategy');
        if ($email_assignment_strategy === CasUserManager::EMAIL_ASSIGNMENT_STANDARD) {
          $user_properties['mail'] = $item['mail'];
        }
        try {
          $user = $cas_user_manager->register($item['uid'], $user_properties);
          $this->createNode($user->id(), $item);
        } catch (CasLoginException $e) {
          \Drupal::logger('cas')->error('CasLoginException when
        registering user with name %name: %e', [
            '%name' => $item['uid'],
            '%e' => $e->getMessage()
          ]);
          return;
        }
        break;
      case 'no-pp':
        $user = $item['user'];
        $this->createNode($user->id(), $item);
        break;
    }
  }

  private function createNode($uid, $data) {
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->create([
      'title' => $data['supannCivilite'] . ' ' . $data['displayName'],
      'type' => 'page_personnelle',
      'langcode' => 'fr',
      'uid' => $uid,
      'status' => 1,
      'field_uid_ldap' => $data['uid'],
      'site_id' => NULL,
    ]);

    $node->save();
  }
}
