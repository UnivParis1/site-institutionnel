<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a recherche pages personnelles block.
 *
 * @Block(
 *   id = "search_pages_persos_block",
 *   admin_label = @Translation("Recherche pages personnelles"),
 *   category = @Translation("Panthéon-Sorbonne"),
 * )
 */
final class SearchPagesPersosBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build() {

    return \Drupal::formBuilder()->getForm('Drupal\up1_pages_persos\Form\SearchPagePersoForm');
  }

}
