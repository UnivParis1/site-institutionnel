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

    $form['filtre_site_principal'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Type de recherche et filtre a appliquer pour recupérer les personnes de l'annuaire du site principal"),
      '#description' => $this->t('Doit être du type "searchUser?filter_member_of_group=..."'),
      '#default_value' => $config->get('filtre_site_principal'),
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
      ->set('filtre_site_principal', $form_state->getValue('filtre_site_principal'))
      ->save();
  }

}
