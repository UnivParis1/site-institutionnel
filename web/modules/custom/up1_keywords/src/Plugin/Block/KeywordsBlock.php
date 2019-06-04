<?php

namespace Drupal\up1_keywords\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'KeywordsBlock' block.
 *
 * @Block(
 *  id = "keywords_block",
 *  admin_label = @Translation("Nuage de mots cle"),
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
//    dump($this->configuration);
    $form['nuage'] = [
      '#type' => 'fieldset',
      '#title' => t('Cloud'),
      '#description' => t('Enter generally used keywords for search'),
      '#tree' => TRUE,
    ];

    $form['nuage']['mot_cle_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword 1'),
      '#default_value' => (empty($this->configuration['mot_cle_1'])?'':$this->configuration['mot_cle_1']),
      '#maxlength' => 100,
      '#size' => 100,
      '#weight' => '0',
    ];
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
//    dump($form_state->getValue('nuage'));
//    die();
    $fs = $form_state->getValue('nuage');
    $this->configuration['mot_cle_1'] = $fs['mot_cle_1'];
    $this->configuration['mot_cle_2'] = $fs['mot_cle_2'];
    $this->configuration['mot_cle_3'] = $fs['mot_cle_3'];
    $this->configuration['mot_cle_4'] = $fs['mot_cle_4'];
    $this->configuration['mot_cle_5'] = $fs['mot_cle_5'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = \Drupal::config('up1_keywords.keywordsconfig');
    $keywords = [];
    $keywords['mot_cle_1'] = $this->configuration['mot_cle_1'];
    $keywords['mot_cle_2'] = $this->configuration['mot_cle_2'];
    $keywords['mot_cle_3'] = $this->configuration['mot_cle_3'];
    $keywords['mot_cle_4'] = $this->configuration['mot_cle_4'];
    $keywords['mot_cle_5'] = $this->configuration['mot_cle_5'];
    $build['up1_keywords'] = [
      '#theme' => 'up1_keywords',
      '#keywords' => $keywords,
      '#url' => $config->get('url_resultat_de_recherche'),
    ];

    return $build;
  }

}
