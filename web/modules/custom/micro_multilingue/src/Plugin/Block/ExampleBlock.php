<?php

namespace Drupal\micro_multilingue\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "micro_multilingue_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("micro_multilingue")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

}
