<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasUserManager;
use Drupal\up1_pages_personnelles\ComptexManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Class WsGroupsController.
 */
class WsGroupsController extends ControllerBase
{
  /**
   * Max number of items in a batch if not defined.
   *
   * @var int
   */
  const IMPORT_USER_SIZE = 50;

  /**
   * @var wsGroupsService
   */
  private $wsGroupsService;

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var QueueWorkerManager
   */
  protected $queueManager;

  /**
   * @param QueueFactory $queue
   * @param QueueWorkerManager $queue_manager
   */
  public function __construct(QueueFactory $queue, QueueWorkerManager $queue_manager)
  {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->wsGroupsService = \Drupal::service('up1_pages_personnelles.wsgroups');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('up1_pages_personnelles.wsgroups')
    );
  }

  public function getCurrentSite() {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site = $negotiator->loadById($negotiator->getActiveId());
    }

    return $site;
  }

  private function getCachedUsers($affiliation = NULL, $siteId = NULL, $group = NULL, $trombi_settings = NULL) {
    $cache = \Drupal::cache();

    if ($siteId) {
      $cachedUser = $cache->get('labeledURI_' . $siteId . '_' . $affiliation);
      if ($cachedUser) {
        $response = $cachedUser->data;
        $users = $response['users'];
      }
      else {
        $consent = $this->wsGroupsService->getSiteField($siteId, 'supannConsentement');
        switch ($group) {
          case 'observatoireIA':
          case 'sante-shs':
            $response = $this->wsGroupsService->getUserListWithConsent($consent, $siteId, $trombi_settings) ;
            break;
          default:
            $response = $this->wsGroupsService->getUserList($affiliation, $siteId, $trombi_settings);
        }


        $users = $response['users'];
        $cache->set('labeledURI_' . $siteId . '_' . $affiliation, $response, time() + 60 * 60);
      }
    } else {
      $cachedUser = $cache->get('labeledURI_' . $affiliation);

      if ($cachedUser) {
        $response = $cachedUser->data;
        $users = $response['users'];
      } else {
        $response = $this->wsGroupsService->getUserList($affiliation);
        $users = $response['users'];
        $cache->set('labeledURI_' . $affiliation, $response, time() + 60 * 60);
      }
    }

    return $users;
  }

  /**
   * Get value of ec_enabled to see if ec annuaire is enable.
   * @return int
   */
  private function getFieldEc() {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site = $negotiator->loadById($negotiator->getActiveId());

      return $site->get('ec_enabled')->value;
    } else return FALSE;
  }

  /**
   * Get value of ec_trombi_enable to see if trombinoscope is the main display.
   * @return int
   */
  private function getFieldTrombiEc()
  {
    $site = $this->getCurrentSite();
    return $site->get('trombi_ec_enable')->value;
  }

  private function getFieldTrombiStudents()
  {
    $site = $this->getCurrentSite();
    return $site->get('trombi_students_enable')->value;
  }

  private function getTrombiFields() {
    $trombi_fields = [];
    if ($this->getFieldTrombiEc()) {
      $site = $this->getCurrentSite();

      $trombi_fields = [
        'supannEntite_pedagogy' => $site->get('supannEntite_pedagogy')->value,
        'supannEntite_research' => $site->get('supannEntite_research')->value,
        'discipline_enseignement' => $site->get('discipline_enseignement')->value,
        'skills_lists' => $site->get('skills_lists')->value,
        'supannRole' => $site->get('supannRole')->value,
        'about_me' => $site->get('about_me')->value,
      ];
    }
    if ($this->getFieldTrombiStudents()) {
      $trombi_fields = [
        'supannEntite_doctoralSchool' => 1,
        'supannEntite_research' => 1,
      ];
    }

    return $trombi_fields;
  }

  private function getFieldDoc()
  {
    $site = $this->getCurrentSite();

    return $site->get('doc_enabled')->value;
  }

  private function getSiteId()
  {
    /** @var $negotiator  SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $siteId = $negotiator->getActiveId();
      if (!empty($siteId)) {
        return $siteId;
      } else return FALSE;
    } else return FALSE;
  }

  /**
   * @param $theme
   * @param $path
   * @param $siteId
   * @return array
   */
  public function getTrombiList($theme, $path, $group, $siteId = NULL) {
    $site_settings = $this->getTrombiFields();
    $users = $this->getCachedUsers('faculty', $siteId, $group, $site_settings);
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $user_photo = $config->get('url_userphoto');

    foreach ($users as &$user) {
      if ($group == 'observatoireIA') {
        $user['skills'] = $this->formatTrombiData('skillsIA', $user, $site_settings);
        $user['about'] = $this->formatTrombiData('aboutIA', $user, $site_settings);
      }
      else {
        $user['skills'] = $this->formatTrombiData('skills', $user, $site_settings);
        $user['about'] = $this->formatTrombiData('about', $user, $site_settings);
      }
      $user['research'] = $this->formatTrombiData('research', $user, $site_settings);
      $user['pedagogy'] = $this->formatTrombiData('pedagogy', $user, $site_settings);
      $user['role'] = $this->formatTrombiData('role', $user, $site_settings);
      $user['discipline'] = $this->formatTrombiData('discipline', $user, $site_settings);
      $user['photo'] = $user_photo . $user['uid'];
    }

    usort($users, function ($a, $b) {
      return strnatcasecmp($a['sn'], $b['sn']);
    });

    $build['item_list'] = [
      '#theme' => $theme,
      '#users' => $users,
      '#affiliation' => 'faculty',
      '#link' => $path,
      '#Trusted' => FALSE,
      '#trombi_settings' => [],
      '#attached' => [
        'library' => [
          'up1_pages_personnelles/trombi'
        ]
      ]
    ];

    return $build;
  }

  public function getStudentsTrombiList($theme, $path, $group, $siteId = NULL) {
    $users = $this->getCachedUsers('student', $siteId, $group);
    $site_settings = $this->getTrombiFields();
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $user_photo = $config->get('url_userphoto');

    foreach ($users as &$user) {
      $user['doctoralSchool'] = $this->formatTrombiData('doctoralSchool', $user, $site_settings);
      $user['research'] = $this->formatTrombiData('research', $user, $site_settings);
      $user['photo'] = $user_photo . $user['uid'];
    }

    usort($users, function ($a, $b) {
      return strnatcasecmp($a['sn'], $b['sn']);
    });

    $build['item_list'] = [
      '#theme' => $theme,
      '#users' => $users,
      '#affiliation' => 'student',
      '#link' => $path,
      '#Trusted' => FALSE,
      '#trombi_settings' => [],
      '#attached' => [
        'library' => [
          'up1_pages_personnelles/trombi'
        ]
      ]
    ];

    return $build;
  }

  public function getList($type, $letter, $theme, $path, $siteId = NULL)
  {
    $filtered_users = [];

    $users = $this->getCachedUsers($type, $siteId);
    if (!empty($users)) {
      foreach ($users as $user) {
        if (strcasecmp(substr($user['sn'], 0, 1), $letter) == 0) {
          $filtered_users[] = $user;
        }
      }

      // on trie les utilisateurs par ordre alphabetique des cn
      usort($filtered_users, function ($a, $b) {
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

  public function masterFacultyList($letter)
  {
    return $this->getList('faculty', $letter, 'liste_pages_persos_filtree', 'up1_pages_personnelles.wsgroups_faculty_list');
  }

  public function masterStudentList($letter)
  {
    return $this->getList('student', $letter, 'liste_pages_persos_filtree', 'up1_pages_personnelles.wsgroups_student_list');
  }

  /**
   * Liste des Enseignants-Chercheurs d'une structure/mini-site
   */
  public function microFacultyList($letter = NULL)
  {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getFieldEc()) {
      $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
      $site = $siteStorage->load($siteId);
      $group = $site->get('groups')->value;

      if ($this->getFieldTrombiEc()) {
        return $this->getTrombiList('list_as_trombinoscope', 'up1_pages_personnelles.micro_faculty_list', $group, $siteId);
      } else {
        return $this->getList('faculty', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_faculty_list', $siteId);
      }
    } else {
      throw new NotFoundHttpException();
    }
  }

  public function microStudentList($letter)
  {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getFieldDoc()) {
      $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
      $site = $siteStorage->load($siteId);
      $group = $site->get('groups')->value;
      if ($this->getFieldTrombiStudents()) {
        return $this->getStudentsTrombiList('list_as_trombinoscope', 'up1_pages_personnelles.micro_student_list', $group, $siteId);
      } else {
        return $this->getList('student', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_student_list', $siteId);
      }
    } else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * @param string $affiliation
   * @return string
   *
   */
  public function getPageTitle($affiliation) {
    return "Pages personnelles $affiliation";
  }

  /**
   * @param string $letter
   * @return string
   */
  public function getPageLetterTitle($letter)
  {
    return ucfirst($letter);
  }

  /**
   * Get Users from external source (wsgroups) and create a item queue for each user.
   * @return JsonResponse
   */
  public function createPagePersoUsers() {
    $users_ws_groups = $this->wsGroupsService->getAllUsers();
    $queue_user_node = $this->queueFactory->get('up1_page_perso_queue');
    $user_more_than_one_pp = [];
    $user_with_unpublished_pp = [];
    $user_without_pp = [];
    $user_does_not_exists = [];

    foreach ($users_ws_groups as $user_ws_groups) {
      $name = $user_ws_groups['uid'];
      $user = user_load_by_name($name);
      //ECD does not exists. We have to create both user & node page perso.
      if (!$user) {
        $user_does_not_exists[] = $name;
        $queue_user_node->createItem($user_ws_groups);
      }
      else {
        $author = $user->id();
        $values = \Drupal::entityQuery('node')
          ->condition('type', 'page_personnelle')
          ->condition('uid', $author)
          ->accessCheck(FALSE)
          ->execute();
        if (empty($values)) {
          //ECD exists but doesn't have page perso. We just create his pp.
          $user_without_pp[] = $name;
          $page_perso = Node::create([
            'title' => $user_ws_groups['supannCivilite'] . ' ' . $user_ws_groups['displayName'],
            'type' => 'page_personnelle',
            'langcode' => 'fr',
            'uid' => $user->id(),
            'status' => 1,
            'field_uid_ldap' => $user_ws_groups['uid'],
            'site_id' => NULL,
          ]);

          $page_perso->save();
        }
        else {
          $nb_pages_persos = count($values);
          switch ($nb_pages_persos) {
            case $nb_pages_persos > 1:
              //ECD has more than one page perso. We report it and do nothing more.
              $user_more_than_one_pp[] = $name;
              \Drupal::logger('createPagePersoUsers')->error("$name has more than one page perso. ");
              break;
            case $nb_pages_persos == 1 :
              $node = Node::load(reset($values));
              if (!empty($node)) {
                if (!empty($node) && $node->status->getString() == 0) {
                  //page perso has been unpublished. We publish it.
                  $user_with_unpublished_pp[] = $name;
                  $node->setPublished();
                  $node->save();
                }
              }
              break;
          }
        }
      }
    }

    return new JsonResponse([
      'data' => [
        'user_more_than_one_pp' => !empty($user_more_than_one_pp) ? implode(', ',$user_more_than_one_pp) : '',
        'user_with_unpublished_pp' => !empty($user_with_unpublished_pp) ? implode(', ',$user_with_unpublished_pp) : '',
        'user_without_pp' => !empty($user_without_pp) ? implode(', ',$user_without_pp) : '',
        'user_does_not_exists' => !empty($user_does_not_exists) ? implode(', ',$user_does_not_exists) : '',
      ],
      'method' => 'GET',
      'status'=> 200
    ]);
  }

  public function batchCreationOfUsers() {
    $batch = [
      'title' => $this->t('Process creating users from wsgroups'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_page_perso_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / self::IMPORT_USER_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersProcess', []];
    }
    batch_set($batch);

    return batch_process('<front>');
  }

  /**
   * Batch users process.
   * @param $context
   */
  public static function batchUsersProcess(&$context){

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_page_perso_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_page_perso_queue');

    // Get the number of items
    $number_of_queue = ($queue->numberOfItems() < self::IMPORT_USER_SIZE) ? $queue->numberOfItems() : self::IMPORT_USER_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_queue; $i++) {
      // Get a queued item
      if ($item = $queue->claimItem()) {
        try {
          // Process it
          $queue_worker->processItem($item->data);
          // If everything was correct, delete the processed item from the queue
          $queue->deleteItem($item);
        } catch (SuspendQueueException $e) {
          // If there was an Exception trown because of an error
          // Releases the item that the worker could not process.
          // Another worker can come and process it
          $queue->releaseItem($item);
          break;
        }
      }
    }
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchUsersFinished($success, $results, $operations)
  {
    if ($success) {
      \Drupal::messenger()->addStatus(t("The users have been successfully imported from Ws Groups."));
    } else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Delete 'up1_page_perso_queue' & 'up1_page_perso_node_creation_queue' queues.
   */
  public function deletePagePersoQueue() {
    $this->queueFactory->get('up1_page_perso_queue')->deleteQueue();
    $this->queueFactory->get('up1_page_perso_node_creation_queue')->deleteQueue();

    return new JsonResponse([
      'data' => ['message' => "Queues up1_page_perso_queue & up1_page_perso_node_creation_queue deleted."],
      'method' => 'GET',
      'status' => 200
    ]);
  }

  public function editPagePerso($username) {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $maintenance = $config->get('activate_maintenance');
    if ($maintenance) {
      $response = new RedirectResponse("https://majtrantor.univ-paris1.fr/miseajourpageperso.html");
      return $response->send();
    }
    else {
      $comptex = new ComptexManager();
      $user = user_load_by_name($username);
      \Drupal::logger('editPagePerso')->info("$username trying to edit page perso from comptex.");
      if ($user && $comptex->userHasPagePerso($username)) {
        $user->addRole('enseignant_doctorant');
        $user->save();
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'page_personnelle')
          ->accessCheck(FALSE)
          ->condition('uid', $user->id());
        $result = $query->execute();
        if (!empty($result)) {
          if (count($result) == 1) {
            //On remet le role enseignant_doctorant à l'utilisateur. Au cas où celui-ci aurait été bloqué / démis de son rôle.
            $nid = reset($result);
            $node = Node::load($nid);
            //On est sûr que le user a une page perso. On republie sa page avant qu'il y accède en modification.
            $node->setPublished();
            $node->save();

            $goto = "/node/$nid/edit";
          }
          else {
            $nodes = Node::loadMultiple($result);
            $prob = [];
            foreach ($nodes as $node) {
              $prob[] = "nid " . $node->id() . ", URL : " . \Drupal::service('path_alias.manager')
                  ->getAliasByPath('/node/' . $node->id() . ', activée ? ' . $node->isPublished());
            }
            \Drupal::logger('page_perso_comptex')->error(implode("\n", $prob));
            $goto = "/node/38866";
          }
        }
        else {
          $comptex = new ComptexManager();
          $item = $comptex->getUserAttributes($username, ['supannCivilite','displayName']);

          $node = Node::create([
            'title' => $item['supannCivilite'] . ' ' . $item['displayName'],
            'type' => 'page_personnelle',
            'langcode' => 'fr',
            'uid' => $user->id(),
            'status' => 1,
            'field_uid_ldap' => $item['uid'],
            'site_id' => NULL,
          ]);
          $node->save();
          $goto = "/node/" . $node->id() . '/edit';
        }
      }
      else {
        $comptex = new ComptexManager();
        $item = $comptex->getUserAttributes($username, [
          'supannCivilite',
          'displayName',
          'mail'
        ]);

        $cas_settings = \Drupal::config('cas.settings');
        $cas_user_manager = \Drupal::service('cas.user_manager');
        $user_properties = [
          'roles' => ['enseignant_doctorant'],
        ];
        $email_assignment_strategy = $cas_settings->get('user_accounts.email_assignment_strategy');
        if ($email_assignment_strategy === CasUserManager::EMAIL_ASSIGNMENT_STANDARD) {
          $user_properties['mail'] = $item['mail'];
        }
        try {
          // function register() : This has to be changed if cas module evolves.
          $user = $cas_user_manager->register($item['uid']);
          $node = Node::create([
            'title' => $item['supannCivilite'] . ' ' . $item['displayName'],
            'type' => 'page_personnelle',
            'langcode' => 'fr',
            'uid' => $user->id(),
            'status' => 1,
            'field_uid_ldap' => $item['uid'],
            'site_id' => NULL,
          ]);
          $node->save();
          $goto = "/node/" . $node->id() . '/edit';
        } catch (CasLoginException $e) {
          \Drupal::logger('cas')->error('CasLoginException when
        registering user with name %name: %e', [
            '%name' => $item['uid'],
            '%e' => $e->getMessage()
          ]);
          $goto = "https://majtrantor.univ-paris1.fr/miseajourpageperso.html";
        }
      }

      $response = new RedirectResponse($goto);
      return $response->send();
    }
  }

  public function equipeDispatch()
  {
    $siteId = $this->getSiteId();
    if (isset($siteId)) {
      if ($this->getFieldEc()) {
        return $this->microFacultyList('A');
      } else if ($this->getFieldDoc()) {
        return $this->microStudentList('A');
      } else {
        throw new NotFoundHttpException();
      }
    } else {
      throw new NotFoundHttpException();
    }
  }

  public function getPageEquipeTitle() {
    $siteId = $this->getSiteId();
    if (isset($siteId)) {
      if ($this->getFieldEc()) {
        return "Pages personnelles enseignants-chercheurs";
      } else if ($this->getFieldDoc()) {
        return "Pages personnelles doctorants";
      } else {
        throw new NotFoundHttpException();
      }
    } else {
      throw new NotFoundHttpException();
    }
  }

  private function formatTrombiData($data_to_get, $user, $settings) {
    $drupal_user = user_load_by_name($user['uid']);
    $result = '';
    $pp = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['uid' => $drupal_user->id(), 'type' => 'page_personnelle']);
    $page_perso = reset($pp);

    if ($page_perso) {
      switch ($data_to_get) {
        case 'skills' :
          if ($settings['skills_lists']) {
            if (!empty($terms = $page_perso->get('field_skills')->referencedEntities())) {
              foreach ($terms as $term) {
                $result .= '<li>' . $term->getName() . '</li>';
              }
            }

          }
          break;
        case 'skillsIA' :
          if ($settings['skills_lists']) {
            $ia_skills = $page_perso->get('field_ia_skills')->getString();
            $all_skills = $page_perso->get('field_ia_skills')->getSetting('allowed_values');
            if (!empty($ia_skills)) {
              $selected_skills = explode(', ', $ia_skills);
              $result_skills = [];
              foreach ($selected_skills as $a_skill) {
                $result_skills[] = '<li>' . $all_skills[$a_skill] . '</li>';
              }
              $result = implode('', $result_skills);
            }

          }
          break;
        case 'aboutIA' :
          if ($settings['about_me'] ) {
            $result = (!empty($page_perso->get('field_short_bio')->value)) ? $page_perso->get('field_short_bio')->value : '';

          }
          break;
        case 'about' :
          if ($settings['about_me']) {
            $result = (!empty($page_perso->get('field_about_me')->value)) ? $page_perso->get('field_about_me')->value : '';
          }
          break;
        case 'research' :
          if ($settings['supannEntite_research'] == 1) {
            $affectation = $user['supannEntiteAffectation-all'];
            $key_search = array_filter($affectation, function ($item) {
              return $item['businessCategory'] == 'research';
            });
            if (!empty($key_search)) {
              $key_search = reset($key_search);
              $result = $key_search[0]['description'];
            }
          }
          break;
        case 'pedagogy' :
          if ($settings['supannEntite_pedagogy'] == 1) {
            $affectation = $user['supannEntiteAffectation-all'];
            $key_search = array_filter($affectation, function ($item) {
              return $item['businessCategory'] == 'pedagogy';
            });
            if (!empty($key_search)) {
              $key_search = reset($key_search);
              $result = $key_search['description'];
            }
          }
          break;
        case 'doctoralSchool' :
          if ($settings['supannEntite_doctoralSchool'] == 1) {
            $affectation = $user['supannEntiteAffectation-all'];
            $key_search = array_filter($affectation, function ($item) {
              return $item['businessCategory'] == 'doctoralSchool';
            });
            if (!empty($key_search)) {
              $key_search = reset($key_search);
              $result = $key_search['description'];
            }
          }
          break;
        case 'role' :
          if ($settings['supannRole'] == 1) {
            if (!empty($user['supannRoleEntite-all'])) {
              $role = $user['supannRoleEntite-all'][0];
              $result = $role['role'] . ' ' . $role['structure']['description'];
            }
          }
          break;
        case 'discipline' :
          if ($settings['discipline_enseignement'] == 1) {
            if (!empty($user['info'])) {
              $result = implode(', ', $user['info']);
            }
          }
          break;
        default:
          break;
      }
    }

    return $result;
  }

  /**
   * Get data from Drupal to display it on Comptex page
   * @param $username
   * @return mixed
   */
  public function getParcoursObsia($username) {
    if (!$this->maintenancePagePersos()) {
      return new JsonResponse([ 'data' => $this->getObsiaFields($username), 'method' => 'GET', 'status'=> 200]);
    }
    else {
      return new JsonResponse([
        'data' => t('Pages personnelles are not available for modifications. Please try later.'),
        'method' => 'GET',
        'status' => 200
      ]);
    }
  }

  /**
   * Set obsia data from Comptex in page_personnelle node.
   * @param $username
   * @return JsonResponse
   */
  public function setParcoursObsia($username) {
    if (!$this->maintenancePagePersos()) {
      $data = [];

      $data['bio'] = \Drupal::request()->query->get('bio');
      $data['formations'] = \Drupal::request()->query->get('formations');
      $data['projets'] = \Drupal::request()->query->get('projets');
      $data['skills'] = \Drupal::request()->query->get('skills');

      $message = $this->updateObsiaFields($username, $data);
    }
    else {
      $message = t('Pages personnelles are not available for modifications. Please try later.');
    }
    return new JsonResponse([
      'data' => [ 'username' => $username, 'message' => $message ],
      'method' => 'GET',
      'status'=> 200
    ]);
  }

  /**
   * Delete Obsia data if "obsia checkbox" uncheck in Comptex and save page_personnelle node.
   * @param $username
   * @return JsonResponse
   */
  public function deleteParcoursObsia($username) {
    if (!$this->maintenancePagePersos()) {
      $message = $this->updateObsiaFields($username);
    }
    else {
      $message = t('Pages personnelles are not available for modifications. Please try later.');
    }
    return new JsonResponse([
      'data' => [ 'username' => $username, 'message' => $message ],
      'method' => 'GET',
      'status'=> 200
    ]);
  }

  public function syncLdap() {
    $count_disabled = 0;
    $disabled_users = [];
    $users_ws_groups = $this->wsGroupsService->getAllUsers();

    $ids = \Drupal::entityQuery('user')
      ->condition('roles', 'enseignant_doctorant')
      ->execute();
    $users = User::loadMultiple($ids);

    if (!empty($users_ws_groups) && !empty($users)) {
      foreach($users as $user) {
        //If Drupal User doesn't exist in ldap, we disable his page_perso.
        $name = $user->get('name')->value;
        if (array_search($user->get('name')->value, array_column($users_ws_groups, 'uid')) === false) {
          $query = \Drupal::entityQuery('node')
            ->condition('type', 'page_personnelle')
            ->condition('uid', $user->id());
          $result = $query->execute();
          //The request must retrieve a unique page perso. But due to previous mistakes, we will disable all pages persos.
          if (!empty($result)) {
            foreach ($result as $key => $nid) {
              $page_perso = Node::load($nid);
              $page_perso->setUnpublished();
              $page_perso->save();
            }
            $count_disabled++;
            $disabled_users[] = $user->get('name')->value;
          }
        }
      }
    }

    return new JsonResponse([
      'data' => ['nb_disabled_pages' => $count_disabled, 'users_name' => implode(', ', $disabled_users)],
      'method' => 'GET',
      'status'=> 200
    ]);
  }

  /**
   * Update fields related to obsia in user's page personelle.
   * @param $username
   * @param $fields
   * @return string
   */
  private function updateObsiaFields($username, $fields = []) {
    $user = user_load_by_name($username);

    if ($user) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $user->id());
      $result = $query->execute();
      if (!empty($result) && count($result) == 1) {
        $nid = reset($result);
        $page_perso = Node::load($nid);
        if (!empty($fields)) {
          if (!empty($fields['bio'])) $page_perso->set('field_short_bio', $fields['bio']);
          if (!empty($fields['formations'])) $page_perso->set('field_formations_ia', $fields['formations']);
          if (!empty($fields['projets'])) $page_perso->set('field_projects_ia', $fields['projets']);
          if (!empty($fields['skills'])) $page_perso->set('field_ia_skills', explode(',', $fields['skills']));
        }
        else {
          $page_perso->set('field_short_bio', NULL);
          $page_perso->set('field_formations_ia', NULL);
          $page_perso->set('field_projects_ia', NULL);
          $page_perso->set('field_ia_skills',  NULL);
        }

        if ($page_perso->save()) {
          $message = t('The page perso has been successfully updated.');
        }
        else {
          $message = t('An error occurred while updating the page perso.');
        }
      }
      else {
        $message = t('%username doesn\'t have page personnelle', ['%username' => $username]);
      }
    }
    else {
      $message = t('No user with the username %username exists here. ', ['%username' => $username]);
    }
    return $message;
  }

  /**
   * Get fields related to obsia in page perso node for a username.
   * @param $username
   * @return array
   */
  private function getObsiaFields($username) {
    $user = user_load_by_name($username);
    $fields = [];
    if ($user) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $user->id());
      $nids = $query->execute();
      if ($nids) {
        foreach ($nids as $nid) {
          $page_perso = Node::load($nid);
          $fields[] = [
            'username' => $username,
            'bio' => $page_perso->field_short_bio->getValue(),
            'formations' => $page_perso->field_formations_ia->getValue(),
            'projets' => $page_perso->field_projects_ia->getValue(),
            'skills' => $page_perso->field_ia_skills->getValue()
          ];
        }
      }
    }

    return $fields;
  }

  /**
   * Check if maintenance mode is activated or not.
   * @return RedirectResponse|FALSE;
   */
  private function maintenancePagePersos() {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $maintenance = $config->get('activate_maintenance');
    if ($maintenance) {
      $response = new RedirectResponse("https://majtrantor.univ-paris1.fr/miseajourpageperso.html");
      return $response->send();
    }
    else return FALSE;
  }
}

