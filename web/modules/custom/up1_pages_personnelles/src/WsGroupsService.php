<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Class AnnuaireService.
 *
*/
class WsGroupsService implements WsGroupsServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  /**
   * Constructs a AnnuaireService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param $siteId (int) ID du site dont on veut recuperer l'annuaire
   *
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserList($affiliation, $siteId = '') {
    $users = [];
    $filter = '';
    $config = \Drupal::config('up1_pages_personnelles.settings');

    if (!empty($siteId)) {
      $siteStorage = $this->entityTypeManager->getStorage('site');
      $currentSite = $siteStorage->load($siteId);

      if (!empty($currentSite->get('groups')->value)) {
        $structure = [
          'filter_member_of_group' => 'structures-' . $currentSite->get('groups')->value
        ];
      }
    }

    $ws = $config->get('url_ws');
    $searchUser = $config->get('search_user');
    $filter_affiliation = $config->get("filtre_$affiliation");
    $request = $ws . $searchUser . $filter_affiliation;
    $labeledURI = $config->get('other_filters');
    if (!empty($labeledURI)) {
      $request .= $labeledURI;
    }
    $params = [
      'attrs' => 'sn,givenName,labeledURI,supannEntiteAffectation,eduPersonPrimaryAffiliation,supannListeRouge'
    ];
    $ch = curl_init();
    if (isset($structure)) {
      $params['attrs'] .= ',employeeType';
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params) . '&' . http_build_query($structure));
    }
    else {
      curl_setopt($ch, CURLOPT_URL, $request . '&' . http_build_query($params));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

    $users = json_decode(curl_exec($ch), TRUE);

    curl_close($ch);

    $reponse['users'] = $users;
    \Drupal::logger('pp')->info(print_r($reponse['users'], 1));
    return $reponse;
  }

}
