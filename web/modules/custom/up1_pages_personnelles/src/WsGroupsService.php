<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Class WsGroupsService.
 *
 */
class WsGroupsService implements WsGroupsServiceInterface {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  const SERVICE_NAME = 'up1_pages_personnelles.wsgroups';
  /**
   * Constructs a AnnuaireService object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function me(): self {
    return \Drupal::service(self::SERVICE_NAME);
  }

  /**
   * @param $siteId (int) ID du site dont on veut recuperer l'annuaire
   *
   * @return array|mixed
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getUserList($affiliation, $siteId = '', $trombi_settings = NULL) {
    if (!empty($siteId)) {
      $siteStorage = $this->entityTypeManager->getStorage('site');
      $currentSite = $siteStorage->load($siteId);

      if (!empty($currentSite->get('groups')->value)) {
        $structure = [
          'filter_member_of_group' => 'structures-' . $currentSite->get('groups')->value,
          'showExtendedInfo' => true
        ];
      }
    }
    $request = $this->getRequest($affiliation);

    $params = [
      'attrs' => 'sn,givenName,displayName,labeledURI,employeeType,supannEntiteAffectation-all,eduPersonPrimaryAffiliation,supannListeRouge,supannConsentement'
    ];
    if (!empty($trombi_settings)) {
      if ($trombi_settings['supannRole']) {
        $params['attrs'] .= ',supannRoleEntite-all';
      }
      if ($trombi_settings['supannEntite_pedagogy'] || $trombi_settings['supannEntite_research']) {
        $params['attrs'] .= ',supannEntiteAffectation-all';
      }
    }

    $ch = curl_init();
    if (isset($structure)) {
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params) . '&' . http_build_query($structure));

    }
    else {
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $users = json_decode(curl_exec($ch), TRUE);

    curl_close($ch);

    $reponse['users'] = $users;

    return $reponse;
  }

  /**
   * @param $affiliation
   * @param $siteId
   * @param $trombi_settings
   * @return array
   */
  public function getUserListWithConsent($consent, $affiliation, $siteId = '', $trombi_settings = NULL) {
    $request = $this->getRequest(NULL, FALSE);

    /**
     * {PROJ:OBSIA}CGU Observatoire IA
     *
     */
    $params = [
      'filter_supannConsentement' => $consent,
      'attrs' => 'sn,givenName,displayName,labeledURI,employeeType,info,supannEntiteAffectation,eduPersonPrimaryAffiliation,supannListeRouge',
      'showExtendedInfo' => true
    ];
    if (!empty($trombi_settings)) {
      if ($trombi_settings['supannRole']) {
        $params['attrs'] .= ',supannRoleEntite-all';
      }
      if ($trombi_settings['supannEntite_pedagogy'] || $trombi_settings['supannEntite_research']) {
        $params['attrs'] .= ',supannEntiteAffectation-all';
      }
    }

    $ch = curl_init();
    if (isset($structure)) {
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params) . '&' . http_build_query($structure));
    }
    else {
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $users = json_decode(curl_exec($ch), TRUE);

    curl_close($ch);

    $reponse['users'] = $users;

    return $reponse;
  }

  public function getUsers($affiliation) {

    $request = $this->getRequest($affiliation);

    $params = [
      'attrs' => 'uid,displayName,supannCivilite,labeledURI,supannListeRouge'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $users = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    $reponse['users'] = $users;

    return $reponse;
  }

  private function getRequest($affiliation = NULL, $filter_labeledURI = TRUE) {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $ws = $config->get('url_ws');
    $searchUser = $config->get('search_user');
    $filter_affiliation = $config->get("filtre_$affiliation");
    $request = $ws . $searchUser . $filter_affiliation;
    if ($filter_labeledURI && !empty($config->get('other_filters')) ) {
      $request .= $config->get('other_filters');
    }

    return $request;
  }

  public function getAllUsers() {
    $faculty = $this->getUsers('faculty');
    $student = $this->getUsers('student');

    return array_merge($faculty['users'], $student['users']);
  }

  public function getSiteField($siteId, $field) {
    $siteStorage = $this->entityTypeManager->getStorage('site');
    $currentSite = $siteStorage->load($siteId);
    $value = $currentSite->get($field)->value;

    return $value;
  }


  public function userHasPagePersoWsGroups($username): bool {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $ws = $config->get('url_ws');
    $searchUser = $config->get('search_user');

    if (!empty($ws) && !empty($searchUser)) {
      $filter = '&id=' . trim($username);
      $params = [
        'attrs' => 'labeledURI,supannListeRouge',
      ];

      $parsed_url = UrlHelper::parse($ws . $searchUser . $filter . '&' . http_build_query($params));
      $url = Url::fromUri($parsed_url['path'], ['query' => $parsed_url['query']]);

      $client = \Drupal::httpClient();
      try {
        $response = $client->get($url->toString());
        $user = json_decode($response->getBody()->getContents(), TRUE);

        if (!empty($user[0]['labeledURI'])) {
          return TRUE;
        }
      } catch (RequestException $e) {
        return FALSE;
      }
    }

    return FALSE;
  }
}
