<?php

namespace Drupal\cmis_extensions\Form;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CmisExtensionsForm extends ConfigFormBase {
  public function getFormId() {
    return 'cmis_extensions_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('cmis_extensions.settings');

    $form['user'] = [
      '#type' => 'details',
      '#title' => $this->t('Nuxeo user'),
      '#open' => TRUE
    ];

    $form['user']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nuxeo user'),
      '#default_value' => $config->get('user'),
      '#required' => true
    ];

    $form['user']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Nuxeo password')
    ];

    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache'),
      '#open' => TRUE
    ];

    $form['cache']['inspect'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => $this->t('Inspect cache'),
      '#attributes' => [
        'href' => Url::fromRoute('cmis_extensions.cache_inspect')->toString(),
        'target' => '_blank',
        'class' => 'button'
      ]
    ];

    $form['cache']['rebuild'] = [
      '#type' => 'button',
      '#value' => $this->t('Rebuild cache'),
      '#ajax' => [
        'callback' => [$this, 'rebuildCache']
      ]
    ];

    $form['cache']['rebuild_message'] = [
      '#id' => 'rebuild-message',
      '#type' => 'item'
    ];

    $form['cache']['details'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('To display document\'s parent folder in an acceptable time, paths need to be fetched eagerly and kept in cache.'),
      '#suffix' => '</p>'
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cmis_extensions.settings');

    $config->set('user', $form_state->getValue('name'));
    $config->set('password', $form_state->getValue('password'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  public function rebuildCache() {
    $service = \Drupal::service('cmis_extensions.nuxeo_service');
    $service->cacheAllPaths();

    $item = [
      '#type' => 'item',
      '#markup' => $this->t('Cache successfully rebuilt'),
    ];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#rebuild-message', $item));

    return $response;
  }

  protected function getEditableConfigNames() {
    return [
      'cmis_extensions.settings',
    ];
  }
}
