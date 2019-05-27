<?php

namespace Drupal\up1_keywords\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeywordsConfigForm.
 */
class KeywordsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'up1_keywords.keywordsconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keywords_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1_keywords.keywordsconfig');
    $form['url_resultat_de_recherche'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de la vue de résultat de recherche'),
      '#description' => $this->t('Doit être du type "https://URL-de-la-page/de-recherche?identifiant-du-filtre-de-recherche="'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('url_resultat_de_recherche'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('up1_keywords.keywordsconfig')
      ->set('url_resultat_de_recherche', $form_state->getValue('url_resultat_de_recherche'))
      ->save();
  }

}
