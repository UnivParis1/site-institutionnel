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
    $form['notification_timing'] = [
      '#type' => 'checkboxes',
      '#title' => t('Notification Timing'),
      '#options' => [
        8 => t('8 days'),
        15=> t('15 days'),
        30 => t('30 days'),
        60 => t('60 days') ],
      '#default_value' => ($this->config('micro_scheduler.settings')->get('notification_timing') ?
        $this->config('micro_scheduler.settings')->get('notification_timing') : []),
    ];

    $form['mail_to_admin'] = [
      '#type' => 'details',
      '#title' => t('Mail to Admin'),
      '#collapsible' => TRUE,
      '#open' => TRUE
    ];
    $form['mail_to_admin']['admin_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#options' => user_role_names(),
      '#default_value' => ($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.admin_roles') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.admin_roles') : []),
    ];
    $form['mail_to_admin']['unpublish_mail_admin'] = [
      '#type' => 'details',
      '#title' => t('Unpublish Mail'),
      '#collapsible' => TRUE
    ];
    $form['mail_to_admin']['unpublish_mail_admin']['admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.subject') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.subject') : 'The site [micro_site:name] has been unpublished')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['mail_to_admin']['unpublish_mail_admin']['admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_admin.message') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_admin.message') : 'Without action on your part, the site [micro_site:name] was automatically unpublished.
Webmaster (s):
[micro_site:administrator_list]]')),
      '#require' => TRUE,
    ];
    $form['mail_to_admin']['notification_mail_admin'] = [
      '#type' => 'details',
      '#title' => t('Notification Mail'),
      '#collapsible' => TRUE
    ];
    $form['mail_to_admin']['notification_mail_admin']['notification_admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('notification_mail_admin.subject') ?
        $this->config('micro_scheduler.settings')->get('notification_mail_admin.subject') : 'Unpublishing of the site [micro_site:name] in [micro_site:remaining-days] days')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['mail_to_admin']['notification_mail_admin']['notification_admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('notification_mail_admin.message') ?
        $this->config('micro_scheduler.settings')->get('notification_mail_admin.message') : 'Warning, the site [micro_site:name] will be automatically put out of service in [micro_site:remaining-days] days.
Webmaster (s):
[micro_site:administrator-list]')),
      '#require' => TRUE,
    ];
    $form['mail_to_admin']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['micro_site'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
    ];


    $form['mail_to_site_admin'] = [
      '#type' => 'details',
      '#title' => t('Mail to Micro Site Admin'),
      '#collapsible' => TRUE,
      '#open' => TRUE
    ];
    $form['mail_to_site_admin']['unpublish_mail_micro_site_admin'] = [
      '#type' => 'details',
      '#title' => t('Unpublish Mail'),
      '#collapsible' => TRUE
    ];
    $form['mail_to_site_admin']['unpublish_mail_micro_site_admin']['site_admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.subject') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.subject') : 'The site [micro_site:name] has been unpublished')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['mail_to_site_admin']['unpublish_mail_micro_site_admin']['site_admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.message') ?
        $this->config('micro_scheduler.settings')->get('unpublish_mail_micro_site_admin.message') : 'Without action on your part, the site [micro_site:name] was automatically put out of service.
To restart your site, you must contact your administrator:
(administrator\'s details)')),
      '#require' => TRUE,
    ];
    $form['mail_to_site_admin']['notification_mail_micro_site_admin'] = [
      '#type' => 'details',
      '#title' => t('Notification Mail'),
      '#collapsible' => TRUE
    ];
    $form['mail_to_site_admin']['notification_mail_micro_site_admin']['notification_site_admin_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('notification_site_admin_mail.subject') ?
        $this->config('micro_scheduler.settings')->get('notification_site_admin_mail.subject') : 'Unpublishing your site [micro_site:name] in [micro_site:remaining-days] days')),
      '#size' => 150,
      '#maxlenght' => 255,
      '#require' => TRUE,
    ];
    $form['mail_to_site_admin']['notification_mail_micro_site_admin']['notification_site_admin_mail_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message body'),
      '#default_value' => t(($this->config('micro_scheduler.settings')->get('notification_site_admin_mail.message') ?
        $this->config('micro_scheduler.settings')->get('notification_site_admin_mail.message') : 'Warning, without action on your part, your site [micro_site:name] will be automatically disabled in [micro_site:remaining-days] days.
To postpone this release, you must contact your administrator.')),
      '#require' => TRUE,
    ];
    $form['mail_to_site_admin']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['micro_site'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
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
      ->set('notification_timing',  $form_state->getValue('notification_timing'))
      ->set('unpublish_mail_admin.admin_roles', $form_state->getValue('admin_roles'))
      ->set('unpublish_mail_admin.subject', $form_state->getValue('admin_mail_subject'))
      ->set('unpublish_mail_admin.message', $form_state->getValue('admin_mail_message'))
      ->set('notification_mail_admin.subject', $form_state->getValue('notification_admin_mail_subject'))
      ->set('notification_mail_admin.message', $form_state->getValue('notification_admin_mail_message'))
      ->set('unpublish_mail_micro_site_admin.subject', $form_state->getValue('site_admin_mail_subject'))
      ->set('unpublish_mail_micro_site_admin.message', $form_state->getValue('site_admin_mail_message'))
      ->set('notification_site_admin_mail.subject', $form_state->getValue('notification_site_admin_mail_subject'))
      ->set('notification_site_admin_mail.message', $form_state->getValue('notification_site_admin_mail_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
