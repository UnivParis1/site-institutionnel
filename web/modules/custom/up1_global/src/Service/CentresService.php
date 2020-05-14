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
    $this->settings = $configFactory->get('up1.settings');
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
   * @param $code
   *
   * @return array $centre.
   */
  public function getACentre($code) {
    $centre = [];
    $url = $this->getWebServiceUrl();
    $dataArray = $this->getCentresJson($url);
    if (!empty($dataArray)) {
      $key = array_search($code, array_column($dataArray, 'code'));
      $centre = $dataArray[$key];
      $protocol = \Drupal::config('up1.settings')->get('webservice_centres.protocol');
      $path = \Drupal::config('up1.settings')->get('webservice_centres.images_path');
      $url_images = "$protocol://$path";
      $file = "$url_images$code.jpg";
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
          $random = rand(0, 1) ? 'blue' : 'white';
          $centre['image_path'] = file_create_url($url_images . "default_$random.jpg");
        }
      }
    }

    return $centre;
  }
}
