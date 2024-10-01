<?php

namespace Drupal\lmc_mail_notifications\Services;

/**
 * Class LmcMailNotificationsService.
 */
class LmcMailNotificationsService {

  /**
   * Add notification info.
   *
   * @param string $subject
   *   Subject string.
   *
   * @param string $body
   *   Body html string.
   *
   * @param User $user
   *   User object.
   *
   * @return void
   */
  public function addNotificationInfo($subject, $body, $user) {
    $user_roles = $user->getRoles();

    $config = \Drupal::config('lmc_mail_notifications.settings')->get('lmc_mail_notifications.settings.config');
    if (!empty($config['roles'])) {
      $add_notif_for_roles = array_filter($config['roles']);

      if (!empty($add_notif_for_roles)) {
        $add_notif = FALSE;
        foreach ($user_roles as $user_role) {
          if (in_array($user_role, $add_notif_for_roles)) {
            $add_notif = TRUE;
            break;
          }
        }

        if ($add_notif) {
          $notif_fields = [
            'uid'       => $user->id(),
            'subject'   => $subject,
            'body'      => $body,
            'timestamp' => \Drupal::time()->getRequestTime(),
          ];

          $this->addNotificationEntry($notif_fields);
        }
      }
    }
  }

  /**
   * Add notification.
   *
   * @param array $fields
   *   Associative fields array.
   *
   * @return NULL|int
   */
  function addNotificationEntry(array $fields) {
    $result = NULL;

    try {
      $result = \Drupal::database()->insert('lmc_mail_notifications')
        ->fields($fields)
        ->execute();
    } catch (\Exception $e) {
     \Drupal::logger('LMC mail notification : addNotificationEntry')->error($e->getMessage());
    }

    return $result;
  }

  /**
   * Get notifications info.
   *
   * @param int $id
   *   Notification id.
   *
   * @return int
   */
  public function getNotificationEntry($id) {
    $result = \Drupal::database()->select('lmc_mail_notifications', 'n')
      ->fields('n')
      ->condition('n.id', $id)
      ->execute()
      ->fetchField();

    return $result;
  }

   /**
   * Get notifications count for user.
   *
   * @param int $uid
   *   User uid.
   *
   * @return int
   */
  public function getUserNotificationsCount($uid) {
    $result = \Drupal::database()->select('lmc_mail_notifications', 'n')
      ->fields('n', ['id'])
      ->condition('n.uid', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();

    return $result;
  }

  /**
   * Delete notifications info.
   *
   * @param int $id
   *   Notification id.
   *
   * @return void
   */
  public function deleteNotificationEntry($id) {
    \Drupal::database()
      ->delete('lmc_mail_notifications')
      ->condition('id', $id)
      ->execute();
  }


  /**
   * Delete notifications older than specific timestamp.
   *
   * @param int $timestamp
   *   Timestamp before which notifications should be deleted.
   *
   * @return void
   */
  public function deleteNotificationEntriesOlderThanTimestamp($timestamp) {
    \Drupal::database()
      ->delete('lmc_mail_notifications')
      ->condition('timestamp', $timestamp, '<=')
      ->execute();
  }
}
