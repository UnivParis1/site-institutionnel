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

  public function renderCentre($url, $code, $with_phone = TRUE, $with_fax = TRUE) {
    $centre = $this->getACentre($url, $code);
    if ($centre) {
      $tel_fax = "";
      $transports = "";
      $has_tel_fax = 0;
      $has_transports = 0;
      !empty($centre['LibWeb'])? $libWeb = $centre['LibWeb'] : $libWeb = "";
      !empty($centre['Adresse'])? $adresse = $centre['Adresse'] : $adresse = "";
      !empty($centre['Tel'] && $with_phone)? $tel = $centre['Tel'] : $tel = "";
      !empty($centre['Tel'] && $with_phone)? $has_tel_fax++ : $has_tel_fax;
      !empty($centre['Fax'] && $with_fax)? $fax = $centre['Fax'] : $fax = "";
      !empty($centre['Fax'] && $with_fax)? $has_tel_fax++ : $has_tel_fax;
      !empty($centre['Metro'])? $metro = $centre['Metro'] : $metro = "";
      if (!empty($metro)) {
        $metro = "<div>
        <span><b>" . t('Métro : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $metro) . "</span>
        </div>";
        $has_transports++;
      }
      !empty($centre['Rer'])? $rer = $centre['Rer'] : $rer = "";
      if (!empty($rer)) {
        $rer = "<div>
        <span><b>" . t('RER : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $rer) . "</span>
        </div>";
        $has_transports++;
      }
      !empty($centre['Bus'])? $bus = $centre['Bus'] : $bus = "";
      if (!empty($bus)) {
        $bus = "<div>
        <span><b>" . t('Bus : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $bus) . "</span>
        </div>";
        $has_transports++;
      }
      if ($has_tel_fax > 0) {
        $tel_fax = "<div class='centre-phone-fax'>";
        if (!empty($tel)) {
          $tel_fax .= "<div class='centre-phone'>
            <span>" . t('Phone : ') . "</span>
            <a href='tel:$tel'>$tel</a>  
            </div>";
        }
        if (!empty($fax)) {
          $tel_fax .= "<div class='centre-fax'>
            <span>" . t('Fax : ') . "</span>
            <span>$fax</span>  
            </div>";
        }
        $tel_fax .= '';
      }
      if ( $has_transports > 0 ) {
        $transports = "<div class='centre-itineraire'>
           <span>" . t('Accès transports : ') . "</span>
           $metro $rer $bus
           </div>";
      }

      $htmlCentre =
        "<div class='centre-up1'>
            <div class='center-address'>
               <i class='fa fa-map-marker'></i> 
               <div>
                <h4>$libWeb</h4>
                <address>$adresse</address>
                $tel_fax
               </div>
            </div>
            $transports
        </div>";

      return $htmlCentre;
    }
  }
}
