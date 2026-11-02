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
    /**
     * Clé de cache posée quand HAL ne répond pas : plus aucun appel pendant
     * HAL_DOWN_DURATION secondes, les pages s'affichent sans publications.
     */
    const HAL_DOWN_KEY = 'up1_pages_personnelles:hal_down';
    const HAL_DOWN_DURATION = 300;

    public function getUserPublications($method, $firstname, $lastname, $id_hal = NULL) {
        $firstname = (string) iconv('UTF-8','ASCII//TRANSLIT',$firstname);
        $lastname = (string) iconv('UTF-8','ASCII//TRANSLIT',$lastname);

        $cache_key = "user_publications:" . md5($method . $firstname . $lastname . $id_hal);

        //Récupération du cache
        $cache = \Drupal::cache()->get($cache_key);
        if ($cache) {
            return $cache->data;
        }

        // HAL a échoué récemment : on n'attend pas, la page s'affiche sans
        // publications.
        if (\Drupal::cache()->get(self::HAL_DOWN_KEY)) {
            return NULL;
        }

        switch ($method) {
            case 'idhal':
                if (empty($id_hal)) {
                    return NULL;
                }
                $author = 'idHal=' . rawurlencode($id_hal);
                break;
            case 'nomprenom':
                $author = 'auteur_exp=' . rawurlencode($firstname) . '+' . rawurlencode($lastname) . '&collection_exp=UNIV-PARIS1';
                break;
            default:
                return NULL;
        }
        $config = \Drupal::config('up1_pages_personnelles.settings');
        $ws = $config->get('url_hal_api');

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

        $url = "$ws?$author&" . http_build_query($params) . "&noheader";

        try {
            // Délais courts : une page perso ne doit pas attendre HAL.
            $response = \Drupal::httpClient()->get($url, [
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);
            $contents = (string) $response->getBody();
            if (trim($contents) === '') {
                throw new \RuntimeException('réponse vide');
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML($contents);
            $publications = $dom->getElementById('res_script');
            if (!$publications) {
                throw new \RuntimeException('réponse sans liste de publications');
            }
            $res_script = trim(str_replace("\u{00A0}", ' ', $publications->textContent));
            $html = $res_script !== '' ? $dom->saveHTML($publications) : '';
            // Mis en cache même vide : une personne sans publication ne relance pas
            // HAL à chaque visite.
            \Drupal::cache()->set($cache_key, $html, time() + 86400);

            return $html !== '' ? $html : NULL;
        }
        catch (\Throwable $e) {
            // HAL injoignable, erreur HTTP, réponse illisible : on n'affiche rien.
            \Drupal::cache()->set(self::HAL_DOWN_KEY, TRUE, time() + self::HAL_DOWN_DURATION);
            \Drupal::logger('up1_pages_personnelles')->error('HAL indisponible (@user), publications masquées pendant @duration s : @message', [
                '@user' => $firstname . ' ' . $lastname,
                '@duration' => self::HAL_DOWN_DURATION,
                '@message' => $e->getMessage(),
            ]);
        }

        return NULL;
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
