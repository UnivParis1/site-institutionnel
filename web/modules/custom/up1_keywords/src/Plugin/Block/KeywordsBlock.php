<?php

namespace Drupal\up1_keywords\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'KeywordsBlock' block.
 *
 * @Block(
 *  id = "keywords_block",
 *  admin_label = @Translation("Mots-clés saisonniers"),
 *  category = @Translation("Panthéon-Sorbonne"),
 * )
 */
class KeywordsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = \Drupal::config('up1_keywords.settings');
    $keywords = $config->get('keywords_links');
    foreach ($keywords as $key => $keyword) {
      if (empty($keyword['title']) || empty($keyword['uri'])) {
        unset($keywords[$key]);
      }
    }

    $current_path = \Drupal::service('path.current')->getPath();
    $search_form = [];
    if (!preg_match('/resultats-recherche/', $current_path)) {
      $search_form = \Drupal::formBuilder()->getForm('Drupal\up1_keywords\Form\HomepageSearchForm');
    }

    $build['up1_keywords'] = [
      '#theme' => 'up1_keywords',
      '#keywords' =>  $keywords,
      '#search' => $search_form,
    ];

    return $build;
  }

}
