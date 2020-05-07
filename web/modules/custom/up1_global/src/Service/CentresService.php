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
      $key = array_search($code, array_column($dataArray, 'code'));
      $centre = $dataArray[$key];

      return $centre;
    }

    else {
      return FALSE;
    }
  }

  public function getCentreByName($url, $title) {
    if (!empty($dataArray = $this->getCentresJson($url))) {
      $title = trim(strtolower(htmlspecialchars($title)));
      $key = array_search($title, array_column($dataArray, 'intitule'));
      $centre = $dataArray[$key];

      return $centre;
    }

    else {
      return FALSE;
    }
  }

  public function getCentreImage($code) {
    $url = $this->getWebServiceUrl();
    \Drupal::logger('centreService')->logger("Webservice URL : $url");
    $file = "$url$code.jpg";
    \Drupal::logger('centreService')->logger("FILE : $file");
    $file_headers = @get_headers($file);
    \Drupal::logger('centreService')->logger($file_headers);
    \Drupal::logger('centres')->info("JPG : " . print_r($file_headers, 1));
    if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
      $image_path = $file;
    }
    else {
      $file = "$url$code.png";
      $file_headers = @get_headers($file);
      \Drupal::logger('centres')->info("PNG : " . print_r($file_headers, 1));
      if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
        $image_path = $file;
      }
      else {
        $random = rand(0, 1) ? 'blue' : 'white';
        $image_path = $url."default_$random.jpg";
      }
    }

    return $image_path;
  }

  public function renderCentre($url, $code) {
    $image_url = $this->getCentreImage($code);
    \Drupal::logger('centreService')->logger("RenderCentre image url : $image_url");
    $centre = $this->getACentre($url, $code);
    if ($centre) {
      $htmlCentre = "";

      $block_phone = "<div class='centre-phone'>";
      $block_email = "<div class='centre-email'>";
      $block_info = "<div class='centre-info'>";

      $title = $centre['intitule'];
      $address = $centre['adresse'];
      $block_address = "
      <i class='fa fa-map-signs'></i>
      <a href='//maps.google.fr/maps?t=m&z=16&q=$address' target='_blank' title='$title'>
        $address
      </a>";
      //telephone
      !empty($centre['telephone']) ? $phone = $centre['telephone'] : $phone = "";

      if (!empty($phone)) {
        $block_phone .= "<i class='fas fa-phone'></i>";
        $block_phone .= "<div>";
        foreach ($phone as $item) {
          $block_phone .= "<p><a href='tel:$item'>$item</a></p>";
        }
        $block_phone .= "</div>";
      }
      $block_phone .= "</div>";

      //email
      !empty($centre['email']) ? $email = $centre['email'] : $email = "";

      if (!empty($email)) {
        $block_email .= "<div class='centre-phone'>
            <i class='fas fa-envelope'></i>
            <a href='mailto:$email'>$email</a>
            </div>";
      }
      $block_email .= "</div>";

      //infos
      !empty($centre['informations']) ? $info = $centre['informations'] : $info = "";

      if (!empty($info)) {
        $block_info .= "<div class='centre-more'>
        <i class='fas fa-info-circle'></i>
        <a href='$info'>$info</a>
        </div>";
      }
      $block_info .= "</div>";
      $img = file_create_url($image_url);
      $htmlCentre .= "<div class='image-centre mask no-hover relative-wrapper'>
        <img src='$img' />
    </div>";

      $htmlCentre .=
        "<div class='centre-up1'>
           <div class='centre-up1-info'>
            <h4>$title</h4>
            <address>$block_address</address>
            $block_phone
            $block_email
            $block_info
          </div>
        </div>";

      return $htmlCentre;
    }
  }
}
