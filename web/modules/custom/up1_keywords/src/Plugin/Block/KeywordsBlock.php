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
  public function blockForm($form, FormStateInterface $form_state) {
    $form['nuage'] = [
      '#type' => 'fieldset',
      '#title' => t('Cloud'),
      '#description' => t('Enter generally used keywords for search'),
      '#tree' => TRUE,
    ];
	for($i=1;$i<=5;$i++){

    $form['nuage']['mot_cle_'.$i] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword '.$i),
      '#default_value' => (empty($this->configuration['mot_cle_'.$i])?'':$this->configuration['mot_cle_'.$i]),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
	    $form['nuage']['lien_mot_cle_'.$i] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lien Keyword '.$i),
      '#default_value' => (empty($this->configuration['lien_mot_cle_'.$i])?'':$this->configuration['lien_mot_cle_'.$i]),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
	}
	
	
	
    $form['nuage']['mot_cle_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword 2'),
      '#default_value' => (empty($this->configuration['mot_cle_2'])?'':$this->configuration['mot_cle_2']),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
    $form['nuage']['mot_cle_3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword 3'),
      '#default_value' => (empty($this->configuration['mot_cle_3'])?'':$this->configuration['mot_cle_3']),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
    $form['nuage']['mot_cle_4'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword 4'),
      '#default_value' => (empty($this->configuration['mot_cle_4'])?'':$this->configuration['mot_cle_4']),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
    $form['nuage']['mot_cle_5'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword 5'),
      '#default_value' => (empty($this->configuration['mot_cle_5'])?'':$this->configuration['mot_cle_5']),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $fs = $form_state->getValue('nuage');
	for($i=1;$i<=5;$i++){
		$this->configuration['mot_cle_'.$i] = $fs['mot_cle_'.$i];
		$this->configuration['lien_mot_cle_'.$i] = $fs['lien_mot_cle_'.$i];
	}
    /*$this->configuration['mot_cle_1'] = $fs['mot_cle_1'];
    $this->configuration['mot_cle_2'] = $fs['mot_cle_2'];
    $this->configuration['mot_cle_3'] = $fs['mot_cle_3'];
    $this->configuration['mot_cle_4'] = $fs['mot_cle_4'];
    $this->configuration['mot_cle_5'] = $fs['mot_cle_5'];*/
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = \Drupal::config('up1_keywords.keywordsconfig');
    $keywords = [];
	for($i=1;$i<=5;$i++){
		$keywords['mot_cle_'.$i] = $this->configuration['mot_cle_'.$i];
		$keywordslinks['lien_mot_cle_'.$i] = $this->configuration['lien_mot_cle_'.$i];
	}
    /*$keywords['mot_cle_1'] = $this->configuration['mot_cle_1'];
    $keywords['mot_cle_2'] = $this->configuration['mot_cle_2'];
    $keywords['mot_cle_3'] = $this->configuration['mot_cle_3'];
    $keywords['mot_cle_4'] = $this->configuration['mot_cle_4'];
    $keywords['mot_cle_5'] = $this->configuration['mot_cle_5'];*/

    $current_path = \Drupal::service('path.current')->getPath();
        $search_form = [];
        if (!preg_match('/resultats-recherche/', $current_path)) {
		      $search_form = \Drupal::formBuilder()->getForm('Drupal\up1_keywords\Form\HomepageSearchForm');
		          }
	$menu =_up1_keywords_render_menu_navigation('mots-cles-page-d-accueil');
    $build['up1_keywords'] = [
      '#theme' => 'up1_keywords',
      '#keywords' => $keywords,
	  '#keywordslinks' => $keywordslinks,
      '#search' => $search_form,
	  '#menu' =>$menu,
      '#url' => $config->get('url_resultat_de_recherche'),
    ];

    return $build;
  }

}
