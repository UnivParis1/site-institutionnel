<?php

namespace Drupal\up1_pages_perso\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PagePersoTypeForm.
 */
class PagePersoTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $page_perso_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $page_perso_type->label(),
      '#description' => $this->t("Label for the Page perso type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $page_perso_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\up1_pages_perso\Entity\PagePersoType::load',
      ],
      '#disabled' => !$page_perso_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $page_perso_type = $this->entity;
    $status = $page_perso_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Page perso type.', [
          '%label' => $page_perso_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Page perso type.', [
          '%label' => $page_perso_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($page_perso_type->toUrl('collection'));
  }

}
