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
          && !empty($these['LIB_THS']) && !empty($these['DAT_SOU_THS']) && !empty($these['LIB_CMT_LEU_SOU_THS']) &&
          !empty($these['LIB_NOM_IND']) && !empty($these['NOMDIR'])) {
          $codedo = "ED" . $these['COD_EDO'];

          $existingTheses = $this->thesesService->getExistingTheses();

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
          \Drupal::logger('up1_theses')->info(print_r($these['DAT_SOU_THS'], 1));
          $nodes[] = [
            'cod_ths' => $cod_ths,
            'title' => $these['LIB_THS'],
            'type' => 'viva',
            'langcode' => 'fr',
            'uid' => $uid,
            'status' => 1,
            'field_subtitle' => $these['LIB_NOM_IND'],
            'field_thesis_supervisor' => $these['NOMDIR'],
            'field_co_director' => $these['NOMCODIR'],
            'field_board' => $these['NOMJUR'],
            'field_event_address' => $these['LIB_CMT_LEU_SOU_THS'],
            'field_viva_date' => $these['DAT_SOU_THS'],
            'field_hdr' => ($these['TEM_DOC_HDR'] == "HDR") ? 1 : 0,
            'field_categories' => $category,
            'cod_edo' => $codedo,
            'lib_edo' => $libedo,
          ];
        }
      }
      \Drupal::logger('up1_theses')->info(print_r($nodes, 1));
    }

    return $nodes;
  }
}
