<?php

namespace Drupal\up1_efs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides an "Expert Finder System" block.
 * @Block(
 *   id="up1_efs_block",
 *   admin_label = @Translation("Expert Finder System block"),
 *   category = @Translation("Panthéon-Sorbonne"),
 * )
 *
 * @package Drupal\up1_efs\Plugin\Block
 */
class EfsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'efs_wrapper';

    return $build;
  }
}
