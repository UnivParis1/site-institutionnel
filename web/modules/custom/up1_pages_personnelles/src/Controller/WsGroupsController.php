<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Database\Connection;

define("IMPORT_BATCH_SIZE", 150);
/**
 * Class WsGroupsController.
 */
class WsGroupsController extends ControllerBase {

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
                              Connection $dataservice = null) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->database = $dataservice;
    $this->wsGroupsService = \Drupal::service('up1_pages_personnelles.wsgroups');
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('up1_pages_personnelles.database'),
      $container->get('up1_pages_personnelles.wsgroups')
    );
  }

  private function getCachedUsers($affiliation = NULL, $siteId = NULL) {
    $cache = \Drupal::cache();

    if ($siteId) {
      $cachedUser = $cache->get('labeledURI_' . $siteId . '_' . $affiliation);
      if ($cachedUser) {
        $response = $cachedUser->data;
        $users = $response['users'];
      }
      else {
        $response = $this->wsGroupsService->getUserList($affiliation, $siteId);
        $users = $response['users'];
        $this->createPagePersoUsers($users);
        $cache->set('labeledURI_' . $siteId . '_' . $affiliation, $response, time() + 60 * 60);
      }
    }
    else {
      $cachedUser = $cache->get('labeledURI_' . $affiliation);

      if ($cachedUser) {
        $response = $cachedUser->data;
        $users = $response['users'];
      }
      else {
        $response = $this->wsGroupsService->getUserList($affiliation);
        $users = $response['users'];
        $this->createPagePersoUsers($users);
        $cache->set('labeledURI_' . $affiliation, $response, time() + 60 * 60);
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
      else {
        return FALSE;
      }
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

  /**
   * Liste des Enseignants-Chercheurs d'une structure/mini-site
   */
  public function microFacultyList($letter) {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getfieldEc()) {
      return $this->getList('faculty', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_faculty_list', $siteId);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  public function microStudentList($letter) {
    $siteId = $this->getSiteId();
    if (isset($siteId) && $this->getfieldDoc()) {
      return $this->getList('student', $letter, 'list_with_employee_type', 'up1_pages_personnelles.micro_student_list', $siteId);
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

  /**
   * Get Users from external source (wsgroups) and create a item queue for each user.
   * @return array
   */
  public function createPagePersoUsers() {
    \Drupal::logger('up1_pages_personnelles')->info("Entering in createPagePersoUsers function!");
    $faculty = $this->wsGroupsService->getUsers('faculty');
    $student = $this->wsGroupsService->getUsers('student');
    $data = array_merge($faculty, $student);
    \Drupal::logger('up1_pages_personnelles')->info("nb users to import : " . count($data));

    $queue = $this->queueFactory->get('up1_page_perso_queue');
    foreach ($data as $datum) {
      $queue->createItem($datum);
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('@count queue items have been created.', ['@count' => $queue->numberOfItems()]),
    ];
  }

  public function batchCreationOfUsers() {
    $batch = [
      'title' => $this->t('Process creating users from wsgroups'),
      'operations' => [],
      'finished' => '\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersFinished',
    ];
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('up1_page_perso_queue');

    for ($i = 0; $i < ceil($queue->numberOfItems() / IMPORT_BATCH_SIZE); $i++) {
      $batch['operations'][] = ['\Drupal\up1_pages_personnelles\Controller\WsGroupsController::batchUsersProcess', []];
    }
    batch_set($batch);

    return batch_process('<front');
  }

  public function batchUsersProcess(&$context){

    // We can't use here the Dependency Injection solution
    // so we load the necessary services in the other way
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    // Get the queue implementation for import_content_from_xml queue
    $queue = $queue_factory->get('up1_page_perso_queue');
    // Get the queue worker
    $queue_worker = $queue_manager->createInstance('up1_page_perso_queue');

    // Get the number of items
    $number_of_queue = ($queue->numberOfItems() < IMPORT_BATCH_SIZE) ? $queue->numberOfItems() : IMPORT_BATCH_SIZE;

    // Repeat $number_of_queue times
    for ($i = 0; $i < $number_of_queue; $i++) {
      // Get a queued item
      if ($item = $queue->claimItem()) {
        try {
          // Process it
          $queue_worker->processItem($item->data);
          // If everything was correct, delete the processed item from the queue
          $queue->deleteItem($item);
        }
        catch (SuspendQueueException $e) {
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
   */
  public static function batchUsersFinished($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t("The users have been successfully imported from Ws Groups."));
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
  }

  /**
   * Displays all new users to import.
   * @param $queue
   * @return array
   */
  protected function getItemList($queue) {
    $retrieved_items = [];
    $items = [];

    // Claim each item in queue.
    while ($item = $queue->claimItem()) {
      $retrieved_items[] = [
        'data' => [$item->data['uid'], $item->data['displayName']],
      ];
      // Track item to release the lock.
      $items[] = $item;
    }

    // Release claims on items in queue.
    foreach ($items as $item) {
      $queue->releaseItem($item);
    }

    // Put the items in a table array for rendering.
    $tableTheme = [
      'header' => [$this->t('username'), $this->t('Name')],
      'rows'   => $retrieved_items,
      'attributes' => [],
      'caption' => '',
      'colgroups' => [],
      'sticky' => TRUE,
      'empty' => $this->t('No items.'),
    ];

    return $tableTheme;
  }

  /**
   * Delete the queue 'up1_page_perso_queue'.
   *
   * Remember that the command drupal dq checks first for a queue worker
   * and if it exists, DC suposes that a queue exists.
   */
  public function deleteTheQueue() {
    $this->queueFactory->get('up1_page_perso_queue')->deleteQueue();
    $this->queueFactory->get('up1_typo3_data_queue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The queues "up1_page_perso_queue" & "up1_typo3_data_queue" have been deleted'),
    ];
  }

  public function populatePagePersoUsers() {
    $users = $this->wsGroupsService->getUsers('faculty');
    $users = reset($users);
    foreach ($users as $user) {
      $data[] = $this->selectFeUsers($user['uid']);
    }

    $header = [];
    $rows = [];
    $attributes = [];
    $sticky = [];
    $finalMessage = "";
    if (!$data) {
      \Drupal::logger('up1_typo3_data_queue')->warning('No new data to import.');
      $finalMessage = $this->t('No new data to import.');
    }
    else {
      $queue = $this->queueFactory->get('up1_typo3_data_queue');
      $totalItemsInQueue = $queue->numberOfItems();
      foreach ($data as $element) {
        $queue->createItem($element);
      }

      // 4. Get the total of item in the Queue.
      $totalItemsAfter = $queue->numberOfItems();

      // 5. Get what's in the queue now.
      $tableVariables = $this->getItemTypo3($queue);
      $header = $tableVariables['header'];
      $rows = $tableVariables['rows'];
      $attributes = $tableVariables['attributes'];
      $sticky = $tableVariables['sticky'];

      $finalMessage = $this->t('The Queue had @totalBefore items.
    We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
        [
          '@count' => count($data),
          '@totalAfter' => $totalItemsAfter,
          '@totalBefore' => $totalItemsInQueue,
        ]);

    }
    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => $header,
      '#rows' => isset($rows) ? $rows : [],
      '#attributes' => $attributes,
      '#sticky' => $sticky,
      '#empty' => $this->t('No items.'),
    ];
  }

  protected function getItemTypo3($queue) {
    $retrieved_items = [];
    $items = [];

    // Claim each item in queue.
    while ($item = $queue->claimItem()) {
      $retrieved_items[] = [
        'information' => [$item->data->username],
      ];
      // Track item to release the lock.
      $items[] = $item;
    }

    // Release claims on items in queue.
    foreach ($items as $item) {
      $queue->releaseItem($item);
    }

    // Put the items in a table array for rendering.
    $tableTheme = [
      'header' => [$this->t('username')],
      'rows'   => $retrieved_items,
      'attributes' => [],
      'caption' => '',
      'colgroups' => [],
      'sticky' => TRUE,
      'empty' => $this->t('No items.'),
    ];

    return $tableTheme;
  }

  /**
   * Delete the queue 'up1_typo3_data_queue'.
   *
   * Remember that the command drupal dq checks first for a queue worker
   * and if it exists, DC suposes that a queue exists.
   */
  public function deleteQueueTypo3() {
    $this->queueFactory->get('up1_typo3_data_queue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The queue "up1_typo3_data_queue" has been deleted'),
    ];
  }

  private function selectFeUsers($username) {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'username',
      'tx_oxcspagepersonnel_courriel',
      'tx_oxcspagepersonnel_responsabilites_scientifiques',
      'tx_oxcspagepersonnel_sujet_these',
      'tx_oxcspagepersonnel_projets_recherche',
      'tx_oxcspagepersonnel_directeur_these',
      //'tx_oxcspagepersonnel_publications',
      'tx_oxcspagepersonnel_epi',
      //'tx_oxcspagepersonnel_cv',
      //'tx_oxcspagepersonnel_cv2',
      'tx_oxcspagepersonnel_directions_these',
      'tx_oxcspagepersonnel_page_externe_url',
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $result = $query->execute()->fetchObject();

    return $result;
  }
}
