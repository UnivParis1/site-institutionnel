<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AnnuaireController.
 */
class WsGroupsController extends ControllerBase {

  /**
   * @var \Drupal\up1_pages_personnelles\wsGroupsService
   */
  private $wsGroupsService;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;


  public function __construct(QueueFactory $queue, MessengerInterface $messenger,
                              ClientInterface $client) {
    $this->queueFactory = $queue;
    $this->messenger = $messenger;
    $this->client = $client;
    $this->wsGroupsService = \Drupal::service('up1_pages_personnelles.wsgroups');
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('up1_pages_personnelles.wsgroups')
    );
  }

  private function getCachedUsers($affiliation = NULL, $siteId = NULL) {
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
        $this->createPagePersoUsers($users);
        $cache->set('labeledURI_' . $siteId . '_' . $affiliation, $reponse, time() + 60 * 60);
      }
    }
    else {
      $cachedUser = $cache->get('labeledURI_' . $affiliation);

      if ($cachedUser) {
        $reponse = $cachedUser->data;
        $users = $reponse['users'];
      }
      else {
        $reponse = $this->wsGroupsService->getUserList($affiliation);
        $users = $reponse['users'];
        $this->createPagePersoUsers($users);
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

  public function createPagePersoUsers() {
    # [2020-09 PRI disable]
    return;

    $data = $this->wsGroupsService->getUsers('faculty');
    $header = [];
    $rows = [];
    $attributes = [];
    $sticky = [];
    $finalMessage = "";
    if (!$data) {
      \Drupal::logger('up1_page_perso_queue')->warning('No new data to import.');
      $finalMessage = $this->t('No new data to import.');
    }
    else {
      $data = reset($data);

      $queue = $this->queueFactory->get('up1_page_perso_queue');
      $totalItemsInQueue = $queue->numberOfItems();
      foreach ($data as $element) {
        $queue->createItem($element);
      }

      // 4. Get the total of item in the Queue.
      $totalItemsAfter = $queue->numberOfItems();

      // 5. Get what's in the queue now.
      $tableVariables = $this->getItemList($queue);
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
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The queue "up1_page_perso_queue" has been deleted'),
    ];
  }
}
