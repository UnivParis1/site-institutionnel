<?php

namespace Drupal\up1_global\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Up1Settings extends ConfigFormBase {

  /**
   * Class UP1Settings.
   * @codeCoverageIgnore
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'up1_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1.settings');

    $form['webservice_centres'] = [
      '#type' => 'details',
      '#title' => $this->t('Annuaire des centres UP1.'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Enter the details of "Annuaire des centres UP1" webservice.'),
    ];
    $form['webservice_centres']['protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('HTTP Protocol'),
      '#options' => [
        'http' => $this->t('HTTP (non-secure)'),
        'https' => $this->t('HTTPS (secure)'),
      ],
      '#default_value' => ($config->get('webservice_centres.protocol'))? $config->get('webservice_centres.protocol') : 'http',
      '#description' => $this->t('HTTP protocol type of the  webservice. '),
    ];
    $form['webservice_centres']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the web service.'),
      '#size' => 60,
      '#default_value' => $config->get('webservice_centres.hostname'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('up1.settings');
    $webservice_data = $form_state->getValue('webservice_centres');
    $config
      ->set('webservice_centres.protocol', $webservice_data['protocol'])
      ->set('webservice_centres.hostname', $webservice_data['hostname'])
    ;

    $config->save();
    parent::submitForm($form, $form_state);
    // Confirmation on form submission.
    \Drupal::messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['up1.settings'];
  }
}
