<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\micro_site\Entity\Site;

define("IMPORT_USER_SIZE", 150);
define("IMPORT_DATA_SIZE", 50);
/**
 * Class WsGroupsController.
 */
class WsGroupsController extends ControllerBase
{

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
   * @var Connection
   */
  protected $database;

  public function __construct(QueueFactory $queue, QueueWorkerManager $queue_manager,
                              Connection   $dataservice = null)
  {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->database = $dataservice;
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
      $container->get('up1_pages_personnelles.database'),
      $container->get('up1_pages_personnelles.wsgroups')
    );
  }

  public function getCurrentSite()
  {
    /** @var $negotiator  \Drupal\micro_site\SiteNegotiatorInterface */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site = $negotiator->loadById($negotiator->getActiveId());
    }

    return $site;
  }

  private function getCachedUsers($affiliation = NULL, $siteId = NULL, $trombi_settings = NULL)
  {
    $cache = \Drupal::cache();

    if ($siteId) {
      $cachedUser = $cache->get('labeledURI_' . $siteId . '_' . $affiliation);
      if ($cachedUser) {
        $response = $cachedUser->data;
        $users = $response['users'];
      } else {
        $response = $this->wsGroupsService->getUserList($affiliation, $siteId, $trombi_settings);
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
  private function getFieldEc()
  {
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
  public function getTrombiList($theme, $path, $siteId = NULL) {
    $site_settings = $this->getTrombiFields();
    $users = $this->getCachedUsers('faculty', $siteId, $site_settings);
    foreach ($users as $user) {
      $skills = $this->formatTrombiData('skills', $user, $site_settings);
      $about = $this->formatTrombiData('about', $user, $site_settings);
      $pedagogy = $this->formatTrombiData('pedagogy', $user, $site_settings);
      $research = $this->formatTrombiData('research', $user, $site_settings);
    }
    $build['item_list'] = [
      '#theme' => $theme,
      '#users' => $users,
      '#affiliation' => 'faculty',
      '#link' => $path,
      '#Trusted' => FALSE,
      '#trombi_settings' => [],
      '#pedagogy' => $pedagogy,
      '#research' => $research,
      '#skills' => $skills,
      '#about_me' => $about,
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
      if ($this->getFieldTrombiEc()) {
        return $this->getTrombiList('list_as_trombinoscope', 'up1_pages_personnelles.micro_faculty_list', $siteId);
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
      return $this->getList('student', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_student_list', $siteId);
    } else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * @param string $affiliation
   * @return string
   *
   */
  public function getPageTitle($affiliation)
  {
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
   * @return array
   */
  public function createPagePersoUsers()
  {
    $users = $this->wsGroupsService->getAllUsers();
    $cas_user_manager = \Drupal::service('cas.user_manager');

    $queue = $this->queueFactory->get('up1_page_perso_queue');

    foreach ($users as $user) {
      $cas_username = $user['uid'];
      $author = $cas_user_manager->getUidForCasUsername($cas_username);
      if (!$author) {
        $queue->createItem($user);
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchCreationOfUsers()
  {
    $batch = [
      'title' => $this->t('Process creating users from wsgroups'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_page_perso_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_USER_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersProcess', []];
    }
    batch_set($batch);

    return batch_process('<front>');
  }

  /**
   * Batch users process.
   * @param $context
   */
  public static function batchUsersProcess(&$context)
  {

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_page_perso_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_page_perso_queue');

    // Get the number of items
    $number_of_queue = ($queue->numberOfItems() < IMPORT_USER_SIZE) ? $queue->numberOfItems() : IMPORT_USER_SIZE;

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
   * Get all Typo3 fields group by user and create queue items.
   *
   * @return array
   */
  public function populatePagePersoUsers()
  {
    $users = $this->wsGroupsService->getAllUsers();

    //Select all Typo3 fields by user.
    foreach ($users as $user) {
      $data[] = $this->selectFeUsers($user['uid']);
    }

    $queue = $this->queueFactory->get('up1_typo3_data_queue');

    //Charge queue items.
    foreach ($data as $datum) {
      $queue->createItem($datum);
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchPopulatePagePerso()
  {
    $batch = [
      'title' => $this->t('Process populating pages persos with Typo3 data'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchPagesPersosFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_typo3_data_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_DATA_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchPagesPersosProcess', []];
    }
    batch_set($batch);

    return batch_process('<front>');
  }

  /**
   * Batch Pages persos process.
   * @param $context
   */
  public function batchPagesPersosProcess(&$context)
  {

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_typo3_data_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_typo3_data_queue');

    // Get the number of items
    $number_of_items = ($queue->numberOfItems() < IMPORT_DATA_SIZE) ? $queue->numberOfItems() : IMPORT_DATA_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_items; $i++) {
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
   * Batch finished callback.
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchPagesPersosFinished($success, $results, $operations)
  {
    if ($success) {
      \Drupal::messenger()->addStatus(t("The Typo3 data haved been successfully imported from Typo3 database."));
    } else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Get Typo3 publications field create queue items.
   *
   * @return array
   */
  public function importPublications()
  {
    $users = $this->wsGroupsService->getAllUsers();

    //Select all Typo3 fields by user.
    foreach ($users as $user) {
      $publications = $this->selectPublications($user['uid']);
      if ($publications) {
        $data[] = $publications;
      }
    }

    $queue = $this->queueFactory->get('up1_typo3_publications_queue');

    //Charge queue items.
    foreach ($data as $datum) {
      $queue->createItem($datum);
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchImportPublications()
  {
    $batch = [
      'title' => $this->t('Process pages persos publications field with Typo3'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchPublicationsFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_typo3_publications_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_DATA_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchPublicationsProcess', []];
    }
    batch_set($batch);

    return batch_process('<front>');
  }

  /**
   * Batch publications process.
   * @param $context
   */
  public static function batchPublicationsProcess(&$context)
  {

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_typo3_publications_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_typo3_publications_queue');

    // Get the number of items
    $number_of_items = ($queue->numberOfItems() < IMPORT_DATA_SIZE) ? $queue->numberOfItems() : IMPORT_DATA_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_items; $i++) {
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
   * Batch finished callback.
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchPublicationsFinished($success, $results, $operations)
  {
    if ($success) {
      \Drupal::messenger()->addStatus(t("The Typo3 publications haved been successfully imported from Typo3 database."));
    } else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Get Typo3 publications field create queue items.
   *
   * @return array
   */
  public function importResume()
  {
    $users = $this->wsGroupsService->getAllUsers();

    //Select all Typo3 fields by user.
    foreach ($users as $user) {
      $resume = $this->selectResume($user['uid']);
      if ($resume) {
        $data[] = $resume;
      }
    }

    $queue = $this->queueFactory->get('up1_typo3_resume_queue');

    //Charge queue items.
    foreach ($data as $datum) {
      $queue->createItem($datum);
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchImportResume()
  {
    $batch = [
      'title' => $this->t('Process pages persos resume text field with Typo3'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchResumeFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_typo3_resume_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_DATA_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchResumeProcess', []];
    }
    batch_set($batch);

    return batch_process('<front>');
  }

  /**
   * Batch publications process.
   * @param $context
   */
  public static function batchResumeProcess(&$context)
  {

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_typo3_resume_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_typo3_resume_queue');

    // Get the number of items
    $number_of_items = ($queue->numberOfItems() < IMPORT_DATA_SIZE) ? $queue->numberOfItems() : IMPORT_DATA_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_items; $i++) {
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
   * Batch finished callback.
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchResumeFinished($success, $results, $operations)
  {
    if ($success) {
      \Drupal::messenger()->addStatus(t("The Typo3 resume text haved been successfully imported from Typo3 database."));
    } else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Get Typo3 english resume & education field create queue items.
   *
   * @return array
   */
  public function importLastFields()
  {
    $users = $this->wsGroupsService->getAllUsers();

    //Select all Typo3 fields by user.
    foreach ($users as $user) {
      $epi_education = $this->selectEpiAndEducation($user['uid']);
      if ($epi_education) {
        $data[] = $epi_education;
      }

    }

    $queue = $this->queueFactory->get('up1_typo3_last_fields_queue');

    //Charge queue items.
    foreach ($data as $datum) {
      $queue->createItem($datum);
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchImportLastFields()
  {
    $batch = [
      'title' => $this->t('Process pages persos english resume & education field with Typo3'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::LastFieldsFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_typo3_last_fields_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_DATA_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::LastFieldsProcess', []];
    }
    batch_set($batch);

    return batch_process('/admin/content/pages-persos');
  }

  /**
   * Batch publications process.
   * @param $context
   */
  public static function batchLastFieldsProcess(&$context)
  {

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_typo3_last_fields_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_typo3_last_fields_queue');

    // Get the number of items
    $number_of_items = ($queue->numberOfItems() < IMPORT_DATA_SIZE) ? $queue->numberOfItems() : IMPORT_DATA_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_items; $i++) {
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
   * Batch finished callback.
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchLastFieldsFinished($success, $results, $operations)
  {
    if ($success) {
      \Drupal::messenger()->addStatus(t("The Typo3 english resume & education field haved been successfully imported from Typo3 database."));
    } else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Delete queues 'up1_page_perso_queue', 'up1_typo3_data_queue', 'up1_typo3_resume_queue', 'up1_typo3_publications_queue' & 'up1_typo3_last_fields_queue'.
   */
  public function deleteTheQueue()
  {
    $this->queueFactory->get('up1_page_perso_queue')->deleteQueue();
    $this->queueFactory->get('up1_typo3_data_queue')->deleteQueue();
    $this->queueFactory->get('up1_typo3_publications_queue')->deleteQueue();
    $this->queueFactory->get('up1_typo3_resume_queue')->deleteQueue();
    $this->queueFactory->get('up1_typo3_last_fields_queue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('All Typo3 queues have been deleted'),
    ];
  }

  /**
   * Delete queue 'up1_page_perso_queue'.
   */
  public function deletePagePersoQueue()
  {
    $this->queueFactory->get('up1_page_perso_queue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Up1 Page Perso queue has been deleted'),
    ];
  }

  public function editPagePerso($username)
  {
    $user = user_load_by_name($username);

    if ($user) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $user->id());
      $result = $query->execute();
      if (!empty($result) && count($result) == 1) {
        $user->addRole('enseignant_doctorant');
        $user->save();
        $nid = reset($result);
        $goto = "/node/$nid/edit";
      } else {
        $goto = '<front>';
      }

      $response = new RedirectResponse($goto);
      return $response->send();
    }
  }

  private function selectFeUsers($username)
  {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_courriel',
      'tx_oxcspagepersonnel_responsabilites_scientifiques',
      'tx_oxcspagepersonnel_sujet_these',
      'tx_oxcspagepersonnel_projets_recherche',
      'tx_oxcspagepersonnel_directeur_these',
      'tx_oxcspagepersonnel_epi',
      'tx_oxcspagepersonnel_cv',
      'tx_oxcspagepersonnel_directions_these',
      'tx_oxcspagepersonnel_page_externe_url',
      'tx_oxcspagepersonnel_themes_recherche',
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $result = $query->execute()->fetchObject();

    return $result;
  }

  private function selectPublications($username)
  {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_publications',
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $query->condition('tx_oxcspagepersonnel_publications', '', '<>');
    $result = $query->execute()->fetchObject();

    return $result;
  }

  private function selectResume($username)
  {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_cv2',
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $query->condition('tx_oxcspagepersonnel_cv2', '', '<>');
    $result = $query->execute()->fetchObject();

    return $result;
  }

  /**
   * @param $username
   * @return mixed
   */
  private function selectEpiAndEducation($username)
  {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_epi',
      'tx_oxcspagepersonnel_anglais'
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');

    $orGroup = $query->orConditionGroup()
      ->condition('tx_oxcspagepersonnel_epi', '', '<>')
      ->condition('tx_oxcspagepersonnel_anglais', '', '<>');

    $query->condition($orGroup);

    $result = $query->execute()->fetchObject();

    return $result;
  }

  public function createMissingPagePerso($username)
  {
    return $this->selectUserData($username);
  }

  private function selectUserData($username)
  {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_courriel',
      'tx_oxcspagepersonnel_responsabilites_scientifiques',
      'tx_oxcspagepersonnel_sujet_these',
      'tx_oxcspagepersonnel_projets_recherche',
      'tx_oxcspagepersonnel_directeur_these',
      'tx_oxcspagepersonnel_epi',
      'tx_oxcspagepersonnel_cv',
      'tx_oxcspagepersonnel_directions_these',
      'tx_oxcspagepersonnel_page_externe_url',
      'tx_oxcspagepersonnel_themes_recherche',
      'tx_oxcspagepersonnel_publications',
      'tx_oxcspagepersonnel_cv2'
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $result = $query->execute()->fetchObject();

    return $result;
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
    switch ($data_to_get) {
      case 'skills' :
        if ($settings['skills_lists'] && $drupal_user) {
          $pp = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['uid' => $drupal_user->id(), 'type' => 'page_personnelle']);
          $page_perso = reset($pp);
          if ($page_perso) {
            $result = (!empty($page_perso->get('field_skills')->value)) ? $page_perso->get('field_skills')->value : '';
          }
        }
        break;
      case 'about' :
        if ($settings['about_me'] && $drupal_user) {
          $pp = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['uid' => $drupal_user->id(), 'type' => 'page_personnelle']);
          $page_perso = reset($pp);
          if ($page_perso) {
            $result = (!empty($page_perso->get('field_about_me')->value)) ? $page_perso->get('field_about_me')->value : '';
          }
        }
        break;
      case 'research' :
        \Drupal::logger('case_research')->info(print_r($settings, 1));
        if ($settings['supannEntite_research'] == 1) {
          $affectation = $user['supannEntiteAffectation-all'];
          $result = $this->formatSupannEntites('research', $affectation, 'businessCategory');
          \Drupal::logger('case_research_result')->info(print_r($result, 1));
        }
        break;
      case 'pedagogy' :
        \Drupal::logger('case_pedagogy')->info(print_r($settings, 1));
        if ($settings['supannEntite_pedagogy'] == 1) {
          $affectation = $user['supannEntiteAffectation-all'];
          $result = $this->formatSupannEntites('pedagogy', $affectation, 'businessCategory');
          \Drupal::logger('case_pedagogy_result')->info(print_r($result, 1));
        }
        break;
      case 'roles' :
      default:
        break;
    }
    return $result;
  }

  private function formatSupannEntites($key, $data, $column) {
    $key_search = array_search($key, array_column($data, $column));
    \Drupal::logger('case_research')->info(print_r($key_search, 1));
    \Drupal::logger("format_supannEntity_$key")->info(print_r($data[$key_search], 1));
    $formated_data = '';
    if ($key_search && !empty($data[$key_search]['labeledURI'])) {
      $formated_data = "<p class='trombi-affectation'><a href='" . $data[$key_search]['labeledURI'] . "' title='" .
        $data[$key_search]['description'] . "' target='_blank'>"
        . $data[$key_search]['description'] . "</a></p>";
    } else if ($key_search && empty($data[$key_search]['labeledURI'])) {
      $formated_data = "<p>" . $data[$key_search]['description'] . "</p>";
    }

    return $formated_data;
  }
}

