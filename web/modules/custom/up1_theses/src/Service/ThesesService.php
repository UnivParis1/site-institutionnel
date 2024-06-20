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
    $hostname = $this->settings->get('webservice.hostname');

    if (!isset($hostname) || empty($hostname)) {
      \Drupal::logger('up1_theses')
        ->error('You must define the hostname of the web service');
      return FALSE;
    }
    else {
      return $hostname;
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
   * @return int $uid
   */
  public function getWebmestreUid() {
    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
    $uids = $user_storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'admin_dir_com')
      ->accessCheck(FALSE)
      ->execute();
    $users = $user_storage->loadMultiple($uids);

    if ($users) {
      $dircom = reset($users);
      $uid = $dircom->id();
    }
    else {
      $uid = 1;
    }
    return $uid;

  }

  /**
   * Add $cod_ths & $nid in "up1_theses_import" table to prevent
   * duplications during "theses" imports.
   *
   * @param $cod_ths
   * @param $nid
   * @param $created
   *
   * @throws \Exception
   */
  public function populateImportTable($cod_ths, $nid, $created) {
    try {
      \Drupal::database()->merge('up1_theses_import')
        ->keys([
          'cod_ths' => $cod_ths,
          'nid' => $nid,
          'created' => $created,
        ])
        ->execute();
      \Drupal::logger('up1_theses')->info("node $nid code THS $cod_ths created at $created.");
    } catch (\Exception $e) {
      \Drupal::logger('up1_theses')->error("Erreur de création de l'entrée en base de données. " . $e->getMessage());
    }
  }
}
