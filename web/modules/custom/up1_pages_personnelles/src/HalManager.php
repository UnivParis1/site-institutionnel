<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class HalManager implements HalInterface {

  /**
   * PagePersonnelleService constructor.
   */
  public function __construct() {}

  /**
   * @param $username (string) username du user dont on veut les informations.
   *
   * @return array|mixed
   */
  public function getUserPublications($username) {
    $publications = FALSE;

    if (isset($username) && !empty($username)) {
      $config = \Drupal::config('up1_pages_personnelles.settings');
      $ws = $config->get('url_hal_api');

      $searchUser = "$ws?idHal=$username";

      $params = [
        "CB_auteur" => "oui",
        "CB_titre" => "oui",
        "CB_typdoc" => "oui",
        "CB_article" => "oui",
        "CB_pubmedId" => "oui",
        "langue" => "Francais",
        "tri_exp" => "annee_publi",
        "tri_exp2" => "typdoc",
        "tri_exp3" => "date_publi",
        "ordre_aff" => "TA",
        "Fen" => "Aff",
      ];

      $url = $searchUser . '&' . http_build_query($params) . "&noheader";
      $publications = file_get_contents($url);
    }

    return $publications;
  }

  /**
   * @param $array
   *
   * @return mixed
   */
  private function formatHalData($response) {
    $publications = [];

    if (isset($response['numFound']) && $response['numFound'] > 0
      && isset($response['docs'])) {
      foreach ($response['docs'] as $doc) {
        $title = isset($doc['title_s']) && !empty($doc['title_s'])?
          reset($doc['title_s']) : "";
        $author = isset($doc['authFullName_s']) ? implode(", ", $doc['authFullName_s']): "";
        $language = isset($doc['language_s']) ? reset($doc['language_s']): "";

        $publications[] = [
          'title' => $title,
          'author' => $author,
          'language' => $language,
          'journal' => !empty($doc['journalTitle_s'])? $doc['journalTitle_s'] : "",
          'producedYear' => !empty($doc['producedDateY_i'])? $doc['producedDateY_i'] : "",
          'url' => !empty($doc['uri_s'])? $doc['uri_s'] : "",
          'docType' => !empty($doc['docType_s'])? $doc['docType_s'] : "",
        ];
      }
    }

    return $publications;
  }
}