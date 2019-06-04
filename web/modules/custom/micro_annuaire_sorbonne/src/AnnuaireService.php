<?php

namespace Drupal\micro_annuaire_sorbonne;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Class AnnuaireService.
 *
*/
class AnnuaireService implements AnnuaireServiceInterface {

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
   */
  public function getUserList($siteId) {
//    dump('service');
    $users = [];
    $siteStorage = $this->entityTypeManager->getStorage('site');
    $currentSite = $siteStorage->load($siteId);

//    dump($currentSite->get('groups')->value);
    if (!empty($currentSite->get('groups')->value)){
      $groups = explode(';', $currentSite->get('groups')->value);
//      dump($groups);

      $config = \Drupal::config('micro_annuaire_sorbonne.annuaireconfig');
      $ws = $config->get('url_ws');
      $search = $config->get('type_de_recherche');

      $searchUser = $ws . $search;
//      dump($searchUser);
      $filter_member_of_group = '';

      foreach ($groups as $group) {
        $filter_member_of_group .= 'structures-' . $group . '|';
      }

      $params = [
        // on enleve le dernier '|'
        'filter_member_of_group' => substr($filter_member_of_group,0, -1),
        'filter_labeledURI' => '*',
        'attrs' => 'sn,givenName,mail,telephoneNumber,labeledURI,supannEntiteAffectation,postalAddress,supannListeRouge',
        'maxRows' => '500'
      ];
      $ch = curl_init();
//      dump($searchUser . http_build_query($params));
      curl_setopt($ch, CURLOPT_URL, $searchUser . http_build_query($params));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

      $users = json_decode(curl_exec($ch), TRUE);
//      dump($users);

      curl_close($ch);

    }

    return $users;

  }

}
