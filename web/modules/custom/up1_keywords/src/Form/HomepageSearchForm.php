<?php

namespace Drupal\up1_keywords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing;

class HomepageSearchForm extends FormBase {

  public function getFormId() {
    /**
     * {@inheritdoc}
     */
    return 'homepage_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();

    if (preg_match('/recherche/', $current_path)) {
      $value = \Drupal::request()->query->get('text');
    }

    $form['homepage_search'] = [
      '#type' => 'search',
      '#title' => t('Search'),
      '#attributes' => [
        'placeholder' => t('Search')
      ],
      '#default_value' => isset($value)? $value : "",
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('OK'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $homepage_search = $form_state->getValue('homepage_search');
    $config = \Drupal::config('up1_keywords.keywordsconfig');

    $form_state->setRedirectUrl(Url::fromUri($config->get('url_resultat_de_recherche') . $homepage_search));

  }
}
