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
   *
   * @param $letter lettre de l'alphabet dont on veut la page d'annuaire
   * @return render array
   *   Contenant la liste des utilisateurs sous forme de tableau (JSON_decode).
   */
  public function list($letter) {
    $cache = \Drupal::cache();
    $filtered_users = [];
    $currentSiteId = '';

    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();

      $users = [];
      if (!empty($currentSiteId)) {
        // On verifie si l'annuaire est dans le cache
        $cachedUser = $cache->get('users_ldap');
        if ($cachedUser) {
          $users = $cachedUser->data;
        }
        else {
          $users = $this->annuaireService->getUserList($currentSiteId);
          $cache->set('users_ldap', $users, time() + 60 * 60);
        }
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
      '#theme' => 'micro_annuaire_sorbonne',
      '#users' => $filtered_users,
      '#site' => $currentSiteId,
      '#Trusted' => (\Drupal::config('micro_annuaire_sorbonne.annuaireconfig')->get('type_de_recherche') == 'searchUserTrusted?' ? TRUE:FALSE),
      '#attached' => [
        'library' => [
          'micro_annuaire_sorbonne/annuaire'
        ]
      ]
    ];

    return $build;

  }

}
