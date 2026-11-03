<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Http\WsClient;

/**
 * Lecture des publications d'une personne sur HAL (haltools, rendu HTML).
 *
 * Aucun cache ici : les encarts sont enregistrés et rafraîchis par
 * HalPublications, depuis le cron.
 */
final class HalManager {

  public function __construct(
    private readonly WsClient $wsClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TransliterationInterface $transliteration,
  ) {
  }

  public function isAvailable(): bool {
    return $this->wsClient->isAvailable(WsClient::HAL);
  }

  /**
   * Liste HTML des publications.
   *
   * @param string $method
   *   'idhal' ou 'nomprenom'.
   *
   * @return string
   *   Chaîne vide si HAL ne connaît aucune publication.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si HAL est injoignable ou ne renvoie pas de liste exploitable.
   * @throws \InvalidArgumentException
   *   Si la méthode ou ses paramètres (idHAL, nom et prénom) manquent.
   */
  public function fetchPublications(string $method, string $firstname, string $lastname, ?string $id_hal = NULL): string {
    $firstname = $this->transliteration->transliterate($firstname, 'fr');
    $lastname = $this->transliteration->transliterate($lastname, 'fr');

    $author = match (TRUE) {
      $method === 'idhal' && (string) $id_hal !== '' => 'idHal=' . rawurlencode((string) $id_hal),
      $method === 'nomprenom' && $firstname !== '' && $lastname !== '' => 'auteur_exp=' . rawurlencode($firstname) . '+' . rawurlencode($lastname) . '&collection_exp=UNIV-PARIS1',
      default => throw new \InvalidArgumentException("Recherche HAL « $method » incomplète."),
    };

    $url = $this->configFactory->get('up1_pages.settings')->get('url_hal_api') . '?' . $author . '&' . http_build_query([
      'CB_auteur' => 'oui',
      'CB_titre' => 'oui',
      'CB_typdoc' => 'oui',
      'CB_article' => 'oui',
      'CB_pubmedId' => 'oui',
      'langue' => 'Francais',
      'tri_exp' => 'annee_publi',
      'tri_exp2' => 'typdoc',
      'tri_exp3' => 'date_publi',
      'ordre_aff' => 'TA',
      'Fen' => 'Aff',
    ]) . '&noheader';

    return $this->extractPublications($this->wsClient->get(WsClient::HAL, $url));
  }

  /**
   * Bloc #res_script de la page haltools ; chaîne vide s'il est vide.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si la page n'a pas de bloc #res_script (page d'erreur, maintenance de
   *   HAL…) : ce n'est pas « aucune publication ».
   */
  private function extractPublications(string $contents): string {
    if (trim($contents) === '') {
      throw new WsUnavailableException('HAL : réponse vide.', WsClient::HAL);
    }
    $dom = new \DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $contents);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $publications = $dom->getElementById('res_script');
    if (!$publications) {
      throw new WsUnavailableException('HAL : réponse sans liste de publications.', WsClient::HAL);
    }
    $text = trim(str_replace("\u{00A0}", ' ', $publications->textContent));
    if ($text === '') {
      return '';
    }

    // Contenu affiché tel quel par le thème : on retire au moins les scripts.
    return Xss::filterAdmin($dom->saveHTML($publications));
  }

}
