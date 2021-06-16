<?php

namespace Drupal\micro_footer_umr\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'UmrFooterBlock' block.
 *
 * @Block(
 *  id = "UMR footer_block",
 *  admin_label = @Translation("Umr Footer block"),
 * )
 */
class UmrFooterBlock extends BlockBase {

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

        $footer = $currentSite->get('field_footer_umr');
        $liens = $footer->getValue();

      }
    }
    $build['footer_umr'] = [
      '#theme' => 'micro_footer_umr',
      '#liens' => $liens,
    ];

    return $build;
  }

}
