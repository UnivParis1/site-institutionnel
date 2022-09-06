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
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = \Drupal::config('up1_keywords.keywordsconfig');

    $current_path = \Drupal::service('path.current')->getPath();
        $search_form = [];
        if (!preg_match('/resultats-recherche/', $current_path)) {
		      $search_form = \Drupal::formBuilder()->getForm('Drupal\up1_keywords\Form\HomepageSearchForm');
		          }
	$menu =_up1_keywords_render_menu_navigation('mots-cles-page-d-accueil');
    $build['up1_keywords'] = [
      '#theme' => 'up1_keywords',
      '#search' => $search_form,
      '#menu' =>$menu,
      '#url' => $config->get('url_resultat_de_recherche'),
    ];

    return $build;
  }
}
