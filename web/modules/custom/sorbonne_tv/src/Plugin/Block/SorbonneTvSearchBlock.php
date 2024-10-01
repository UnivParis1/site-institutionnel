<?php
/**
 * Provides a Block
 *
 * @Block(
 *   id = "SorbonneTvSearchBlock",
 *   admin_label = @Translation("Sorbonne TV Search Block"),
 *
 * )
 */

namespace Drupal\sorbonne_tv\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

class SorbonneTvSearchBlock extends BlockBase {

  /**
    * {@inheritdoc}
  */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sorbonne_tv\Form\SorbonneTvSearchForm');
    return $form;
  }
}
