<?php

namespace Drupal\lmc_recruitment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LmcRecruitmentConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lmc_recruitment_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lmc_recruitment.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form   = parent::buildForm($form, $form_state);
    $config = $this->config('lmc_recruitment.settings');
    $form['ignore_date'] = [
      '#type' => 'checkbox',
      '#title' => 'Check this box to ignore "last_modification_date" from beetween.',
      '#default_value' => $config->get('ignore_date'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lmc_recruitment.settings')
      ->set('ignore_date', $form_state->getValue('ignore_date'));
    $config->save();

    parent::submitForm($form, $form_state);
  }


}
