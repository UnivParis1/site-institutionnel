<?php

namespace Drupal\micro_publications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MicroPublicationsSettings extends ConfigFormBase {

  const FORMID = 'micro_publications_settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORMID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micro_publications.settings');

    $form['webservice'] = [
      '#type' => 'details',
      '#title' => $this->t('Récupération des publications depuis HAL.'),
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
    $form['parameters'] = [
      '#type' => 'details',
      '#title' => $this->t('Paramètres de la requête HAL.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['parameters']['wt'] = [
      '#type' => 'radios',
      'title' => $this->t('Format de retour'),
      '#options' => [
        'json' => $this->t('JSON'),
        'xml' => $this->t('XML'),
      ],
      '#default_value' => $config->get('parameters.wt'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('micro_publications.settings')
      ->set('hostname', $form_state->getValue(['webservice','hostname']))
      ->set('wt', $form_state->getValue(['parameters','wt']))
      ->save();
  }

  protected function getEditableConfigNames(): array
  {
    return [
      'micro_publications.settings',
    ];
  }
}
