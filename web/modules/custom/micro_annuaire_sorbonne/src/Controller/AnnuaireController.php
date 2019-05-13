<?php

namespace Drupal\micro_annuaire_sorbonne\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * Class AnnuaireController.
 */
class AnnuaireController extends ControllerBase {

  /**
   * @var \Drupal\micro_annuaire_sorbonne\AnnuaireService
   */
  private $annuaireService;

  public function __construct() {
    $this->annuaireService = \Drupal::service('micro_annuaire_sorbonne.annuaire');
  }

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function list($letter) {
    $cache = \Drupal::cache();

    $negotiator = \Drupal::service('micro_site.negotiator');
    $currentSiteId = $negotiator->getActiveId();

    $users = [];
    if (!empty($currentSiteId)) {
      $cachedUser = $cache->get('users_ldap');
      if ($cachedUser){
        $users = $cachedUser->data;
      }
      else {
        $users = $this->annuaireService->getUserList($currentSiteId);
        $cache->set('users_ldap', $users, time() + 60*60);
      }
    }

    $filtered_users = [];
    foreach ($users as $user){
      if (strcasecmp(substr($user['sn'],0,1),$letter) == 0){
        $filtered_users[] = $user;
      }
    }
    // on trie les utilisateurs par ordre alphabetique des cn
    $sortedUsers = usort($filtered_users, function ($a, $b) {
      return strnatcasecmp($a['sn'], $b['sn']);
    });

    $build['item_list'] = [
      '#theme' => 'micro_annuaire_sorbonne',
      '#users' => $filtered_users,
      //'#users' => $users,
      '#site' => $currentSiteId,
      '#attached' => [
        'library' => [
          'micro_annuaire_sorbonne/annuaire'
        ]
      ]
    ];

    return $build;

  }

}
