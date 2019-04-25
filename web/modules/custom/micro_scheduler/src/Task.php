<?php


namespace Drupal\micro_scheduler;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\user\Entity\User;

abstract class Task
{

  /**
   * The config factory.
   *@var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  /**
   * Constructs a tasknotification object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  abstract function execute();

  public function _getAdminMailTo() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $roles = $this->configFactory->get('micro_scheduler.settings')->get('unpublish_mail_admin.admin_roles');
    $to = [];

    $userIds = $userStorage->getQuery()
      ->condition('status', 1)
      ->condition('roles', $roles , 'IN')
      ->execute();
    $users = User::loadMultiple($userIds);

    foreach ($users as $user) {
      $to[] = $user->getEmail();
    }

    return implode(',', $to);
  }

  public function _getMicroSiteAdminMailTo(SiteInterface $site) {
    $to = [];
    $users = $site->getUsers();

    foreach ($users as $user) {
      $to[] = $user->getEmail();
    }

    return implode(',', $to);
  }
}
