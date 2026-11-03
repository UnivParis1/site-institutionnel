<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Gateway;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Http\WsClient;

/**
 * Listes de personnes lues dans wsgroups.
 *
 * Toutes les méthodes lèvent WsUnavailableException si wsgroups ne répond
 * pas : c'est à l'appelant de choisir entre le repli (trombinoscopes) et
 * l'abandon (synchro). Aucune ne renvoie de liste partielle ou vide « par
 * défaut ».
 */
final class WsGroupsDirectoryGateway {

  private const SETTINGS = 'up1_pages.settings';

  public function __construct(
    private readonly WsClient $wsClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Liste complète des personnes ayant droit à une page personnelle.
   *
   * @param string $affiliation
   *   'faculty' (enseignants-chercheurs) ou 'student' (doctorants).
   *
   * @return array
   *   Une entrée par personne (uid, displayName, supannCivilite, mail…).
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si wsgroups ne répond pas, ou répond une liste vide ou illisible.
   */
  public function getPagePersoUsers(string $affiliation): array {
    $url = $this->buildListUrl($affiliation) . '&' . http_build_query([
      'attrs' => 'uid,displayName,supannCivilite,mail,labeledURI,supannListeRouge',
    ]);
    $users = $this->wsClient->getJson(WsClient::WSGROUPS, $url, $this->timeout('sync_timeout'));

    // Une liste de personnes, chacune avec un uid. Tout le reste (objet
    // d'erreur, liste vide, entrées sans uid) est une réponse douteuse.
    if (!is_array($users) || empty($users) || !array_is_list($users)) {
      throw new WsUnavailableException("wsgroups ($affiliation) : liste vide ou réponse inattendue.", WsClient::WSGROUPS);
    }
    $without_uid = count(array_filter($users, static fn($user) => !is_array($user) || empty($user['uid']) || !is_string($user['uid'])));
    if ($without_uid > 0) {
      throw new WsUnavailableException("wsgroups ($affiliation) : $without_uid entrées sans uid, réponse inattendue.", WsClient::WSGROUPS);
    }

    return $users;
  }

  /**
   * La personne a-t-elle encore droit à une page personnelle ?
   *
   * Même requête que la synchro, limitée à un identifiant : sert à confirmer
   * une désactivation juste avant de l'appliquer.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si wsgroups ne répond pas : on ne peut alors rien conclure.
   */
  public function isPagePersoUser(string $login): bool {
    foreach (['faculty', 'student'] as $affiliation) {
      $url = $this->buildListUrl($affiliation) . '&' . http_build_query(['id' => $login, 'attrs' => 'uid']);
      $users = $this->wsClient->getJson(WsClient::WSGROUPS, $url);
      if (!is_array($users) || !array_is_list($users)) {
        throw new WsUnavailableException("wsgroups : réponse inattendue pour $login.", WsClient::WSGROUPS);
      }
      foreach ($users as $user) {
        if (is_array($user) && is_string($user['uid'] ?? NULL) && mb_strtolower($user['uid']) === mb_strtolower($login)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Enseignants-chercheurs et doctorants ayant droit à une page personnelle.
   *
   * @return array
   *   Les personnes, indexées par leur uid LDAP en minuscules. Chacune a une
   *   clé « affiliations » : les listes où elle figure (faculty, student).
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si l'une des deux listes n'a pas pu être récupérée.
   */
  public function getAllPagePersoUsers(): array {
    $users = [];
    foreach (['faculty', 'student'] as $affiliation) {
      foreach ($this->getPagePersoUsers($affiliation) as $user) {
        if (!empty($user['uid']) && is_string($user['uid'])) {
          $login = mb_strtolower(trim($user['uid']));
          $affiliations = $users[$login]['affiliations'] ?? [];
          $affiliations[] = $affiliation;
          $users[$login] = ['affiliations' => $affiliations] + $user;
        }
      }
    }

    return $users;
  }

  /**
   * Personnes d'une structure (mini-site), pour les trombinoscopes.
   *
   * @param string $affiliation
   *   'faculty' ou 'student'.
   * @param int|string $siteId
   *   Identifiant du mini-site : la liste est limitée à son groupe LDAP.
   * @param array|null $trombi_settings
   *   Réglages d'affichage du mini-site (attributs supplémentaires à lire).
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   */
  public function getUserList(string $affiliation, $siteId = '', ?array $trombi_settings = NULL): array {
    $params = [
      'attrs' => 'sn,givenName,displayName,labeledURI,employeeType,supannEntiteAffectation-all,eduPersonPrimaryAffiliation,supannListeRouge,supannConsentement'
        . $this->trombiAttrs($trombi_settings),
    ];
    if (!empty($siteId)) {
      $group = $this->getSiteField($siteId, 'groups');
      if (!empty($group)) {
        $params['filter_member_of_group'] = 'structures-' . $group;
        $params['showExtendedInfo'] = 1;
      }
    }

    return $this->fetchList($this->buildListUrl($affiliation) . '&' . http_build_query($params));
  }

  /**
   * Personnes ayant donné un consentement (annuaires OBSIA, Santé-SHS…).
   *
   * @param string $consent
   *   Valeur de supannConsentement, ex. « {PROJ:OBSIA}CGU ».
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   */
  public function getUserListWithConsent(string $consent, ?array $trombi_settings = NULL): array {
    $config = $this->configFactory->get(self::SETTINGS);
    $params = [
      'filter_supannConsentement' => $consent,
      'attrs' => 'sn,givenName,displayName,labeledURI,employeeType,info,supannEntiteAffectation,eduPersonPrimaryAffiliation,supannListeRouge'
        . $this->trombiAttrs($trombi_settings),
      'showExtendedInfo' => 1,
    ];

    return $this->fetchList($config->get('url_ws') . $config->get('search_user') . '&' . http_build_query($params));
  }

  /**
   * Valeur d'un champ du mini-site (groups, supannConsentement…).
   */
  public function getSiteField($siteId, string $field) {
    $site = $this->entityTypeManager->getStorage('site')->load($siteId);

    return $site ? $site->get($field)->value : NULL;
  }

  private function buildListUrl(string $affiliation): string {
    $config = $this->configFactory->get(self::SETTINGS);

    return $config->get('url_ws') . $config->get('search_user')
      . $config->get("filtre_$affiliation") . $config->get('other_filters');
  }

  private function trombiAttrs(?array $trombi_settings): string {
    $attrs = '';
    if (!empty($trombi_settings['supannRole'])) {
      $attrs .= ',supannRoleEntite-all';
    }
    if (!empty($trombi_settings['supannEntite_pedagogy']) || !empty($trombi_settings['supannEntite_research'])) {
      $attrs .= ',supannEntiteAffectation-all';
    }

    return $attrs;
  }

  private function fetchList(string $url): array {
    $users = $this->wsClient->getJson(WsClient::WSGROUPS, $url, $this->timeout('list_timeout'));
    if (!is_array($users)) {
      throw new WsUnavailableException('wsgroups : liste illisible.', WsClient::WSGROUPS);
    }

    return $users;
  }

  private function timeout(string $key): int {
    return max(1, (int) $this->configFactory->get(self::SETTINGS)->get("ws.$key"));
  }

}
