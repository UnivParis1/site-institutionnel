<?php

namespace Drupal\micro_scheduler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * TaskNotification service.
 */
class TaskNotification {

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

  /**
   * Method description.
   */
  public function execute() {
    $siteStorage = $this->entityTypeManager->getStorage('site');
    $currentDate = new DrupalDateTime();
    $in30days = new DrupalDateTime('+30 days');
    $in8days = new DrupalDateTime('+8 days');


    //treatment of the sites to be unpublished in 30 days
    $siteOutdated30Ids = $siteStorage->getQuery()
      ->condition('status', TRUE)
      ->condition('schedule_end', $in30days->format('Y-m-d'), '=' )
      ->execute();
    $sitesOutdated30 = $siteStorage->loadMultiple($siteOutdated30Ids);
    foreach ($sitesOutdated30 as $siteOutdated30) {
        $to = 'dominique.delepine@open-groupe.com';
       $langcode = 'fr';
      \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublish_notication', $to, $langcode, ['message'
      => 'message à 30 jours', 'days' => '30']);

    }

    //treatment of the sites to be unpublished in 8 days
    $siteOutdated8Ids = $siteStorage->getQuery()
      ->condition('status', TRUE)
      ->condition('schedule_end', $in8days->format('Y-m-d'), '=' )
      ->execute();
    $sitesOutdated8 = $siteStorage->loadMultiple($siteOutdated8Ids);
    foreach ($sitesOutdated8 as $siteOutdated8) {
      $to = 'dominique.delepine@open-groupe.com';
      $langcode = 'fr';
      \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublish_notication', $to, $langcode, ['message'
      => 'message à 8 jours', 'days' => '8']);

    }  }

}
