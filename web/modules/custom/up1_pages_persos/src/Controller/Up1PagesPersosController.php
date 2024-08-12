<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Url;
use Drupal\up1_pages_persos\Manager\PagePersoManager;
use Drupal\up1_webservices\Manager\WsGroupsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for UP1 Pages Persos routes.
 */
final class Up1PagesPersosController extends ControllerBase {

  public function __construct(
    private readonly WsGroupsManager $wsGroupsManager,
    private readonly PagePersoManager $pagePersoManager
  ) {}

  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('up1_webservices.wsgroups_manager'),
      $container->get('up1_pages_persos.pages_persos_manager')
    );
  }

  public function editPagePerso($username) {

    if ( $this->wsGroupsManager->hasPagePersoInWsGroups($username) ) {
      $page_perso = $this->pagePersoManager->getPagePersoWebsite($username);
      //Update page perso according to wsgroups data (supannCivilite,displayName,sn,givenName)

    }

  }

  /**
   * Exécute les requêtes de récupération
   * du user et de ses infos wsgroups.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleAutocomplete(Request $request) {
    $results = [];

    $input = $request->query->get('q');

    if ( !$input ) {
      return new JsonResponse($results);
    }

    $input = Xss::filter($input);

    $pages = $this->pagePersoManager->getPagesPersosAutocomplete($input);

    if ( !empty($pages) ) {
      foreach ($pages as $page) {
        $url = Url::fromRoute('entity.node.canonical',
          ['node' => $page->id()]
        )->toString();

        $title = $page->getTitle();
        $field_uid_ldap = $page->get('field_uid_ldap')->value;
        $user_attrs = $this->wsGroupsManager->getUserAttrs($field_uid_ldap, ['employeeType','supannEntiteAffectation','supannActivite','eduPersonPrimaryAffiliation,departmentNumber']);

        if ( !empty($user_attrs) ) {
          $label = "<span class='userItem'><span class='match'>$title</span>&nbsp;";
          if ($user_attrs['eduPersonPrimaryAffiliation'] == 'student' ) {
            $label .=
              "<span class='details'>" . implode(', ', $user_attrs['supannEntiteAffectation']) ."</span>";
          }
          else {
            $employeeType =  $user_attrs['employeeType'][0];
            $label .= "<span class='details'>$employeeType - "
              . implode(', ', $user_attrs['supannEntiteAffectation']);
            if ( !empty($user_attrs['supannActivite']) ) {
              $label .= " - " . substr($user_attrs['supannActivite'][0], 0, 50) . '...';
            }if ( !empty($user_attrs['departmentNumber']) ) {
              $label .= " - " . $user_attrs['departmentNumber'][0];
            }
            $label .=  "</span>";
          }
          $label .= "</span>";

          $results[] = [
            'value' => $title . " (" .$page->id() . ")",
            'label' => $label,
            'url' => $url
          ];

        }
      }
    }

    return new JsonResponse($results);
  }
}