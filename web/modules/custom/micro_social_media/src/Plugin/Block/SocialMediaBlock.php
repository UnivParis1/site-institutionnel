<?php

namespace Drupal\micro_social_media\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SocialMediaBlock' block.
 *
 * @Block(
 *  id = "social_media_block",
 *  admin_label = @Translation("Social Media block"),
 * )
 */
class SocialMediaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $liens = [];
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();

      if (!empty($currentSiteId)) {
        $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
        $currentSite = $siteStorage->load($currentSiteId);

        $social_media = $currentSite->get('field_social_media');
        $liens = $social_media->getValue();

      }
    }
    $build['social_media'] = [
      '#theme' => 'micro_social_media',
      '#liens' => $liens,
    ];

    return $build;
  }

}
