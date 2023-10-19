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
    $form['homepage_search'] = [
      '#type' => 'search',
      '#title' => t('Search'),
      '#attributes' => [
        'placeholder' => t('Search')
      ],
      '#default_value' => isset($value)?? '',
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('OK'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $homepage_search = $form_state->getValue('homepage_search');
    $config = \Drupal::config('up1_keywords.settings');

    $search_page_url = $config->get('search_page_url');

    $form_state->setRedirectUrl(Url::fromUri( "internal:$search_page_url", ['query' => ['text' => $homepage_search]]));

  }
}
