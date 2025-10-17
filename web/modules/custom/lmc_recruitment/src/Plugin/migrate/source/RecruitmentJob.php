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
      // Load current entity (lastest revision)
      $nid = $row->getIdMap()['destid1'];
      $vid = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getLatestRevisionId($nid);
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($vid);
      if ($node->field_job_unsync->value === "1") {
        return FALSE;
      }
      $node_last_time = $node->getChangedTime();
      $beetween_last_time = $row->getSourceProperty('last_modification_date');
      $beetween_last_time = strtotime($beetween_last_time);
      if ($beetween_last_time < $node_last_time) {
        RETURN FALSE;
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

    // Do not rely on Lat/Lon provided by XML file.
    $center = $row->getSourceProperty('cf_sites');
    switch ($center) {
      case 'Centre Panthéon':
        $row->setSourceProperty('latitude', '48.847024300712896');
        $row->setSourceProperty('longitude', '2.344046699999996');
        break;
      case 'Centre Sorbonne':
        $row->setSourceProperty('latitude', '48.84885679243756');
        $row->setSourceProperty('longitude', '2.3432904982108083');
        break;
      case 'Centre Saint-Charles':
        $row->setSourceProperty('latitude', '48.842034');
        $row->setSourceProperty('longitude', '2.2804506');
        break;
      case 'Centre Soufflot':
        $row->setSourceProperty('latitude', '48.8469194');
        $row->setSourceProperty('longitude', '2.3421448');
        break;
      case 'Centre Lhomond':
        $row->setSourceProperty('latitude', '48.842366');
        $row->setSourceProperty('longitude', '2.347564');
        break;
      case 'Centre Sainte-Barbe':
        $row->setSourceProperty('latitude', '48.847416514360475');
        $row->setSourceProperty('longitude', '2.3466646533224416');
        break;
      case 'Publication de la Sorbonne':
        $row->setSourceProperty('latitude', '48.845358398059936');
        $row->setSourceProperty('longitude', '2.3425018936385644');
        break;
      case 'Centre Pierre-Mendès-France':
        $row->setSourceProperty('latitude', '48.8269527');
        $row->setSourceProperty('longitude', '2.3648285');
        break;
      case 'Campus Royal':
        $row->setSourceProperty('latitude', '48.837303');
        $row->setSourceProperty('longitude', '2.3455667');
        break;
      case 'Campus Condorcet':
        $row->setSourceProperty('latitude', '48.908212');
        $row->setSourceProperty('longitude', '2.364712');
        break;
      case 'Centre Broca':
        $row->setSourceProperty('latitude', '48.8383965');
        $row->setSourceProperty('longitude', '2.3485557');
        break;
      case 'Maison de la philosophie Marin-Mersenne':
        $row->setSourceProperty('latitude', '48.8527955');
        $row->setSourceProperty('longitude', '2.3352474');
        break;
      case "Maison des Sciences de l'Homme Mondes":
        $row->setSourceProperty('latitude', '48.903179361491425');
        $row->setSourceProperty('longitude', '2.2119106096799173');
        break;
      case 'Centre Ulm':
        $row->setSourceProperty('latitude', '48.8453307');
        $row->setSourceProperty('longitude', '2.3458013');
        break;
      case 'Centre Michelet':
        $row->setSourceProperty('latitude', '48.8424675');
        $row->setSourceProperty('longitude', '2.3360459');
        break;
      case 'Centre de Bourg-la-Reine':
        $row->setSourceProperty('latitude', '48.7785429');
        $row->setSourceProperty('longitude', '2.3182475');
        break;
      case 'Centre de Nogent-sur-Marne':
        $row->setSourceProperty('latitude', '48.83445567847155');
        $row->setSourceProperty('longitude', '2.4658287962548275');
        break;
      case 'Carré Colbert (INHA)':
        $row->setSourceProperty('latitude', '48.8665021');
        $row->setSourceProperty('longitude', '2.3388301');
        break;
      case 'Institut de Géographie':
        $row->setSourceProperty('latitude', '48.8448147305746');
        $row->setSourceProperty('longitude', '2.342448253005949');
        break;
      case 'Maison des sciences économiques':
        $row->setSourceProperty('latitude', '48.835458488823384');
        $row->setSourceProperty('longitude', '2.3581823854389317');
        break;
      case 'Maison internationale':
        $row->setSourceProperty('latitude', '48.8351006');
        $row->setSourceProperty('longitude', '2.3441017');
        break;
      case 'Maison des Sciences de la Communication':
        $row->setSourceProperty('latitude', '48.8354425');
        $row->setSourceProperty('longitude', '2.3502799');
        break;
      case 'Centre Malher':
        $row->setSourceProperty('latitude', '48.855951');
        $row->setSourceProperty('longitude', '2.3611887');
        break;
      case 'Centre Censier':
        $row->setSourceProperty('latitude', '48.8399787');
        $row->setSourceProperty('longitude', '2.3542965');
        break;
      case 'Campus La Chapelle':
        $row->setSourceProperty('latitude', '48.899215742826016');
        $row->setSourceProperty('longitude', '2.359420794844716');
        break;
      case 'Centre Cujas':
        $row->setSourceProperty('latitude', '48.84739925097855');
        $row->setSourceProperty('longitude', '2.3454546999999875');
        break;
      default:
        // Do nothing. It will take the Lat/Lon from the XML.
    }

    return parent::prepareRow($row);
  }

}
