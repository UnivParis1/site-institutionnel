<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

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

  /**
   * Constructs a AnnuaireService object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
          'filter_member_of_group' => 'structures-' . $currentSite->get('groups')->value
        ];
      }
    }
    $request = $this->getRequest($affiliation);

    $params = [
      'attrs' => 'sn,givenName,labeledURI,employeeType,supannEntiteAffectation,eduPersonPrimaryAffiliation,supannListeRouge'
    ];
    if (!empty($trombi_settings)) {
      if ($trombi_settings['supannRole']) {
        $params['attrs'] .= ',supannRoleEntite-all';
      }
      if ($trombi_settings['supannEntite_pedagogy'] || $trombi_settings['supannEntite_research']) {
        $params['attrs'] .= ',supannEntiteAffectation-all';
      }
    }

    \Drupal::logger('up1_pages_personnelles')->info(print_r($params['attrs'], 1));

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

  private function getRequest($affiliation) {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $ws = $config->get('url_ws');
    $searchUser = $config->get('search_user');
    $filter_affiliation = $config->get("filtre_$affiliation");
    $request = $ws . $searchUser . $filter_affiliation;
    $labeledURI = $config->get('other_filters');
    if (!empty($labeledURI)) {
      $request .= $labeledURI;
    }

    return $request;
  }

  public function getAllUsers() {
    $faculty = $this->getUsers('faculty');
    $student = $this->getUsers('student');
    $users = array_merge($faculty['users'], $student['users']);



    return $users;
  }

}
