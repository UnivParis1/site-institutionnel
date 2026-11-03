<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Manager\PagePersoManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplétion du bloc « Recherche pages personnelles ».
 */
final class AutocompleteController extends ControllerBase {

  public function __construct(
    private readonly PagePersoManager $pagePersoManager,
    private readonly ComptexManager $comptex,
  ) {
  }

  /**
   * Pages dont le titre contient la saisie, avec statut et affectation.
   *
   * Si wsgroups est indisponible, les résultats sont donnés sans détails.
   */
  public function handleAutocomplete(Request $request): JsonResponse {
    $input = trim((string) $request->query->get('q'));
    if ($input === '') {
      return new JsonResponse([]);
    }

    $results = [];
    foreach ($this->pagePersoManager->autocomplete($input) as $page) {
      $title = $page->getTitle();
      $label = "<span class='userItem'><span class='match'>" . Html::escape($title) . '</span>&nbsp;';
      $details = $this->details($this->comptex->getUserSummary((string) $page->get(PagePersoManager::FIELD_UID_LDAP)->value));
      if ($details !== '') {
        $label .= "<span class='details'>" . Html::escape($details) . '</span>';
      }
      $label .= '</span>';

      $results[] = [
        'value' => $title . ' (' . $page->id() . ')',
        'label' => $label,
        'url' => $page->toUrl()->toString(),
      ];
    }

    return new JsonResponse($results);
  }

  /**
   * « Professeur - UFR 27 - Mathématiques… - 26 ».
   */
  private function details(array $attrs): string {
    if (!$attrs) {
      return '';
    }
    $affectation = implode(', ', (array) ($attrs['supannEntiteAffectation'] ?? []));

    if (($attrs['eduPersonPrimaryAffiliation'] ?? '') === 'student') {
      return trim($this->t('Student') . ' - ' . $affectation, ' -');
    }

    if (!empty($attrs['employeeType'])) {
      $type = (string) ((array) $attrs['employeeType'])[0];
    }
    else {
      $type = match ($attrs['eduPersonPrimaryAffiliation'] ?? '') {
        'emeritus' => (string) $this->t('Emeritus'),
        'researcher' => (string) $this->t('Researcher'),
        default => (string) $this->t('Faculty'),
      };
    }

    $parts = [$type, $affectation];
    if (!empty($attrs['supannActivite'])) {
      $parts[] = Unicode::truncate((string) ((array) $attrs['supannActivite'])[0], 50, FALSE, TRUE);
    }
    if (!empty($attrs['departmentNumber'])) {
      $parts[] = (string) ((array) $attrs['departmentNumber'])[0];
    }

    return implode(' - ', array_filter($parts, static fn($part) => $part !== ''));
  }

}
