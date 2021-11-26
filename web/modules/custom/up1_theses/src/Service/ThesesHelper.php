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
            'field_categories' => $category,
            'cod_edo' => $codedo,
            'lib_edo' => $libedo,
          ];
        }
      }
    }
    return $nodes;

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
    $date_apogee = explode('/', $date);
    $mois = $date_apogee[1];
    $date_apogee[1] = $date_apogee[0];
    $date_apogee[0] = $mois;
    $date_apogee[2] = '20'.$date_apogee[2];
    $minutes = ($minutes == 0 || $minutes == "")? "00" : $minutes;

    $timestamp = strtotime(implode('/', $date_apogee) . "$hours:$minutes:00", date_default_timezone_set("Europe/Paris"));

    return gmdate('Y-m-d\TH:i:s', $timestamp);
  }
}
