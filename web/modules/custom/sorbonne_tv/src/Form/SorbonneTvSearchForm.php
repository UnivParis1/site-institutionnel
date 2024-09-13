<?php

namespace Drupal\sorbonne_tv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Form controller for the shareholders_plan entity edit forms.
 *
 * @ingroup shareholders_plan
 */
class SorbonneTvSearchForm extends FormBase {

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sorbonne_tv_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [
      '#attributes' => [
        'class' => ['sorbonne-tv-search-form']
      ]
    ];

    $searchQuery = \Drupal::request()->query->get('search_api_fulltext');
    $form['search'] = [
      '#type' => 'textfield',
      //'#title' => $this->t('Search'),
      '#default_value' => $searchQuery,
      '#attributes' => array(
        'placeholder' => $this->t('Search'),
      ),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $redirectToUrl = FALSE;
    $search = $form_state->getValue('search');

    $url_options = [
      'query' => ['search_api_fulltext' => $search],
    ];
    $redirectToUrl = Url::fromRoute('view.sorbonne_tv_search.stv_search_page', [], $url_options);

    $form_state->setRedirectUrl($redirectToUrl);
  }
}

