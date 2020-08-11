<?php

namespace Drupal\up1_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\up1_data_import\Service\DataHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueFactory;

class DataController extends ControllerBase {

  /**
   * The news helper used to get settings from.
   *
   * @var \Drupal\up1_data_import\Service\DataHelper
   */
  protected $dataHelper;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queueFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;


  public function __construct(DataHelper $data_helper, QueueFactory $queue,
                              MessengerInterface $messenger, ClientInterface $client) {
    $this->dataHelper = $data_helper;
    $this->queueFactory = $queue;
    $this->messenger = $messenger;
    $this->client = $client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('data.helper'),
      $container->get('queue'),
      $container->get('messenger'),
      $container->get('http_client')
    );
  }

  public function getNewsList() {
    $data = $this->dataHelper->createNews2018Nodes();
    if (!$data) {
      \Drupal::logger('up1_data_import')->warning('No news nodes to import.');
      $finalMessage = $this->t('No news to import from old website.');
    }
    else {
      $queue = $this->queueFactory->get('up1_news_import_queue');
      $totalItems = $queue->numberOfItems();

      foreach ($data as $datum) {
        $queue->createItem($datum);
      }
      $totalItemsAfter = $queue->numberOfItems();

      // 5. Get what's in the queue now.
      $tableVariables = $this->getItemList($queue);

      $finalMessage = $this->t('We have @count items to import.
      @totalBefore in queue before. @totalAfter now in queue.',
        [
          '@count' => count($data),
          '@totalBefore' => $totalItems,
          '@totalAfter' => $totalItemsAfter,
        ]);
    }
    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => isset ($tableVariables['header']) ? $tableVariables['header'] : ['Old nid', 'Title'],
      '#rows' => isset($tableVariables['rows']) ? $tableVariables['rows'] : [],
      '#attributes' => [],
      '#sticky' => TRUE,
      '#empty' => $this->t('No items.'),
    ];
  }

  public function getEventList() {
    $data = $this->dataHelper->createEventNodes();
    if (!$data) {
      \Drupal::logger('up1_data_import')->warning('No event nodes to import.');
      $finalMessage = $this->t('No events to import from old website.');
    }
    else {
      $queue = $this->queueFactory->get('up1_event_import_queue');
      $totalItems = $queue->numberOfItems();

      foreach ($data as $datum) {
        $queue->createItem($datum);
      }
      $totalItemsAfter = $queue->numberOfItems();

      // 5. Get what's in the queue now.
      $tableVariables = $this->getItemList($queue);

      $finalMessage = $this->t('We have @count items to import.
      @totalBefore in queue before. @totalAfter now in queue.',
        [
          '@count' => count($data),
          '@totalBefore' => $totalItems,
          '@totalAfter' => $totalItemsAfter,
        ]);
    }
    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => isset ($tableVariables['header']) ? $tableVariables['header'] : ['Old nid', 'Title'],
      '#rows' => isset($tableVariables['rows']) ? $tableVariables['rows'] : [],
      '#attributes' => [],
      '#sticky' => TRUE,
      '#empty' => $this->t('No items.'),
    ];
  }

  /**
   * Delete the queues 'up1_news_import_queue' & 'up1_event_import_queue'.
   *
   * Remember that the command drupal dq checks first for a queue worker
   * and if it exists, DC supposes that a queue exists.
   */
  public function deleteQueues() {
    $this->queueFactory->get('up1_news_import_queue')->deleteQueue();
    $this->queueFactory->get('up1_event_import_queue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('All queues have been deleted'),
    ];
  }

  protected function getItemList($queue) {
    $retrieved_items = [];
    $items = [];

    // Claim each item in queue.
    while ($item = $queue->claimItem()) {
      $retrieved_items[] = [
        'data' => [$item->data['nid'], $item->data['title']],
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
      'header' => [$this->t('old nid'), $this->t('Title')],
      'rows'   => $retrieved_items,
      'attributes' => [],
      'caption' => '',
      'colgroups' => [],
      'sticky' => TRUE,
      'empty' => $this->t('No items.'),
    ];

    return $tableTheme;
  }
}
