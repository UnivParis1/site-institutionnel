<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a UP1 Pages Persos form.
 */
final class SearchPagePersoForm extends FormBase {

  /**
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;


  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
  }

  public static function create(ContainerInterface $container) {

    return new static ($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'up1_pages_persos_search_page_perso';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['search_person'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search a person'),
      '#placeholder' => $this->t('Firstname, lastname'),
      '#autocomplete_route_name' => 'up1_pages_persos.autocomplete.person',
      '#attributes' => [
        'class' => ['pages-persos-autocomplete'],
      ],
      '#description' => $this->t("Enter the person's first or last name and select from the list. You will then be automatically redirected to the personal page.")
    ];

    // Add module's library
    $form['#attached']['library'][] = 'up1_pages_persos/autocomplete_redirect';

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => ['hide']
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_perso = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('search_person'));

    if ($page_perso) {
      // Generate the URL and redirect
      $url = Url::fromRoute('entity.node.canonical',
        [
          'node' => $page_perso
        ]);
      $form_state->setRedirectUrl($url);
    }
  }

}
