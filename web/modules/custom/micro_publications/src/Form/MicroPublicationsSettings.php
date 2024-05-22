<?php

namespace Drupal\micro_publications\Form;

class MicroPublicationsSettings extends ConfigFormBase {

  const FORMID = "micro_publications_settings";

  public function getFormId() {
    return self::FORMID;
  }

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
    ];
    $form['parameters']['rows'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre de publications à récupérer'),
      '#description' => $this->t('Nombre de publications à récupérer. Mettre 0 pour tout récupérer.'),
      '#size' => 60,
      '#default_value' => $config->get('parameters.rows'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
