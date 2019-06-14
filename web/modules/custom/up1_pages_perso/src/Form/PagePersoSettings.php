<?php


namespace Drupal\up1_pages_perso\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Up1PagesPersosSettings.
 */
class PagePersoSettings extends ConfigFormBase {

  /**
   *
   * Constructs a \Drupal\up1_pages_perso\Form\PagePersoSettings object.
   *
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
    return 'up1_pages_perso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1_pages_perso.settings');
    $form['webservice'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Perso.'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Details of "Page Perso".'),
    ];
    $form['webservice']['hal_publications'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du web service HAL. '),
      '#description' => $this->t('Hostname or IP Address of the HAL web service.'),
      '#size' => 60,
      '#default_value' => $config->get('webservice.hal_publications'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('up1_pages_perso.settings');
    $webservice_data = $form_state->getValue('webservice');
    $config
      ->set('webservice.hal_publications', $webservice_data['hal_publications']);

    $config->save();
    parent::submitForm($form, $form_state);
    // Confirmation on form submission.
    \Drupal::messenger()
      ->addMessage($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['up1_pages_perso.settings'];
  }

}