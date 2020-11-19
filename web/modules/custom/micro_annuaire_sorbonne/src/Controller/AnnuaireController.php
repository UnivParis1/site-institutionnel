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
   * @var \Drupal\micro_annuaire_sorbonne\WsGroupsService
   */
  private $annuaireService;

  public function __construct() {
    $this->annuaireService = \Drupal::service('micro_annuaire_sorbonne.annuaire');
  }

  private function getCachedUsers( $affiliation = NULL, $siteId = NULL ) {
    $cache = \Drupal::cache();

    if ($siteId) {
      $cachedUser = $cache->get('labeledURI_' . $siteId . '_' . $affiliation);
      if ($cachedUser) {
        $reponse = $cachedUser->data;
        $users = $reponse['users'];
      }
      else {
        $reponse = $this->annuaireService->getUserList($affiliation, $siteId);
        $users = $reponse['users'];
        $cache->set('labeledURI_' . $siteId . '_' . $affiliation, $reponse, time() + 60 * 60);
      }
    }
    else {
       $cachedUser = $cache->get('labeledURI_' . $affiliation);

      if ($cachedUser){
        $reponse = $cachedUser->data;
        $users = $reponse['users'];
      }
      else {
        $reponse = $this->annuaireService->getUserList($affiliation);
        $users = $reponse['users'];
        $cache->set('labeledURI_' . $affiliation, $reponse, time() + 60 * 60);
      }
    }

    return $users;
  }

  public function facultyList($letter) {
    $filtered_users = [];
    $currentSiteId = '';
    $sortedUsers = [];

    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();
      if (!empty($currentSiteId)) {
        $users = $this->getCachedUsers('faculty', $currentSiteId);
      }
    }
    else {
      $users = $this->getCachedUsers('faculty');
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

    $build['item_list'] = [
      '#theme' => 'micro_page_perso_filtree',
      '#users' => $filtered_users,
      '#affiliation' => 'faculty',
      '#Trusted' => FALSE,
      '#attached' => [
        'library' => [
          'micro_annuaire_sorbonne/annuaire'
        ]
      ]
    ];

    return $build;
  }

  public function studentList($letter) {
    $filtered_users = [];
    $sortedUsers = [];
    $currentSiteId = '';


    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();
      if (!empty($currentSiteId)) {
        $users = $this->getCachedUsers('student', $currentSiteId);
      }
    }
    else {
      $users = $this->getCachedUsers('student');
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

      $build['item_list'] = [
        '#theme' => 'micro_page_perso_filtree',
        '#users' => $sortedUsers,
        '#affiliation' => 'student',
        '#Trusted' => FALSE,
        '#attached' => [
          'library' => [
            'micro_annuaire_sorbonne/annuaire'
          ]
        ]
      ];

      return $build;
  }

  public function getPageTitle($affiliation) {
    return "Page personnelle $affiliation";
  }

  public function getPageLetterTitle($letter) {
    return ucfirst($letter);
  }
}
