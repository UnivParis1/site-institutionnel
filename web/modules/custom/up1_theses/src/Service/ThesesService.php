<?php

namespace Drupal\up1_theses\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class ThesesService {
  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * ThesesService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('up1_theses.settings');
  }

  /**
   * Construct the base URL to the Thèses web service.
   *
   * @return string
   *   The base URL.
   */
  public function getWebServiceUrl() {
    $protocol = $this->settings->get('webservice.protocol');
    $hostname = $this->settings->get('webservice.hostname');

    if (!isset($hostname) || empty($hostname)) {
      \Drupal::logger('up1_theses')
        ->error('You must define the hostname of the web service');
      return FALSE;
    }
    else {
      return "$protocol://$hostname";
    }
  }

  /**
   * @return mixed
   */
  public function getExistingTheses() {
    $query = \Drupal::database()->select('up1_theses_import', 't')
      ->fields('t', ['cod_ths']);

    return $query->execute()->fetchCol();
  }

  /**
   * Add $cod_ths & $nid in "up1_theses_import" table to prevent
   * duplications during "theses" imports.
   *
   * @param $cod_ths
   * @param $nid
   * @param $created
   */
  public function populateImportTable($cod_ths, $nid, $created) {
    \Drupal::database()->merge('up1_theses_import')
      ->keys([
        'cod_ths' => $cod_ths,
        'nid' => $nid,
        'created' => $created,
      ])
      ->execute();
  }
}