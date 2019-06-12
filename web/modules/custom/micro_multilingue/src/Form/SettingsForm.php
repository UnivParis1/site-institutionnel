<?php

namespace Drupal\micro_multilingue\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure micro_multilingue settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_multilingue_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_multilingue.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * @var Drupal\Core\Language\LanguageManager $languageManager
     */
    $languageManager = Drupal::service('language_manager');
    $languages = $languageManager->getLanguages();
    $options = [];


    foreach ($languages as $id => $language) {
      $options[$id] = $language->getName();
    }

    $form['host_active_language'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Host Site Available Language'),
      '#options' => $options,
      '#default_value' => $this->config('micro_multilingue.settings')->get('host_active_language'),
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
    $this->config('micro_multilingue.settings')
      ->set('host_active_language', $form_state->getValue('host_active_language'))
      ->save();
    parent::submitForm($form, $form_state);
  }



}
