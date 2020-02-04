<?php

namespace Drupal\micro_annuaire_sorbonne\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;


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
   * Retourne une listre triée par ordre alphabétique et filtrée en fonction de la lettre
   * de l'alphabet demandée.
   * Ou la liste de tous les utilisateurs si on est sur un mini site
   *
   * @param $letter lettre de l'alphabet dont on veut la page d'annuaire
   * @return render array
   *   Contenant la liste des utilisateurs sous forme de tableau (JSON_decode).
   */
  public function list($letter) {
    $cache = \Drupal::cache();
    $filtered_users = [];
    $currentSiteId = '';
    $theme = '';

    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();

      if (!empty($currentSiteId)) {
        $theme = 'annuaire_mini_site';
        // On verifie si l'annuaire est dans le cache
        $cachedUser = $cache->get('users_ldap'.$currentSiteId);

        if ($cachedUser) {
          $reponse = $cachedUser->data;
          $filtered_users = $reponse['users'];
        }
        else {
          $reponse = $this->annuaireService->getUserList($currentSiteId);
          $filtered_users = $reponse['users'];
          $cache->set('users_ldap'.$currentSiteId, $reponse, time() + 60 * 60);
        }

        if (!empty($filtered_users)){
          // on trie les utilisateurs par ordre alphabetique des cn
          $sortedUsers = usort($filtered_users, function ($a, $b) {
            return strnatcasecmp($a['sn'], $b['sn']);
          });
        }



      }
//      dump($users);
    }
    else {
      $theme = 'micro_annuaire_sorbonne';
      $cachedUser = $cache->get('users_ldap_principal');

      if ($cachedUser){
        $reponse = $cachedUser->data;
        $users = $reponse['users'];
      }
      else {
        $reponse = $this->annuaireService->getUserList('');
        $users = $reponse['users'];
        $cache->set('users_ldap_principal', $reponse, time() + 60 * 60);
      }

      if (!empty($users)) {
        foreach ($users as $user) {
          if (strcasecmp(substr($user['sn'], 0, 1), $letter) == 0) {
            $filtered_users[] = $user;
          }
        }

        // on trie les utilisateurs par ordre alphabetique des cn
        $sortedUsers = usort($filtered_users, function ($a, $b) {
          return strnatcasecmp($a['sn'], $b['sn']);
        });



      }
    }

    $build['item_list'] = [
      '#theme' => $theme,
      '#users' => $filtered_users,
      '#Trusted' => FALSE,
      '#attached' => [
        'library' => [
          'micro_annuaire_sorbonne/annuaire'
        ]
      ]
    ];

    return $build;

  }

}
