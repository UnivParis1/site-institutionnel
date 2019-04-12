<?php

namespace Drupal\micro_scheduler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Configure Micro Scheduler settings for this site.
 */
class MicroSchedulerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_scheduler_micro_scheduler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_scheduler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['default_life_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Life Time'),
      '#size' => '3',
      '#description' => $this->t('Number of days by default after which micro sites will be unpublished'),
      '#default_value' => $this->config('micro_scheduler.settings')->get('default_life_time'),
    ];

    $form['unpublish_mail_admin'] = [
      '#type' => 'fieldset',
      '#title' => t('Unpublish Mail to Admin'),
      '#collapsible' => TRUE
    ];
    $form['unpublish_mail_admin']['admin_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#options' => user_role_names(),
      '#default_value' => ($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.admin_roles') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.admin_roles') : []),
    ];
    $form['unpublish_mail_admin']['admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.subject') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.subject') : 'The site [micro_site:name] has been unpublished')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['unpublish_mail_admin']['admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.message') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.message') : 'Without action on your part, the site [micro_site:name] was automatically unpublished.
Webmaster (s):
[micro_site:admininistrator_list]]')),
      '#require' => TRUE,
    ];
    $form['unpublish_mail_admin']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['micro_site'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
    ];

    $form['unpublish_mail_micro_site_admin'] = [
      '#type' => 'fieldset',
      '#title' => t('Unpublish Mail to Micro Site Admin'),
      '#collapsible' => TRUE
    ];

    $form['unpublish_mail_micro_site_admin']['site_admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.subject') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.subject') : 'The site [micro_site:name] has been unpublished')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['unpublish_mail_micro_site_admin']['site_admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.message') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.message') : 'Without action on your part, the site [micro_site:name] was automatically put out of service.
To restart your site, you must contact your administrator:
(administrator\'s details)')),
      '#require' => TRUE,
    ];
    $form['unpublish_mail_micro_site_admin']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['micro_site', 'date'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
    ];


    $form['notification_mail'] = [
      '#type' => 'fieldset',
      '#title' => t('Notification Mail at 8 days and 30 days'),
      '#collapsible' => TRUE
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
    $this->config('micro_scheduler.settings')
      ->set('default_life_time', $form_state->getValue('default_life_time'))
      ->set('unpublish_mail_admin.admin_roles', $form_state->getValue('admin_roles'))
      ->set('unpublish_mail_admin.subject', $form_state->getValue('admin_mail_subject'))
      ->set('unpublish_mail_admin.message', $form_state->getValue('admin_mail_message'))
      ->set('unpublish_mail_micro_site_admin.subject', $form_state->getValue('site_admin_mail_subject'))
      ->set('unpublish_mail_micro_site_admin.message', $form_state->getValue('site_admin_mail_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
