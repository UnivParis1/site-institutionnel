<?php

namespace Drupal\micro_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FooterBlock' block.
 *
 * @Block(
 *  id = "footer_block",
 *  admin_label = @Translation("Footer block"),
 * )
 */
class FooterBlock extends BlockBase {

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

        $footer = $currentSite->get('field_footer');
        $liens = $footer->getValue();

      }
    }
    $build['footer'] = [
      '#theme' => 'micro_footer',
      '#liens' => $liens,
    ];

    return $build;
  }

}
