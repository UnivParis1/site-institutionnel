<?php

namespace Drupal\micro_annuaire_sorbonne\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;


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
   * Retourne une liste triée par ordre alphabétique et filtrée en fonction de la lettre
   * de l'alphabet demandée.
   * Ou la liste de tous les utilisateurs si on est sur un mini site
   *
   * @param $letter "lettre de l'alphabet dont on veut la page d'annuaire"
   *
   * @return array
   *   Contenant la liste des utilisateurs sous forme de tableau (JSON_decode).
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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

        if (!empty($filtered_users)) {
          $distinct_users = [];
          // on trie les utilisateurs par ordre alphabetique des cn
          $sortedUsers = usort($filtered_users, function ($a, $b) {
            return strnatcasecmp($a['sn'], $b['sn']);
          });
          foreach ($filtered_users as $filtered_user) {
            if ($filtered_user['eduPersonPrimaryAffiliation'] == 'student') {
              $distinct_users['student'][] = $filtered_user;
            }
            else {
              $distinct_users['teacher'][] = $filtered_user;
            }
          }
        }
      }
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

        $distinct_users = [];
        // on trie les utilisateurs par ordre alphabetique des cn
        $sortedUsers = usort($filtered_users, function ($a, $b) {
          return strnatcasecmp($a['sn'], $b['sn']);
        });
        foreach ($filtered_users as $filtered_user) {
          if ($filtered_user['eduPersonPrimaryAffiliation'] == 'student') {
            $distinct_users['student'][] = $filtered_user;
          }
          else {
            $distinct_users['teacher'][] = $filtered_user;
          }
        }

      }
    }

    $build['item_list'] = [
      '#theme' => $theme,
      '#users' => $distinct_users,
      '#students' => $distinct_users['student'],
      '#teachers' => $distinct_users['teacher'],
      '#Trusted' => FALSE,
      '#attached' => [
        'library' => [
          'micro_annuaire_sorbonne/annuaire'
        ]
      ]
    ];

    return $build;

  }

  /**
   * Returns a filtered and ordered list depending on the user's affiliation
   * @param $affiliation
   * @param $letter
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function filteredList($affiliation, $letter) {
    $cache = \Drupal::cache();
    $filtered_users = [];

    if ($affiliation == 'doctorant') {
      $fonction = "student";
    }
    elseif ($affiliation == "enseignant-chercheur") {
      $fonction = "teacher";
    }

    $cachedUser = $cache->get('users_ldap_principal');

    if ($cachedUser) {
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

      $distinct_users = [];
      // on trie les utilisateurs par ordre alphabetique des cn
      $sortedUsers = usort($filtered_users, function ($a, $b) {
        return strnatcasecmp($a['sn'], $b['sn']);
      });
      foreach ($filtered_users as $filtered_user) {
        if ($fonction == "student") {
          if ($filtered_user['eduPersonPrimaryAffiliation'] == 'student') {
            $distinct_users[] = $filtered_user;
          }
        }
        if ($fonction == "teacher") {
          if ($filtered_user['eduPersonPrimaryAffiliation'] != 'student') {
            $distinct_users[] = $filtered_user;
          }
        }
      }

      $build['item_list'] = [
        '#theme' => 'micro_page_perso_filtree',
        '#users' => $distinct_users,
        '#affiliation' => $affiliation,
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

  public function getPageTitle($affiliation) {
    return "Page personnelle $affiliation";
  }

  public function getPageLetterTitle($letter) {
    return ucfirst($letter);
  }
}
