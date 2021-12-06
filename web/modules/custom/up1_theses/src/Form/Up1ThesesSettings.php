<?php

namespace Drupal\up1_theses\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Up1ThesesSettings.
 *
 * @codeCoverageIgnore
 */
class Up1ThesesSettings extends ConfigFormBase {

  /**
   *
   * Constructs a \Drupal\up1_theses\Form\Up1ThesesSettings object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'up1_theses_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1_theses.settings');
    $form['webservice'] = [
      '#type' => 'details',
      '#title' => $this->t('Récupération des soutenances de thèse depuis Apogée.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['webservice']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the web service.'),
      '#size' => 60,
      '#default_value' => $config->get('webservice.hostname'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('up1_theses.settings');
    $webservice_data = $form_state->getValue('webservice');
    $config->set('webservice.hostname', $webservice_data['hostname']);

    $config->save();
    parent::submitForm($form, $form_state);
    \Drupal::messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['up1_theses.settings'];
  }

}
