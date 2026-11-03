<?php

declare(strict_types=1);

namespace Drupal\up1_pages;

/**
 * Comptes des pages personnelles, mis en forme pour Drush et les réglages.
 */
final class StatusReport {

  private const PLAN_LABELS = [
    'account' => 'Comptes à créer',
    'role' => 'Rôles à ajouter',
    'page' => 'Pages à créer',
    'publish' => 'Pages à republier',
    'title' => 'Titres à mettre à jour',
    'gone' => 'Désactivations (personnes sorties de wsgroups)',
  ];

  /**
   * Sections du rapport.
   *
   * @param array $sync
   *   PagePersoSyncManager::getStatistics().
   * @param array $hal
   *   HalPublications::statistics().
   * @param callable $format_date
   *   Timestamp → date lisible.
   *
   * @return array
   *   Titre de section => lignes [libellé, valeur].
   */
  public static function sections(array $sync, array $hal, callable $format_date): array {
    $sections = [];

    $last = $sync['last_result'];
    $sections['Dernière synchro'] = $last
      ? [
        ['Date', $format_date($last['date'])],
        ['Résultat', $last['status'] === 'ok' ? 'Effectuée' : 'Annulée'],
        ['Détail', $last['message']],
      ]
      : [['Résultat', 'Jamais lancée']];

    $stats = $sync['statistics'];
    if ($stats) {
      $date = $format_date($stats['date']);
      $ws = $stats['wsgroups'];
      $sections["wsgroups (au $date)"] = [
        ['Enseignants-chercheurs ayant droit à une page', $ws['faculty']],
        ['Doctorants ayant droit à une page', $ws['student']],
        ['Dont présents dans les deux listes', $ws['both']],
        ['Total de personnes distinctes', $ws['total']],
      ];

      $drupal = $stats['drupal'];
      $sections["Drupal (au $date)"] = [
        ['Comptes avec le rôle enseignant_doctorant', $drupal['role']],
        ['Pages personnelles publiées', $drupal['published']],
        ['… dont enseignants-chercheurs', $drupal['published_faculty']],
        ['… dont doctorants', $drupal['published_student']],
        ['… dont présents dans les deux listes', $drupal['published_both']],
        ['… dont absents de wsgroups', $drupal['published_absent']],
        ['Pages personnelles dépubliées', $drupal['unpublished']],
        ['Personnes ayant plusieurs pages (non traitées)', $drupal['duplicates']],
      ];

      $rows = [];
      foreach (self::PLAN_LABELS as $reason => $label) {
        $rows[] = [$label, $stats['plan'][$reason] ?? 0];
      }
      $sections["Écarts trouvés (au $date)"] = $rows;
    }
    else {
      $sections['Comptes'] = [['', 'Aucune comparaison réussie pour le moment.']];
    }

    $sections['Publications HAL'] = [
      ['Pages avec une recherche HAL', $hal['with_search']],
      ['Encarts enregistrés', $hal['stored']],
      ['… dont sans publication', $hal['empty']],
      ['Pages jamais traitées', $hal['missing']],
      ['Pages en file de rafraîchissement', $hal['queued']],
      ['Dernière mise en file', $hal['last_enqueue'] ? $format_date($hal['last_enqueue']) : 'Jamais'],
    ];

    return $sections;
  }

}
