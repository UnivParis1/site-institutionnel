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
    //$this->settings = $configFactory->get('up1.settings');
  }

  public function getCentresJson($url) {
    $json = file_get_contents($url);
    $dataArray = json_decode($json, TRUE);

    return $dataArray;
  }

  /**
   * Get a centre in the list.
   * @param $code
   *
   * @return array $centre.
   */
  public function getACentre($code) {
    $centre = [];
    $url = "https://ws-centres.univ-paris1.fr/new_liste_centres_up1.json";
    $dataArray = $this->getCentresJson($url);
    if (!empty($dataArray)) {
      $key = array_search($code, array_column($dataArray, 'code'));
      $centre = $dataArray[$key];
      $url_images = "https://ws-centres.univ-paris1.fr/images/";
      $file = "$url_images$code.jpg";
      if ($code == "0011_B") $file = $url_images . "0011_A.jpg";
      $file_headers = @get_headers($file);
      if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
        $centre['image_path'] = $file;
      }
      else {
        $file = "$url_images$code.png";
        $file_headers = @get_headers($file);
        if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
          $centre['image_path'] = $file;
        }
        else {
          $centre['image_path'] = file_create_url($url_images . "default_white.jpg");
        }
      }
    }

    return $centre;
  }
}
