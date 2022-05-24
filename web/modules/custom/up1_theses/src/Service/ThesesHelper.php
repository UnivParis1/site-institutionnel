<?php

namespace Drupal\up1_theses\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;


class ThesesHelper {

  /**
   * The theses service.
   *
   * @var ThesesService
   */
  protected $thesesService;

  use StringTranslationTrait;

  /**
   * Constructs a MyClass object.
   * @param ThesesService $theses_service
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
   * @return array|mixed
   */
  public function transformJsonDataToArray() {
    $json = file_get_contents($this->thesesService->getWebServiceUrl());
    $dataArray = json_decode($json, TRUE);
    if (!empty($dataArray)) {
      return $dataArray;
    }
    else {
      return [];
    }
  }

  /**
   * Prepare data for node viva from json.
   * @return array
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
        if (!empty($these['LIB_THS'])) {
          $these['LIB_EDO'] = trim($these['LIB_EDO']);

          if (!empty($these['DAT_SOU_THS'])) {
            $date_sout = gmdate('Y-m-d\TH:i:s', strtotime($these['DAT_SOU_THS'],
              date_default_timezone_set("Europe/Paris")));
          }
          else {
            $date_sout = gmdate('Y-m-d\TH:i:s', strtotime(time(),
              date_default_timezone_set("Europe/Paris")));
          }

          $board = "";
          if (!empty($these['NOMJUR'])) {
            $jury = explode(',', $these['NOMJUR']);
            foreach ($jury as $key => $member) {
              $jury[$key] = ucwords(strtolower(trim($member)));
            }
            $board = implode(', ',$jury);
          }

          $nodes[] = [
            'cod_ths' => $these['COD_THS'],
            'title' => trim($these['LIB_THS']),
            'type' => 'viva',
            'langcode' => 'fr',
            'uid' => $uid,
            'status' => 1,
            'field_subtitle' => !empty($these['LIB_NOM_IND']) ? ucwords(strtolower(trim($these['LIB_NOM_IND']))) : "",
            'field_thesis_supervisor' => !empty($these['NOMDIR']) ? ucwords(strtolower(trim($these['NOMDIR']))) : "",
            'field_co_director' => !empty($these['NOMCODIR']) ? ucwords(strtolower(trim($these['NOMCODIR']))) : "",
            'field_board' => $board,
            'field_event_address' => !empty($these['LIB_CMT_LEU_SOU_THS']) ? $these['LIB_CMT_LEU_SOU_THS'] : "",
            'field_viva_date' => $date_sout,
            'field_hdr' => ($these['TEM_DOC_HDR'] == "HDR") ? 1 : 0,
            'field_categories' => $category,
            'cod_edo' => !empty($these['COD_EDO']) ? "ED" . $these['COD_EDO'] :  "",
            'lib_edo' => (!empty($these['LIB_EDO'])) ? $this->formatEdoLabel($these['LIB_EDO']) : "",
          ];
        }
      }
    }

    return $nodes;
  }

  private function formatEdoLabel($label) {

    if (preg_match('/^[aeiouyh]/i', $label) ||
      preg_match('/^[É]/i', $label) ||
      preg_match('/^[é]/i', $label)) {
      $edo = "École doctorale d'" . $label;
    }
    elseif (preg_match('/(de)/i', $label)) {
      $edo = "École doctorale " . $label;
    }
    else {
      $edo = "École doctorale de " . $label;
    }
    return $edo;
  }
}
