<?php

namespace Drupal\micro_annuaire_sorbonne\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AnnuaireConfigForm.
 */
class AnnuaireConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'micro_annuaire_sorbonne.annuaireconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'annuaire_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micro_annuaire_sorbonne.annuaireconfig');
    $form['url_ws'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du web service WSGROUPS'),
      '#description' => $this->t('Doit être du type "https://wsgroups.univ-paris1.fr/"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('url_ws'),
    ];

    $form['search_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Type de recherche"),
      '#description' => $this->t('Doit être du type "searchUser, searchUserTrusted etc."'),
      '#default_value' => $config->get('search_user'),
    ];

    $form['filtre_site_principal'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Filtre(s) à appliquer pour recupérer la liste des pages personnelles."),
      '#description' => $this->t('Doit être du type "filter_member_of_group=..."'),
      '#default_value' => $config->get('filtre_site_principal'),
    ];

    $form['filtre_faculty'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Filtre(s) à appliquer pour recupérer uniquement la liste des pages personnelles des enseignants-chercheurs"),
      '#description' => $this->t('Doit être du type "filter_eduPersonAffiliation=..."'),
      '#default_value' => $config->get('filtre_faculty'),
    ];

    $form['filtre_student'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Filtre(s) a appliquer pour recupérer uniquement la liste des pages personnelles des doctorants"),
      '#description' => $this->t('Doit être du type "filter_eduPersonAffiliation=..., filter_student="'),
      '#default_value' => $config->get('filtre_student'),
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


    $this->config('micro_annuaire_sorbonne.annuaireconfig')
      ->set('url_ws', $form_state->getValue('url_ws'))
      ->set('search_user', $form_state->getValue('search_user'))
      ->set('filtre_site_principal', $form_state->getValue('filtre_site_principal'))
      ->set('filtre_faculty', $form_state->getValue('filtre_faculty'))
      ->set('filtre_student', $form_state->getValue('filtre_student'))
      ->save();
  }

}
