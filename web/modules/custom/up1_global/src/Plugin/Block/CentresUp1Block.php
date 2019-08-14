<?php

namespace Drupal\up1_global\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a "Ent & Search" block.
 * @Block(
 *   id="centres_up1_block",
 *   admin_label = @Translation("Centres UP1 block"),
 *   category = @Translation("Panthéon-Sorbonne"),
 * )
 *
 * @package Drupal\up1_global\Plugin\Block
 */
class CentresUp1Block extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\up1_global\Form\CentresUp1Form');
    return $form;
  }
}
