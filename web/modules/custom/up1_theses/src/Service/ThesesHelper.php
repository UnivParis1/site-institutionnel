<?php

namespace Drupal\up1_theses\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;


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

    return FALSE;
  }

  /**
   * Create nodes viva from json.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function formatDataFromJson() {
    $nodes = [];
    $data = $this->transformJsonDataToArray();

    if (!empty($data)) {
      $taxonomyEntity = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term');
      $termCategory = $taxonomyEntity->loadByProperties([
        'name' => 'Recherche'
      ]);
      $category = reset($termCategory);
      $uid = $this->thesesService->getWebmestreUid();
      foreach ($data as $key => $these) {
        $ok = FALSE;
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
          !empty($these['PNOMDIR']) && !empty($these['NOMPDIR'])) {
          $codedo = "ED" . $these['COD_EDO'];

          $existingTheses = $this->thesesService->getExistingTheses();
          $address = $this->formatAddress($these['LIB_CMT_LEU_SOU_THS']);
          $thesard = ucfirst(strtolower($these['LIB_PR1_IND']))
            . " " . ucfirst(strtolower($these['LIB_NOM_PAT_IND']));
          $dir_ths = ucfirst($these['PNOMDIR']) . " " . ucfirst($these['NOMPDIR']);

          if (preg_match('/^[aeiouyh]/i', $these['LIB_EDO']) ||
            preg_match('/^[É]/i', $these['LIB_EDO']) ||
            preg_match('/^[é]/i', $these['LIB_EDO'])) {
            $libedo = "École doctorale d'" . $these['LIB_EDO'];
          }
          elseif (preg_match('/(de)/i', $these['LIB_EDO'])) {
            $libedo = "École doctorale " . $these['LIB_EDO'];
          }
          else {
            $libedo = "École doctorale de " . $these['LIB_EDO'];
          }
          \Drupal::logger('up1_theses')->info(print_r($these['LIB_THS'] . " " . $these['DAT_SOU_THS'] . " " . $these['HH_SOU_THS'] . ":" . $these['MM_SOU_THS'],1));

          $nodes[] = [
            'cod_ths' => $cod_ths,
            'title' => $these['LIB_THS'],
            'type' => 'viva',
            'langcode' => 'fr',
            'uid' => $uid,
            'status' => 1,
            'field_subtitle' => $thesard,
            'field_thesis_supervisor' => $dir_ths,
            'field_event_address' => $these['LIB_CMT_LEU_SOU_THS'],
            'field_event_date' => [
              [
                'value' => $this->formatDate($these['DAT_SOU_THS'],
                  $these['HH_SOU_THS'], $these['MM_SOU_THS']),
                'end_value' => $this->formatDate($these['DAT_SOU_THS'],
                  ($these['HH_SOU_THS'] + 4), $these['MM_SOU_THS'])
              ]
            ],
            'field_address_map' => [
              [
                'lat' => isset($address['lat']) ? $address['lat'] : 0,
                'lng' => isset($address['lon']) ? $address['lon'] : 0,
              ]
            ],
            'field_ecole_doctorale' => $this->getEcoleDoctoraleTerm("ED" . $these['COD_EDO'], $these['LIB_EDO']) ?
              $this->getEcoleDoctoraleTerm("ED" . $these['COD_EDO'], $these['LIB_EDO']) : NULL,
            'field_categories' => $category,
            'cod_edo' => $codedo,
            'lib_edo' => $libedo,
          ];
        }
      }
    }
    return $nodes;

  }

  public function getEcoleDoctoraleTerm($code, $libelle) {
    $vocabulary_name = "ecoles_doctorales";

    $taxonomyEntity = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $termEd = $taxonomyEntity->loadByProperties([
      'name' => $libelle,
      'vid' => $vocabulary_name
    ]);
    if (!empty($termEd)) {
      $term = reset($termEd);
    }
    else {
      $term = Term::create([
        'name' => $libelle,
        'vid' => $vocabulary_name,
        'field_code_edo' => $code,
      ]);
      $term->save();
    }
    return $term;
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
    if ($french) {
      $baseUrl = $this->thesesService->getFrenchAddressUrl();
      $url = "$baseUrl$address&limit=1";

      $response = \Drupal::httpClient()->get($url, [
        'headers' => ['Accept' => 'text/plain']
      ]);
      $data = $response->getBody();
      $json = json_decode($data, TRUE);

      if (isset($json['features'][0]['geometry']['coordinates'])) {
        $coord = $json['features'][0]['geometry']['coordinates'];

        $latLon = [
          'lat' => $coord[1],
          'lon' => $coord[0],
        ];
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

      if (isset($json[0]['lat']) &&isset($json[0]['lon'])) {
        $latLon = [
          'lat' => $json[0]['lat'],
          'lon' => $json[0]['lon'],
        ];
      }
    }

    if (!isset($latLon['lat']) && !isset($latLon['lon'])) {
      $latLon  = [
        'lat' => "48.8468",
        'lon' => "2.344879"
      ];
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
    if (preg_match('/centre Panthéon/i', $address) && !preg_match('/Paris/i', $address)) {
      $formattedAddress = "12+place+du+Pantheon+75005+Paris";
      $french = TRUE;
    }
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
    $timestamp = '';
    $date_apogee = explode('/', $date);
    $mois = $date_apogee[1];
    $date_apogee[1] = $date_apogee[0];
    $date_apogee[0] = $mois;
    $date_apogee[2] = '20'.$date_apogee[2];
    $minutes = ($minutes == 0 || $minutes == "")? "00" : $minutes;

    $timestamp = strtotime(implode('/', $date_apogee) . "$hours:$minutes:00", date_default_timezone_set("Europe/Paris"));

    $formatted_date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y-m-d H:i', "Europe/Paris");
    \Drupal::logger('up1_theses')->info(print_r("timestamp : $timestamp, Formatted date : $formatted_date", 1));


    return $formatted_date;
  }
}
