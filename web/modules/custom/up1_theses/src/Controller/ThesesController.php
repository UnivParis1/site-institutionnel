<?php

namespace Drupal\up1_theses\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\up1_theses\Service\ThesesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

class ThesesController extends ControllerBase {

  /**
   * The theses helper used to get settings from.
   *
   * @var \Drupal\up1_theses\Service\ThesesHelper
   */
  protected $thesesHelper;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Array that contains all formated data.
   * @var $theses
   */
  protected $theses;

  /**
   * Constructor.
   *
   * @param \Drupal\up1_theses\Service\ThesesHelper $theses_helper
   *   The ThesesHelper to get data.
   */
  public function __construct(ThesesHelper $theses_helper) {
    $this->thesesHelper = $theses_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theses.helper')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function listeTheses() {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(21);
    return [
      '#markup' => '<div>' . print_r($this->createNodesFromData(), 1) . '</div>',
     //<div>' . print_r($node, 1) . '</div>',
    //'#markup' => '<div>' . print_r($node, 1) . '</div>',
    ];

  }

  /**
   * Prepares data to be inserted as node event.
   *
   * @return array $this->theses
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function prepareDataBeforeImport() {
    $taxonomyEntity = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $term = $taxonomyEntity->loadByProperties(['name' => 'Soutenance de thèse']);
    $soutenance = reset($term);
    $term = $taxonomyEntity->loadByProperties(['name' => 'Recherche']);
    $category = reset($term);
    $data = $this->thesesHelper->transformJsonDataToArray();

    $existingTheses = $this->thesesHelper->getExistingTheses();

    \Drupal::logger('up1_theses')->info(print_r($existingTheses, 1));

    foreach ($data as $key => $these) {
      if (!in_array($these['COD_THS'], $existingTheses)  && !isset($this->theses[$these['COD_THS']]) &&
        !empty($these['LIB_THS']) && !empty($these['DAT_SOU_THS']) &&
        !empty($these['HH_SOU_THS']) && !empty($these['LIB_CMT_LEU_SOU_THS']) &&
        !empty($these['LIB_PR1_IND']) && !empty($these['LIB_NOM_PAT_IND']) &&
        !empty($these['PNOMDIR']) && !empty($these['NOMPDIR']) ) {

        $address = $this->formatAddress($these['LIB_CMT_LEU_SOU_THS']);

        $this->theses[$these['COD_THS']] = [
          'title' => $these['LIB_THS'],
          'field_subtitle' => $this->t('By') . " " .
            ucfirst($these['LIB_PR1_IND']) . " " . ucfirst($these['LIB_NOM_PAT_IND']),
          'field_event_address' => $these['LIB_CMT_LEU_SOU_THS'],
          'event_start_date' => $this->formatDate($these['DAT_SOU_THS'], $these['HH_SOU_THS'],
            $these['MM_SOU_THS']),
          'event_end_date' => $this->formatDate($these['DAT_SOU_THS'], ($these['HH_SOU_THS']+2),
            $these['MM_SOU_THS']),
          'address_latitude' => isset($address['lat'])? $address['lat'] : 0,
          'address_longitude' => isset($address['lon'])? $address['lon'] : 0,
          'field_event_type' => $soutenance->get('tid')->value,
          'field_categories' => $category->get('tid')->value,
          'body' => $this->t('Directeur de thèse : ') . " " . ucfirst($these['PNOMDIR'])
            . " " . ucfirst($these['NOMPDIR']),
        ];
      }

    }
    return $this->theses;

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createNodesFromData() {
    $theses = $this->prepareDataBeforeImport();
    if (!empty($theses) ) {
      foreach ($theses as $key => $these) {
        $newNode = [
          'type' => 'event',
          'langcode' => 'fr',
          'uid' => '1',
          'status' => 1,
          'title' => $these['title'],
          'field_subtitle' => $these['field_subtitle'],
          'body' => $these['body'],
          'field_event_address' => $these['field_event_address'],
          'field_event_date' => [
            [
              'value' => $these['event_start_date'],
              'end_value' => $these['event_end_date'],
            ]
          ],
        ];
        if (!empty($these['address_latitude']) && !empty($these['address_longitude'])) {
          $newNode['field_address_map'] = [
            [
              'lat' => $these['address_latitude'],
              'lng' => $these['address_longitude'],
            ]
          ];
        }

        $node = Node::create($newNode);
        $node->set('field_event_type', [$these['field_event_type']]);
        $node->set('field_categories', [$these['field_categories']]);
        $node->save();
        if ($node) {
          \Drupal::database()->merge('up1_theses_import')
            ->keys([
              'cod_ths' => $key,
              'nid' => $node->id(),
              'created' => $node->getCreatedTime(),
            ])
            ->execute();
        }
      }
    }

  }

  public function formatAddress($address) {

    $formattedAddress = $address;
    if(preg_match('/Paris/i', $address)) {

      preg_match('/^\D*(?=\d)/', $address, $m);
      if (isset($m[0])) {
        $formattedAddress = substr($address, strlen($m[0]));
      }
      if (!empty($formattedAddress)) {
        if (preg_match('/Paris(.*)?/i', $formattedAddress)) {
          $formattedAddress = preg_replace('/Paris(.*)?/i', '$2 Paris', $formattedAddress);
        }
        $formattedAddress = str_replace("  ", " ", $formattedAddress);
        $formattedAddress = str_replace("-", "", $formattedAddress);
        $formattedAddress = str_replace(",", "", $formattedAddress);
        $formattedAddress = str_replace(" ", "+", $formattedAddress);
        $formattedAddress = str_replace("++", "+", $formattedAddress);
      }
    }

    $addressData = $this->thesesHelper->getLatLongFromAddress($formattedAddress);
    return $addressData;

  }

  /**
   * @param string $date
   * @param string $heures
   * @param string $minutes
   *
   * @return string $formattedDate
   */
  public function formatDate($date, $heures, $minutes) {
    $fullDate = $date . " " . ($heures-2).":";
    $fullDate .= ($minutes == 0)? "00" : $minutes;

    $newDate = \DateTime::createFromFormat('d/m/y H:i', $fullDate);

    $formattedDate = \Drupal::service('date.formatter')
      ->format($newDate->getTimestamp(), 'custom', 'Y-m-dTH:i:s');
    \Drupal::logger('up1_theses')->info(print_r($formattedDate, 1));
    $formattedDate = preg_replace('/CEST/i', 'T', $formattedDate);
    \Drupal::logger('up1_theses')->info(print_r($formattedDate, 1));

    return $formattedDate;
  }
}