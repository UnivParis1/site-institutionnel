<?php

namespace Drupal\lmc_mail_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

class LmcMailNotificationsConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lmc_mail__notifications_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form          = parent::buildForm($form, $form_state);
    $config        = $this->config('lmc_mail_notifications.settings');
    $settings      = $config->get('lmc_mail_notifications.settings.config');
    $roles_options = [];

    $form['config'] = [
      '#type'  => 'fieldset',
      '#title' => t('Configuration'),
      '#tree'  => TRUE,
    ];

    $roles = Role::loadMultiple();
    foreach ($roles as $role) {
      $roles_options[$role->id()] = $role->label();
    }

    unset($roles_options['anonymous']);

    $form['config']['roles'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Save the notifications for the following roles :'),
      '#default_value' => $settings['roles'] ?? [],
      '#options'       => $roles_options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lmc_mail_notifications.settings');
    $config->set('lmc_mail_notifications.settings.config', $form_state->getValue('config'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lmc_mail_notifications.settings',
      'lmc_mail_notifications.settings.config',
    ];
  }
}
