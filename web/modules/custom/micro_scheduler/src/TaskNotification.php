<?php

namespace Drupal\micro_scheduler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * TaskNotification service.
 */
class TaskNotification extends Task {

  /**
   * Method description.
   */
  public function execute() {
    $siteStorage = $this->entityTypeManager->getStorage('site');
    $currentDate = new DrupalDateTime();
    $notificationTiming = array_values($this->configFactory->get('micro_scheduler.settings')->get('notification_timing'));
    $negotiator = \Drupal::service('micro_site.negotiator');
    $adminMailSubject = $this->configFactory->get('micro_scheduler.settings')->get('notification_mail_admin.subject');
    $adminMailMessage = $this->configFactory->get('micro_scheduler.settings')->get('notification_mail_admin.message');
    $siteAdminMailSubject = $this->configFactory->get('micro_scheduler.settings')->get('notification_site_admin_mail.subject');
    $siteAdminMailMessage = $this->configFactory->get('micro_scheduler.settings')->get('notification_site_admin_mail.message');
    $adminTo = $this->_getAdminMailTo();


    foreach ($notificationTiming as $remainingTiming) {
      if (!empty($remainingTiming)) {
        $inDays = new DrupalDateTime('+' . $remainingTiming . 'days');
        $siteOutdatedIds = $siteStorage->getQuery()
          ->condition('status', TRUE)
          ->condition('schedule_end', $inDays->format('Y-m-d'), '=' )
          ->execute();
        $sitesOutdated = $siteStorage->loadMultiple($siteOutdatedIds);
        foreach ($sitesOutdated as $siteOutdated) {
          $negotiator->setActiveSite($siteOutdated);
          $langcode = 'fr';
          $microAdminTo = $this->_getMicroSiteAdminMailTo($siteOutdated);

          \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublish_notication', $adminTo ,
            $langcode,
            ['message' => \Drupal::token()->replace($adminMailMessage, ['remaining_days' => $remainingTiming]), 'subject' =>
              \Drupal::token()
              ->replace
            ($adminMailSubject, ['remaining_days' => $remainingTiming])]);

          \Drupal::service('plugin.manager.mail')->mail('micro_scheduler', 'site_unpublish_notication', $microAdminTo ,
            $langcode,
            ['message' => \Drupal::token()->replace($siteAdminMailMessage, ['remaining_days' => $remainingTiming]), 'subject' => \Drupal::token()->replace
            ($siteAdminMailSubject,['remaining_days' => $remainingTiming])]);
        }
      }
    }
  }

}
