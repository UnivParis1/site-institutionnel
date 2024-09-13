<?php
/**
 * Provides a Block
 *
 * @Block(
 *   id = "lmc_mail_notifications_block",
 *   admin_label = @Translation("LMC mail notifications block"),
 * )
 */

namespace Drupal\lmc_mail_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;

class LmcMailNotificationsBlock extends BlockBase {

  /**
    * {@inheritdoc}
   */
  public function build() {

    $uid = \Drupal::currentUser()->id();
    $extra_class = '';
    $count = \Drupal::service('lmc_mail_notifications.service')->getUserNotificationsCount($uid);
    
    if (intval($count) > 0) {
      $extra_class = 'active';
    }

    $build = [
      'button' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t('Notifications'),
        '#attributes' => [
          'class' => [
            'notifs-toggle ' . $extra_class
          ],
        ],
      ],
      'content' => [
        '#type' => 'view',
        '#name' => 'lmc_mail_notifications',
        '#display_id' => 'user_notifications',
        '#arguments' => [
          $uid,
        ],
        '#prefix' => '<div class="notis-container">',
        '#suffix' => '</div>',
      ],
      '#attributes' => [
        'class' => [
          'lmc-mail-notifications-blk',
        ],
      ],
    ];

    $build['#attached']['library'][] = 'lmc_mail_notifications/notifications_block';

    $build['#cache'] = [
      'max-age' => 0,
    ];

    return $build;
  }
}
