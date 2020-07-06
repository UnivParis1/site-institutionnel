<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AnnuaireController.
 */
class WsGroupsController extends ControllerBase {

  /**
   * @var \Drupal\up1_pages_personnelles\wsGroupsService
   */
  private $wsGroupsService;

  public function __construct() {
    $this->wsGroupsService = \Drupal::service('up1_pages_personnelles.wsgroups');
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
        $reponse = $this->wsGroupsService->getUserList($affiliation, $siteId);
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
        $reponse = $this->wsGroupsService->getUserList($affiliation);
        $users = $reponse['users'];
        $cache->set('labeledURI_' . $affiliation, $reponse, time() + 60 * 60);
      }
    }

    return $users;
  }

  private function getfieldEc() {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site = $negotiator->loadById($negotiator->getActiveId());

      return $site->get('ec_enabled')->value;
    }
  }
  private function getfieldDoc() {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site = $negotiator->loadById($negotiator->getActiveId());

      return $site->get('doc_enabled')->value;
    }
  }

  private function getSiteId() {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $siteId = $negotiator->getActiveId();
      if (!empty($siteId)) {
        return $siteId;
      }
      else return FALSE;
    }
  }

  public function getList($type, $letter, $theme, $path, $siteId = NULL) {
    $filtered_users = [];
    $sortedUsers = [];

    $users = $this->getCachedUsers($type, $siteId);
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
      '#theme' => $theme,
      '#users' => $filtered_users,
      '#affiliation' => $type,
      '#link' => $path,
      '#Trusted' => FALSE,
      '#attached' => [
        'library' => [
          'up1_pages_personnelles/liste'
        ]
      ]
    ];

    return $build;
  }

  public function masterFacultyList($letter) {
    return $this->getList('faculty', $letter, 'liste_pages_persos_filtree', 'up1_pages_personnelles.wsgroups_faculty_list');
  }

  public function masterStudentList($letter) {
    return $this->getList('student', $letter, 'liste_pages_persos_filtree', 'up1_pages_personnelles.wsgroups_student_list');
  }

  public function microFacultyList($letter) {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getfieldEc()) {
      return $this->getList('faculty', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_faculty_list',  $siteId);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  public function microStudentList($letter) {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getfieldDoc()) {
      return $this->getList('student', $letter,'list_with_employee_type', 'up1_pages_personnelles.micro_student_list', $siteId);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  public function getPageTitle($affiliation) {
    return "Page personnelle $affiliation";
  }

  public function getPageLetterTitle($letter) {
    return ucfirst($letter);
  }
}
