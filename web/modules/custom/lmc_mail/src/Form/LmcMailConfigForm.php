<?php

namespace Drupal\lmc_mail\Form;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LmcMailConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lmc_mail_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form   = parent::buildForm($form, $form_state);
    $config = $this->config('lmc_mail.settings');
    $url    = Url::fromRoute('view.lmc_mail_management.lmc_mail_management_view_page');
    $link   = Link::fromTextAndUrl(t('Gérer les templates d\'emails'), $url);

    $form['link'] = [
      '#markup' => '<h2>' . $link->toString() . '</h2>',
      '#prefix' => '<br />'
    ];

    /*$url    = Url::fromUri($base_url . '/admin/structure/types/manage/email/fields/node.email.field_email_key/storage');
    $link   = Link::fromTextAndUrl(t('Gérer les clés des templates d\'emails'), $url);

    $form['link2'] = array(
      '#markup' => '<h2>' . $link->toString() . '</h2>',
      '#suffix' => '<br />'
    );*/

    $form['disclamer'] = [
      '#type'  => 'fieldset',
      '#title' => t('Disclamer text'),
      '#tree'  => TRUE,
    ];

    $disclamer_txts_conf = $config->get('lmc_mail.settings.disclamer');

    $bottom_mail_disclamer_default = 'This message is being sent from a law firm and may contain confidential or privileged information. If you are not the intended recipient, please advise the sender immediately and delete this message and any attachments without retaining a copy.';

    $bottom_mail_disclamer_default_trad = t('This message is being sent from a law firm and may contain confidential or privileged information. If you are not the intended recipient, please advise the sender immediately and delete this message and any attachments without retaining a copy.');

    $form['disclamer']['bottom_mail_disclamer'] = [
      '#type'  => 'fieldset',
      '#type' => 'text_format',
      '#default_value' => isset($disclamer_txts_conf['bottom_mail_disclamer']['value']) ? $disclamer_txts_conf['bottom_mail_disclamer']['value'] : $bottom_mail_disclamer_default,
      '#description' => '<p><b>Merci de rentrer une valeur en Anglais</b></p>'
        .'<p>Valeur par défaut (traduite) : '. ( isset($disclamer_txts_conf['bottom_mail_disclamer']['value']) ? t($disclamer_txts_conf['bottom_mail_disclamer']['value']) : $bottom_mail_disclamer_default_trad ) .'</p>',
      '#format' => 'full_html',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lmc_mail.settings');
    $config->set('lmc_mail.settings.disclamer', $form_state->getValue('disclamer'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lmc_mail.settings',
      'lmc_mail.settings.disclamer',
    ];
  }

}
