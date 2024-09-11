<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Cache\CacheBackendInterface;

class HalManager implements HalInterface {

  /**
   * PagePersonnelleService constructor.
   */
  public function __construct() {}

  /**
   * @param string $method
   * @param string $firstname
   * @param string $lastname
   * @param string|null $id_hal
   *
   * @return array|mixed
   */
   public function getUserPublications($method, $firstname, $lastname, $id_hal = NULL) {

   $cache_key = "user_publications:" . md5($method . $firstname . $lastname . $id_hal);

    //Récupération du cache
    $cache = \Drupal::cache()->get($cache_key);
    if ($cache) {
      return $cache->data;
    }
    else {
      $publications = FALSE;

      switch ($method) {
        case 'idhal':
          $author = "idHal=$id_hal";
          break;
        case 'nomprenom':
          $firstname = $this->removeSpecialChars($firstname);
          $lastname = $this->removeSpecialChars($lastname);
          $author = "auteur_exp=$firstname+$lastname&collection_exp=UNIV-PARIS1";
          break;
      }
      $config = \Drupal::config('up1_pages_personnelles.settings');
      $ws = $config->get('url_hal_api');

      $searchUser = "$ws?$author";

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

      try {
        $response = \Drupal::httpClient()->get($url, [
          'timeout' => 15,
          'connect_timeout' => 10, 
        ]);

        if ($response->getStatusCode() == 200) {
          $publications = $response->getBody()->getContents();
          \Drupal::cache()->set($cache_key, $publications, time() + 86400);
        } else {
          \Drupal::logger('up1_pages_personnelles')->error('Error while fetching HAL data for user : @user', [
            '@user' => $firstname . ' ' . $lastname
          ]);
        }
      } catch (ConnectException $e) {
        \Drupal::logger('up1_pages_personnelles')->error('Connexion error for user : @user', [
          '@user' => $firstname . ' ' . $lastname
        ]);
      } catch (RequestException $e) {
        \Drupal::logger('up1_pages_personnelles')->error('CError while fetching HAL data for user : @user', [
          '@user' => $firstname . ' ' . $lastname
        ]);
      } 
    }

    return $publications;
  }

  /**
   * @param $response
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

  private function removeSpecialChars($string) {
    $string = str_replace(' ','%20',trim(strtolower(
      strtr($string, 'áàâäãåçéèêëíìîïñóòôöõúùûüýÿ', 'aaaaaaceeeeiiiinooooouuuuyy'))));

    return $string;
  }
}
