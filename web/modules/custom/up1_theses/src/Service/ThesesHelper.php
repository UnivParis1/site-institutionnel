<?php

namespace Drupal\up1_theses\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ThesesHelper {

  /**
   * The theses service.
   *
   * @var \Drupal\up1_theses\Service\ThesesService
   */
  protected $thesesService;

  use StringTranslationTrait;

  /**
   * Constructs a MyClass object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\up1_theses\Service\ThesesService $theses_service
   */
  public function __construct(ThesesService $theses_service) {
    $this->thesesService = $theses_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theses.service')
    );
  }
  /**
   * Transform Json data to array.
   *
   * return void
   */
  public function transformJsonDataToArray() {
    try {
      $json = file_get_contents($this->thesesService->getWebServiceUrl());
      $dataArray = json_decode($json, TRUE);
      if (!empty($dataArray)) {
        return $dataArray;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('up1_theses', $e);
    }

  }

  /**
   * Create nodes event from json.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function formatDataFromJson() {
    $taxonomyEntity = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $termType = $taxonomyEntity->loadByProperties([
      'name' => 'Soutenance de thèse'
    ]);
    $type = reset($termType);
    $termCategory = $taxonomyEntity->loadByProperties([
      'name' => 'Recherche'
    ]);
    $category = reset($termCategory);
    $nodes = [];
    $data = $this->transformJsonDataToArray();
    $uid = $this->thesesService->getWebmestreUid();

    foreach ($data as $key => $these) {
      $ok = FALSE;
      $existingTheses = $this->thesesService->getExistingTheses();
      $cod_ths = $these['COD_THS'];

      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          if ($cod_ths == $node['cod_ths']) {
            $ok = TRUE;
            break;
          }
        }
      }

      if (!$ok && !in_array($cod_ths, $existingTheses)
        && !empty($these['LIB_THS']) && !empty($these['DAT_SOU_THS']) &&
        !empty($these['HH_SOU_THS']) && !empty($these['LIB_CMT_LEU_SOU_THS']) &&
        !empty($these['LIB_PR1_IND']) && !empty($these['LIB_NOM_PAT_IND']) &&
        !empty($these['PNOMDIR']) && !empty($these['NOMPDIR']) ) {

        $address = $this->formatAddress($these['LIB_CMT_LEU_SOU_THS']);
        $thesard = $this->t('By') . " " . ucfirst($these['LIB_PR1_IND'])
          . " " . ucfirst($these['LIB_NOM_PAT_IND']);
        $dir_ths = $this->t('Directeur de thèse : ') . " "
          . ucfirst($these['PNOMDIR']) . " " . ucfirst($these['NOMPDIR']);

        $nodes[] = [
          'cod_ths' => $cod_ths,
          'title' => $these['LIB_THS'],
          'type' => 'event',
          'langcode' => 'fr',
          'uid' => $uid,
          'status' => 1,
          'field_subtitle' => $thesard,
          'body' => [
            'value' => $dir_ths,
            'format' => 'full_html',
          ],
          'field_event_address' => $these['LIB_CMT_LEU_SOU_THS'],
          'field_event_date' => [[
            'value' => $this->formatDate($these['DAT_SOU_THS'],
              $these['HH_SOU_THS'], $these['MM_SOU_THS']),
            'end_value' => $this->formatDate($these['DAT_SOU_THS'],
              ($these['HH_SOU_THS']+2), $these['MM_SOU_THS'])
          ]],
          'field_address_map' => [
            [
              'lat' => isset($address['lat'])? $address['lat'] : 0,
              'lng' => isset($address['lon'])? $address['lon'] : 0,
            ]
          ],
          'field_event_type' => $type,
          'field_categories' => $category,
          'cod_edo' => $these['COD_EDO'],
        ];
      }
    }
    return $nodes;

  }

  /**
   * Gets latitude and longitude from the address field of the web service.
   *
   * @param string $address
   * @param bool $french
   *
   * @return array The array of json data
   */
  public function getLatLongFromAddress($address, $french = TRUE) {
    $latLon = [
      'lat' => '',
      'lon' => '',
    ];

    if ($french) {
      $baseUrl = $this->thesesService->getFrenchAddressUrl();
      $url = "$baseUrl$address&limit=1";

      $response = \Drupal::httpClient()->get($url, [
        'headers' => ['Accept' => 'text/plain']
      ]);
      $data = $response->getBody();
      $json = json_decode($data, TRUE);

      if ($json) {
        $coord = $json['features'][0]['geometry']['coordinates'];
        $latLon['lat'] = $coord[1];
        $latLon['lon'] = $coord[0];
      }
    }
    else {
      $baseUrl = $this->thesesService->getWorldwideAddressUrl();
      $url = "$baseUrl$address&limit=1";

      $response = \Drupal::httpClient()->get($url, [
        'headers' => ['Accept' => 'text/plain']
      ]);
      $data = $response->getBody();
      $json = json_decode($data, TRUE);

      if ($json) {
        $latLon['lat'] = $json[0]['lat'];
        $latLon['lon'] = $json[0]['lon'];
      }
    }

    return $latLon;

  }

  /**
   * Obtains latitude and longitude from the address field of the web service.
   * @param string $address
   *
   * @return array $addressData
   */
  public function formatAddress($address) {
    $formattedAddress = $address;
    $french = FALSE;
    if(preg_match('/Paris/i', $address)) {
      $french = TRUE;
      preg_match('/^\D*(?=\d)/', $address, $m);
      if (isset($m[0])) {
        $formattedAddress = substr($address, strlen($m[0]));
      }
      if (!empty($formattedAddress)) {
        if (preg_match('/Paris(.*)?/i', $formattedAddress)) {
          $formattedAddress = preg_replace('/Paris(.*)?/i', '$2 Paris', $formattedAddress);
        }
        $formattedAddress = preg_replace("/(\s-\s)|(\s\s)|(\s)/i", "+", $formattedAddress);
      }
    }

    $addressData = $this->getLatLongFromAddress($formattedAddress, $french);
    return $addressData;

  }

  /**
   * Get Drupal formatted date from date field of the web service.
   *
   * @param string $date
   * @param string $hours
   * @param string $minutes
   *
   * @return string $formattedDate
   */
  public function formatDate($date, $hours, $minutes) {
    $fullDate = $date . " " . ($hours - 2).":";
    $fullDate .= ($minutes == 0)? "00" : $minutes;
    $newDate = \DateTime::createFromFormat('d/m/y H:i', $fullDate);
    $formattedDate = "";
    if ($newDate) {
      $formattedDate = \Drupal::service('date.formatter')
        ->format($newDate->getTimestamp(), 'custom', 'Y-m-dTH:i:s');
      $formattedDate = preg_replace('/CEST/i', 'T', $formattedDate);
    }

    return $formattedDate;

  }
}