<?php

namespace Drupal\up1_global\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class CentresService {
  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * CentresService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('up1_global.settings');
  }

  /**
   * Construct the base URL to the web service.
   *
   * @return string
   *   The base URL.
   */
  public function getWebServiceUrl() {
    $protocol = $this->settings->get('webservice_centres.protocol');
    $hostname = $this->settings->get('webservice_centres.hostname');

    if (!isset($hostname) || empty($hostname)) {
      \Drupal::logger('up1_global')
        ->error('You must define the hostname of the web service');
      return FALSE;
    }
    else {
      return "$protocol://$hostname";
    }
  }

  public function getCentresJson($url) {
    $json = file_get_contents($url);
    $dataArray = json_decode($json, TRUE);

    return $dataArray;
  }

  /**
   * Get a centre in the list.
   *
   * @param $url
   * @param $code
   *
   * @return string
   *   The code.
   */
  public function getACentre($url, $code) {
    if (!empty($dataArray = $this->getCentresJson($url))) {
      $key = array_search($code, array_column($dataArray, 'Code'));
      $centre = $dataArray[$key];

      return $centre;
    }

    else {
      return FALSE;
    }
  }

  public function renderCentre($url, $code) {
    $centre = $this->getACentre($url, $code);
    $htmlCentre = '';

    if ($centre) {
      $htmlCentre .= '<div class="centre-up1">';
      $htmlCentre .= '<h4>' . $centre['LibWeb'] . '</h4>';
      $htmlCentre .= '<address><i class="fa fa-map-marker"></i>' . $centre['Adresse'] . '</address>';
      if (!empty($centre['Tel']) || $centre['Fax']) {
        $htmlCentre .= '<div class="centre-phone-fax" >';
        if (!empty($centre['Tel'])) {
          $htmlCentre .= '<div class="centre-phone">
          <span>' . t('Phone : ') . '</span>
          <a href = "tel:' . $centre['Tel'] . '">' . $centre['Tel'] . '</a >
          </div >';
        }
        if (!empty($centre['Fax'])) {
          $htmlCentre .= '<div class="centre-fax">
          <span>' . t('Fax : ') . '</span>' . $centre['Fax'] . '</div >';
        }
        $htmlCentre .= '</div >';
      }
      if (!empty($centre['Metro']) || $centre['Rer'] || $centre['Bus']) {
        $htmlCentre .= '<div class="centre-itineraire">';
        if (!empty($centre['Metro'])) {
          $htmlCentre .= '<div class="centre-metro">
          <i class="fa fa-subway"></i>' . $centre['Metro'] . '</div>';
        }
        if (!empty($centre['Rer'])) {
          $htmlCentre .= '<div class="centre-rer" >
          <i class="fa fa-train" ></i >' . $centre['Rer'] . '</div >';
        }
        if (!empty($centre['Bus'])) {
          $htmlCentre .= '<div class="centre-bus" >
          <i class="fa fa-bus" ></i >' . $centre['Bus'] . '</div >';
        }
        $htmlCentre .= '</div>';
      }
    }

    $htmlCentre .= '</div>';

    return $htmlCentre;
  }
}
