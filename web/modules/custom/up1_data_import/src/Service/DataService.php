<?php


namespace Drupal\up1_data_import\Service;


class DataService {

  /**
   * Add $old_nid, $old_uuid & $nid in "up1_data_import" table to prevent
   * duplications during data imports.
   *
   * @param $old_nid
   * @param $old_uuid
   * @param $nid
   * @param $created
   *
   * @throws \Exception
   */
  public function populateImportTable($old_nid, $old_uuid, $nid, $created) {
    \Drupal::database()->merge('up1_data_import')
      ->keys([
        'old_nid' => $old_nid,
        'old_uuid' => $old_uuid,
        'nid' => $nid,
        'created' => $created
      ])
      ->execute();
  }

  /**
   * Construct the base URL to the old news Json.
   *
   * @return string
   *   The base URL.
   */
  public function getNewsUrl() {
      return "https://www.pantheonsorbonne.fr/export/actualites/all";
  }

  /**
   * Construct the base URL to the old events Json.
   *
   * @return string
   *   The base URL.
   */
  public function getEventsUrl() {
      return "https://www.pantheonsorbonne.fr/export/events/all";
  }

  /**
   * @param string name
   * @return int $uid
   */
  public function getAuthorUid($name) {
    $user = user_load_by_name($name);
    if ($user) {
      return $user->id();
    }
    else {
      return 1;
    }
  }
}
