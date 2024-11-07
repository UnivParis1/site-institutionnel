<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\up1_pages_persos\Manager\PagePersoManager;
use Drupal\up1_webservices\Manager\WsGroupsManager;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
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
        $user_attrs = $this->wsGroupsManager->getUserAttrs($field_uid_ldap, [
          'employeeType',
          'supannEntiteAffectation',
          'supannActivite',
          'eduPersonPrimaryAffiliation,departmentNumber'
        ]);

        if (!empty($user_attrs)) {
          $label = "<span class='userItem'><span class='match'>$title</span>&nbsp;";

          isset($user_attrs['supannEntiteAffectation']) ?
            $entiteAffectation = "- " . is_array($user_attrs['supannEntiteAffectation']) ?
              implode(', ', $user_attrs['supannEntiteAffectation']) : $user_attrs['supannEntiteAffectation'] :
            $entiteAffectation = "- ";

          if ($user_attrs['eduPersonPrimaryAffiliation'] == 'student') {
            $label .=
              "<span class='details'>" . $this->t('Student') . " " . $entiteAffectation;
          }
          else {
            //Get EmployeeType or EduPersonAffiliation
            if (empty($user_attrs['employeeType'])) {
              $employeeType = $user_attrs['employeeType'][0];
            }
            else {
              switch ($user_attrs['eduPersonPrimaryAffiliation']) {
                case 'emeritus':
                  $employeeType = $this->t('Emeritus');
                  break;
                case 'researcher':
                  $employeeType = $this->t('Researcher');
                  break;
                default:
                  $employeeType = $this->t('Faculty');
                  break;
              }
            }

            if (isset($user_attrs['supannEntiteAffectation']) && is_array($user_attrs['supannEntiteAffectation'])) {
              $label .= "<span class='details'>$employeeType - "
                . implode(', ', $user_attrs['supannEntiteAffectation']);
            }
            else $label .= "<span class='details'>$employeeType - " . $user_attrs['supannEntiteAffectation'];

            if (!empty($user_attrs['supannActivite'])) {
              $label .= " - " . substr($user_attrs['supannActivite'][0], 0, 50) . '...';
            }
            if (!empty($user_attrs['departmentNumber'])) {
              $label .= " - " . $user_attrs['departmentNumber'][0];
            }
            $label .= "</span>";
          }
          $label .= "</span>";

          $results[] = [
            'value' => $title . " (" . $page->id() . ")",
            'label' => $label,
            'url' => $url,
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Récupère les champs relatifs à l'obsia
   * de la page personnelle d'un utilisateur donné.
   *
   * @param string $user
   *   Le nom d'utilisateur ou l'ID de l'utilisateur.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Les champs demandés sous forme de JSON.
   */
  public function getObsiaFields($user) {
    // Vérifier si l'utilisateur existe.
    if (!$this->pagePersoManager->is_student_or_teacher($user)) {
      return new JsonResponse([
        'error' => $this->t('User not found or is not student or teacher')
      ], 404);
    }

    $node = $this->pagePersoManager->getPagePersoWebsite($user);
    $node = reset($node);

    if (!empty($node) || $node) {
      $projets = $node->get('field_projects_ia')->getValue();
      $formations = $node->get('field_formations_ia')->getValue();
      $skills = $node->field_ia_skills->getValue();

      // Extraire les champs demandés.
      $fields = [
        'username' => $user,
        'bio' => $node->get('field_short_bio')->value,
        'projets' => !empty($projets) ? $projets[0]['value']: '',
        'formations' => !empty($formations) ? $formations[0]['value']: '',
        'skills' =>!empty($skills) ? $skills: '',
      ];
    }
    else {
      return new JsonResponse([
        'error' => $this->t(
          "Page personnelle not found for @username",
          ['@username' => $user])], 404);
    }
    // Retourner les champs sous forme de JSON.
    return new JsonResponse($fields);
  }

  public function updateObsiaFields($user, Request $request) {
    $field_short_bio = $request->query->get('bio');
    $field_projects_ia = $request->query->get('projets');
    $field_formations_ia = $request->query->get('formations');
    $field_ia_skills = $request->query->get('skills');

    if (!$this->pagePersoManager->is_student_or_teacher($user)) {
      return new JsonResponse([
        'error' => $this->t('User @username not found',
          ['@username' => $user])], 404);
    }

    $node = $this->pagePersoManager->getPagePersoWebsite($user);
    $node = reset($node);

    if ($node) {
      if (!empty($field_short_bio)) {
        $node->set('field_short_bio', $field_short_bio);
      }
      if (!empty($field_projects_ia)) {
        $node->set('field_projects_ia', $field_projects_ia);
      }
      if (!empty($field_formations_ia)) {
        $node->set('field_formations_ia', $field_formations_ia);
      }
      if (!empty($field_ia_skills)) {
        $node->set('field_ia_skills', explode(',', $field_ia_skills));
      }
      $node->save();

      // Retourner une réponse JSON de succès.
      return new JsonResponse([
        'success' => $this->t(
          'Page perso of @username updated successfully',
          ['@username' => $user])], 200);
    }
    else {
      return new JsonResponse([
        'error' => $this->t(
          "Page personnelle not found for @username",
          ['@username' => $user])], 404);
    }
  }

  public function deleteObsiaFields($user) {
    if (!$this->pagePersoManager->is_student_or_teacher($user)) {
      return new JsonResponse([
        'error' => $this->t('User @username not found',
          ['@username' => $user])], 404);
    }

    $node = $this->pagePersoManager->getPagePersoWebsite($user);
    $node = reset($node);

    if ($node) {
      $node->set('field_short_bio', NULL);
      $node->set('field_projects_ia', NULL);
      $node->set('field_formations_ia', NULL);
      $node->set('field_ia_skills', explode(',', ''));
      $node->save();

      // Retourner une réponse JSON de succès.
      return new JsonResponse([
        'success' => $this->t(
          'Page perso of @username updated successfully',
          ['@username' => $user])], 200);
    }
    else {
      return new JsonResponse([
        'error' => $this->t(
          "Page personnelle not found for @username",
          ['@username' => $user])], 404);
    }
  }

  /**
   * Récupère les noms des éléments de la liste à partir des clés sélectionnées.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Le node dont on veut récupérer les valeurs.
   * @param string $field_name
   *   Le nom du champ de type Liste (texte).
   *
   * @return array
   *   Un tableau contenant les noms des éléments de la liste.
   */
  private function getListFieldLabels(Node $node, $field_name) {
    $list_labels = [];

    // Récupérer les valeurs actuelles du champ.
    $field_values = $node->get($field_name)->getValue();

    // Obtenir la définition du champ pour accéder aux options.
    $field_definition = $node->getFieldDefinition($field_name);
    $allowed_values = $field_definition->getFieldStorageDefinition()->getSetting('allowed_values');

    // Parcourir les valeurs du champ et récupérer les noms des éléments.
    foreach ($field_values as $value) {
      if (!empty($value['value']) && isset($allowed_values[$value['value']])) {
        // Utiliser la clé pour obtenir le nom correspondant.
        $list_labels[] = $allowed_values[$value['value']];
      }
    }

    return $list_labels;
  }

  private function getAccountFromUser($user) {
    if (is_numeric($user)) {
      return User::load($user);
    } else {
      return user_load_by_name($user);
    }
  }

}
