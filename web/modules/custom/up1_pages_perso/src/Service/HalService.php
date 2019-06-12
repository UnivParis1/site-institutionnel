<?php

namespace Drupal\up1_pages_perso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class HalService {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;
  use StringTranslationTrait;

  /**
   * ThesesService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('up1_pages_perso.settings');
  }

  /**
   * Construct the base URL to the HAL web service.
   *
   * @return string
   *   The base URL.
   */
  public function getWebServiceUrl() {
    $hal = $this->settings->get('webservice.hal_publications');

    if (!isset($hal) || empty($hal)) {
      \Drupal::logger('up1_pages_perso')
        ->error('You must define the hostname of the web service');
      return FALSE;
    }
    else {
      return "$hal";
    }
  }

  public function transformJsonDataToArray($jsonUrl) {
    try {
      $json = file_get_contents($jsonUrl);
      $dataArray = json_decode($json, TRUE);

      if (!empty($dataArray)) {
        return $dataArray;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('up1_pages_perso', $e);
    }

  }

  public function formatPublications($id_hal, $filters, $sort) {
    $url = $this->getWebServiceUrl();
    $query = "$url:$id_hal&fl=title_s,authFullName_s";

    if ($filters) {
      $query .= ",$filters";
    }
    else {
      $query .= ",producedDateY_i,language_s,uri_s,journalTitle_s";
    }
    $query .= "&sort=producedDateY_i%20$sort";
    $jsonData = $this->transformJsonDataToArray($query);
    $rawDocs = $jsonData['response']['docs'];

    $docs = [];

    foreach ($rawDocs as $rawDoc) {
      $title = isset($rawDoc['title_s']) && !empty($rawDoc['title_s'])?
        implode(", ", $rawDoc['title_s']) : "";
      $author = isset($rawDoc['authFullName_s']) ? implode(", ", $rawDoc['authFullName_s']): "";
        $language = isset($rawDoc['language_s']) ? implode(", ", $rawDoc['language_s']): "";

      $docs[] = [
        $title, $author, $language,
        !empty($rawDoc['journalTitle_s'])? $rawDoc['journalTitle_s'] : "",
        !empty($rawDoc['producedDateY_i'])? $rawDoc['producedDateY_i'] : "",
        !empty($rawDoc['uri_s'])? $rawDoc['uri_s'] : "",
      ];
    }
    return $docs;

  }

}