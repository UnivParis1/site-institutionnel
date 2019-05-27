<?php


namespace Drupal\up1_theses\Service;


use Drupal\Core\Config\ConfigFactoryInterface;

class ThesesHelper {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Array with data from json.
   *
   * @var array
   */
  protected $jsonData = [];


  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('up1_theses.settings');
  }

  /**
   * Construct the base URL to the Thèses webservice.
   *
   * @return string
   *   The base URL.
   */
  public function getWebServiceUrl() {
    $protocol = $this->settings->get('webservice.protocol');

    return $protocol . "://" . $this->settings->get('webservice.hostname');
  }

  /**
   * Gets Json data from url
   *
   * @param string $webservice
   * return void
   */
  public function getJsonDataFromUrl() {
    $this->jsonData = file_get_contents($this->getWebServiceUrl());
  }


  /**
   * Transform Json data to array
   *
   * return void
   */
  public function transformJsonDataToArray() {
    try {
      $json = file_get_contents($this->getWebServiceUrl());
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
   * Gets the DataArray
   *
   * @param string $address
   * @return array The array of json data
   */
  public function getLatLongFromAddress($address) {
    $latLon = [
      'lat' => '',
      'lon' => '',
    ];

    $baseUrl = 'https://nominatim.openstreetmap.org/?format=json&addressdetails=1&q=';

    $url = "$baseUrl$address&format=json&limit=1";

    $response = \Drupal::httpClient()->get($url, array('headers' => array('Accept' => 'text/plain')));
    $data = $response->getBody();

    $json = json_decode($data, TRUE);

    if ($json) {
      $latLon['lat'] = $json[0]['lat'];
      $latLon['lon'] = $json[0]['lon'];
    }
    return $latLon;

  }

  /**
   * Gets the DataArray
   *
   * @return array The array of json data
   */
  public function getJsonDataArray() {
    return $this->jsonData;
  }

}