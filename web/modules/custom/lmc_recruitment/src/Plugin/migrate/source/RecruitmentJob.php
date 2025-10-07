<?php

declare(strict_types=1);

namespace Drupal\lmc_recruitment\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\node\Entity\Node;

/**
 * A URL source plugin, to retrieve category data from a source URI.
 *
 * @MigrateSource(
 *    id = "recruitment_job",
 *    source_module = "lmc_recruitment",
 *  )
 */
final class RecruitmentJob extends Url {

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, DataParserPluginManager $parserPluginManager) {
    // Expose XML's URI here and not in the migration's config file.
    // This allows easier alteration. We could get this URI from the settings.php
    // file. This could also be different accross envs.
    //
    // In settings.php or settings.local.php, do the following :
    // $config['lmc_recruitment.settings']['xml_uri'] = 'https://...';
    $config = \Drupal::config('lmc_recruitment.settings');
    $xml_uri = $config->get('xml_uri');
    if ($xml_uri === NULL) {
      $xml_uri = 'https://partners.beetween.com/WeaselWeb/xen/feed/generic?on=custom-connector-6s55g75D9333&opt_custom_connector_uuid=0199519d-64c3-795c-a01d-f29b3a0f2288';
    }

    $configuration['urls'][] = $xml_uri;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parserPluginManager);
  }

  /**
   * {@inheritdoc}
   */
  function prepareRow(Row $row) {
    if (!empty($row->getIdMap()['destid1'])) {
      // Load current entity
      $node = Node::load($row->getIdMap()['destid1']);
      if ($node->field_job_unsync->value === "1") {
        return FALSE;
      }
    }

    $range_min_value = $row->getSourceProperty('rangeMinValue');
    $range_min_value = round((float)$range_min_value);
    $range_max_value = $row->getSourceProperty('rangeMaxValue');
    $range_max_value = round((float)$range_max_value);

    $salary_unit = $row->getSourceProperty('salary_unit');
    $salary_unit = match($salary_unit) {
      'MONTH' => 'mois',
      default => 'an(s)',
    };
    $job_type = $row->getSourceProperty('cf_nature_de_l_emploi');
    $job_type = match($job_type) {
      'Emploi ouvert aux fonctionnaires' => 'Emploi ouvert aux fonctionnaires',
      'Emploi uniquement ouvert aux fonctionnaires' => 'Emploi ouvert aux fonctionnaires',
      'Emploi ouvert aux contractuels' => 'Emploi ouvert aux contractuels',
      'Emploi uniquement ouvert aux contractuels' => 'Emploi ouvert aux contractuels',
      'Emploi ouvert aux fonctionnaires et/ou aux contractuels' => 'Emploi ouvert aux fonctionnaires et/ou aux contractuels',
      'emploi ouvert aux fonctionnaires et/ou aux contractuels' => 'Emploi ouvert aux fonctionnaires et/ou aux contractuels',
      default => '',
    };
    $row->setSourceProperty('cf_nature_de_l_emploi', $job_type);

    $salary_range = $range_min_value . ' à ' . $range_max_value . '€ brut / ' . $salary_unit;
    $row->setSourceProperty('_rangeMinValue', $range_min_value);
    $row->setSourceProperty('_rangeMaxValue', $range_max_value);
    $row->setSourceProperty('_salary_unit', $salary_unit);
    $row->setSourceProperty('_salary_range', $salary_range);

    $experience = $row->getSourceProperty('experience');
    if ($experience > 0) {
      $experience = round((float) $experience) . ' an(s)';
      $row->setSourceProperty('_experience', $experience);
    }
    else {
      $row->setSourceProperty('_experience', NULL);
    }

    $contract = $row->getSourceProperty('contract');

    $contractUnit = $row->getSourceProperty('contractUnit');
    $contractUnit = match($contractUnit) {
      'MONTH' => 'mois',
      default => 'an',
    };

    if (!empty($contract)) {
      $row->setSourceProperty('_contract_duration', $contract . ' ' . $contractUnit);
    }
    else {
      $row->setSourceProperty('_contract_duration', NULL);
    }
    // Set the Moderation State on the source for processing.
    $row->setSourceProperty('moderation_state', 'published');

    $service = $row->getSourceProperty('service');
    if (is_array($service)) {
      $service = implode(' / ', $service);
    }
    $row->setSourceProperty('service', $service);

    // According to Beetween, "You must add _5k_ just before the @."
    $email = $row->getSourceProperty('email');
    $email_suffixed = preg_replace('/@/', '5k@', $email, 1);
    $row->setSourceProperty('email', $email_suffixed);

    return parent::prepareRow($row);
  }

}
