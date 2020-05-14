<?php

namespace Drupal\up1_pages_personnelles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PagePersonnelleSettingsForm.
 *
 * @ingroup up1_pages_personnelles
 */
class PagesPersonnellesSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'up1_pages_personnelles.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'up1_pages_personnelles_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('up1_pages_personnelles.settings')
      ->set('url_ws', $form_state->getValue('url_ws'))
      ->set('search_user', $form_state->getValue('search_user'))
      ->set('filtre_faculty', $form_state->getValue('filtre_faculty'))
      ->set('filtre_student', $form_state->getValue('filtre_student'))
      ->set('other_filters', $form_state->getValue('other_filters'))
      ->set('search_user_page', $form_state->getValue('search_user_page'))
      ->set('url_hal_api', $form_state->getValue('url_hal_api'))
      ->set('url_userphoto', $form_state->getValue('url_userphoto'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Defines the settings form for Page personnelle entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1_pages_personnelles.settings');

    $form['wsGroups'] = [
      '#type' => 'details',
      '#title' => t('WS groups'),
      '#weight' => 99,
      '#open' => TRUE,
    ];
    $form['wsGroups']['url_ws'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du web service WSGROUPS'),
      '#description' => $this->t('Doit être du type "https://wsgroups.univ-paris1.fr/"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('url_ws'),
      '#required' => TRUE
    ];

    $form['list'] = [
      '#type' => 'details',
      '#title' => t('Configuration pour la liste des pages personnelles'),
      '#weight' => 99,
      '#open' => TRUE,
    ];

    $form['list']['search_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Recherche initiale"),
      '#description' => $this->t('Doit être du type "searchUser, searchUserTrusted etc., avec filtre si nécessaire. "'),
      '#default_value' => $config->get('search_user'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];

    $form['list']['filtre_faculty'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Filtre(s) à appliquer pour recupérer les enseignants-chercheurs"),
      '#description' => $this->t('Doit être du type "filter_eduPersonAffiliation=..."'),
      '#default_value' => $config->get('filtre_faculty'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];

    $form['list']['filtre_student'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Filtre(s) à appliquer pour recupérer les doctorants"),
      '#description' => $this->t('Doit être du type "filter_eduPersonAffiliation=..., filter_student="'),
      '#default_value' => $config->get('filtre_student'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];

    $form['list']['other_filters'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Filtre(s) supplémentaire(s) à appliquer."),
      '#description' => $this->t('cf. https://github.com/UnivParis1/wsgroups#usergroupsandroles'),
      '#default_value' => $config->get('filtre_site_principal'),
    ];

    $form['single_page'] = [
      '#type' => 'details',
      '#title' => t('Paramètres pour une page personnelle'),
      '#weight' => 99,
      '#open' => TRUE,
    ];

    $form['single_page']['search_user_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Recherche initiale "),
      '#description' => $this->t('Doit être du type "searchUser, searchUserTrusted etc., avec filtre si nécessaire. "'),
      '#default_value' => $config->get('search_user_page'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];

    $form['single_page']['url_userphoto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URLdu webservice userphoto'),
      '#default_value' => $config->get('url_userphoto'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];
    $form['single_page']['url_hal_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the web service HAL'),
      '#default_value' => $config->get('url_hal_api'),
      '#maxlength' => 255,
      '#size' => 120,
      '#required' => TRUE
    ];

    return parent::buildForm($form, $form_state);
  }

}
