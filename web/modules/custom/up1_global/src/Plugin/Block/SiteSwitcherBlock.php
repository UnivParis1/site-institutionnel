<?php

declare(strict_types=1);

namespace Drupal\up1_global\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Site\Settings;

/**
 * Provides a siteswitcherblock block.
 *
 * @Block(
 *   id = "site_switcher_block",
 *   admin_label = @Translation("SiteSwitcherBlock"),
 *   category = @Translation("Panthéon-Sorbonne"),
 * )
 */
final class SiteSwitcherBlock extends BlockBase {

  public function build() {
    $website_settings = Settings::get('website');
    $current_request = \Drupal::request();
    $host = $current_request->getHost();

    if (trim($website_settings['french_version']) === $host ||
      trim($website_settings['english_version']) === $host) {

      return [
        '#theme' => 'up1_site_switcher_block',
        '#current_language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
        '#french_version' => trim($website_settings['french_version']),
        '#english_version' => trim($website_settings['english_version']),
      ];
    }

    else return [];
  }

}
