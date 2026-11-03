<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Recherche d'une page personnelle par nom, avec redirection.
 */
final class SearchPagePersoForm extends FormBase {

  public function getFormId(): string {
    return 'up1_pages_search_page_perso';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['search_person'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search a person'),
      '#placeholder' => $this->t('Firstname, lastname'),
      '#autocomplete_route_name' => 'up1_pages.autocomplete',
      '#attributes' => [
        'class' => ['pages-persos-autocomplete'],
      ],
      '#description' => $this->t("Enter the person's first or last name and select from the list. You will then be automatically redirected to the personal page."),
    ];
    $form['#attached']['library'][] = 'up1_pages/autocomplete_redirect';

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => ['hide'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $nid = EntityAutocomplete::extractEntityIdFromAutocompleteInput((string) $form_state->getValue('search_person'));
    if ($nid) {
      $form_state->setRedirectUrl(Url::fromRoute('entity.node.canonical', ['node' => $nid]));
    }
  }

}
