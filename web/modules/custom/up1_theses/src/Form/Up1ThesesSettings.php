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
      '#title' => $this->t('Agenda des soutenances de thèse.'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Enter the details of "Agenda des soutenances de thèse".'),
    ];
    $form['webservice']['protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('HTTP Protocol'),
      '#options' => [
        'http' => $this->t('HTTP (non-secure)'),
        'https' => $this->t('HTTPS (secure)'),
      ],
      '#default_value' => ($config->get('webservice.protocol'))? $config->get('webservice.protocol') : 'http',
      '#description' => $this->t('HTTP protocol type of the  webservice. '),
    ];
    $form['webservice']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the web service.'),
      '#size' => 60,
      '#default_value' => $config->get('webservice.hostname'),
    ];
    $form['address'] = [
      '#type' => 'details',
      '#title' => $this->t('Addresses API.'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Addresses API'),
    ];
    $form['address']['french'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the web service for french addresses.'),
      '#description' => $this->t('URL of the web service to retrieve 
      french addresses coordinates. Exemple : https://api-adresse.data.gouv.fr/search'),
      '#size' => 60,
      '#default_value' => $config->get('address.french'),
    ];
    $form['address']['worldwide'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the web service for worldwide addresses.'),
      '#description' => $this->t('URL of the web service to retrieve 
      worldwide addresses coordinates. Exemple : https://nominatim.openstreetmap.org'),
      '#size' => 60,
      '#default_value' => $config->get('address.worldwide'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('up1_theses.settings');
    $webservice_data = $form_state->getValue('webservice');
    $address_data = $form_state->getValue('address');
    $config
      ->set('webservice.protocol', $webservice_data['protocol'])
      ->set('webservice.hostname', $webservice_data['hostname'])
      ->set('address.french', $address_data['french'])
      ->set('address.worldwide', $address_data['worldwide']);

    $config->save();
    parent::submitForm($form, $form_state);
    // Confirmation on form submission.
    \Drupal::messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['up1_theses.settings'];
  }

}