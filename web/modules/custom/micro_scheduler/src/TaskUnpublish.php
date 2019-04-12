<?php

namespace Drupal\micro_scheduler;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\user\Entity\User;

/**
 * TaskUnpublish service.
 */
class TaskUnpublish {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constructs a taskunpublish object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface
  $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Method description.
   */
  public function execute() {
    $siteStorage = $this->entityTypeManager->getStorage('site');
    $negotiator = \Drupal::service('micro_site.negotiator');
    $currentDate = new DrupalDateTime();
    $siteOutdatedIds = $siteStorage->getQuery()
      ->condition('status', TRUE)
      ->condition('schedule_end', $currentDate->format('Y-m-d'), '<=')
      ->execute();

    $adminTo = $this->_getAdminMailTo();

    $sitesOutdated = $siteStorage->loadMultiple($siteOutdatedIds);
    $adminMailSubject = $this->configFactory->get('micro_scheduler.settings')->get('unpublish_mail_admin.subject');
    $adminMailMessage = $this->configFactory->get('micro_scheduler.settings')->get('unpublish_mail_admin.message');
    $siteAdminMailSubject = $this->configFactory->get('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.subject');
    $siteAdminMailMessage = $this->configFactory->get('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.message');

    foreach ($sitesOutdated as $siteOutdated) {
      if($siteOutdated->setUnpublished()->save()) {
        $negotiator->setActiveSite($siteOutdated);
        $langcode = 'fr';
        $microAdminTo = $this->_getMicroSiteAdminMailTo($siteOutdated);

        \Drupal::logger('micro_scheduler')->info('The micro_site named : @site_name has been unpublished because is outdated',
          ['@site_name' => $siteOutdated->getName()]);

        \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublished', $adminTo ,
        $langcode,
        ['message' => \Drupal::token()->replace($adminMailMessage), 'subject' => \Drupal::token()->replace($adminMailSubject)]);

        \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublished', $microAdminTo ,
          $langcode,
          ['message' => \Drupal::token()->replace($siteAdminMailMessage), 'subject' => \Drupal::token()->replace($siteAdminMailSubject)]);
      }
    }
  }

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
