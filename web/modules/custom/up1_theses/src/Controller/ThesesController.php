<?php

namespace Drupal\up1_theses\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\Entity\Node;
use Drupal\up1_theses\Service\ThesesHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ThesesController extends ControllerBase {

  /**
   * The theses helper used to get settings from.
   *
   * @var \Drupal\up1_theses\Service\ThesesHelper
   */
  protected $thesesHelper;
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
  /**
   *
   */

  /**
   * Inject services.
   *
   * @param \Drupal\up1_theses\Service\ThesesHelper $theses_helper
   * @param \Drupal\Core\Queue\QueueFactory $queue
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \GuzzleHttp\ClientInterface $client
   */
  public function __construct(ThesesHelper $theses_helper, QueueFactory $queue,
       MessengerInterface $messenger, ClientInterface $client) {
    $this->thesesHelper = $theses_helper;
    $this->queueFactory = $queue;
    $this->messenger = $messenger;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theses.helper'),
      $container->get('queue'),
      $container->get('messenger'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getThesesList() {
    \Drupal::logger('node_load')->info(print_r(Node::load(414), 1));
    $data = $this->thesesHelper->formatDataFromJson();
    if (!$data) {
      \Drupal::logger('up1_theses')->warning('No new data to import.');
    }
    else {
      $queue = $this->queueFactory->get('up1_theses_queue_import');
      $totalItemsInQueue = $queue->numberOfItems();

      foreach ($data as $element) {
        $queue->createItem($element);
      }
      // 4. Get the total of item in the Queue.
      $totalItemsAfter = $queue->numberOfItems();

      // 5. Get what's in the queue now.
      $tableVariables = $this->getItemList($queue);

      $finalMessage = $this->t('The Queue had @totalBefore items. 
    We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
        [
          '@count' => count($data),
          '@totalAfter' => $totalItemsAfter,
          '@totalBefore' => $totalItemsInQueue,
        ]);

      return [
        '#type' => 'table',
        '#caption' => $finalMessage,
        '#header' => $tableVariables['header'],
        '#rows' => isset($tableVariables['rows']) ? $tableVariables['rows'] : [],
        '#attributes' => $tableVariables['attributes'],
        '#sticky' => $tableVariables['sticky'],
        '#empty' => $this->t('No items.'),
      ];
    }
  }

  /**
   * Transform Json data to array.
   *
   * return void
   */
  public function transformJsonDataToArray() {
    try {
      $json = file_get_contents($this->thesesService->getWebServiceUrl());
      $dataArray = json_decode($json, TRUE);
      if (!empty($dataArray)) {
        return $dataArray;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('up1_theses', $e);
    }

  }

  /**
   * Delete the queue 'up1_theses_queue_import'.
   *
   * Remember that the command drupal dq checks first for a queue worker
   * and if it exists, DC suposes that a queue exists.
   */
  public function deleteTheQueue() {
    $this->queueFactory->get('up1_theses_queue_import')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The queue "up1_theses_queue_import" has been deleted'),
    ];
  }

  protected function getItemList($queue) {
    $retrieved_items = [];
    $items = [];

    // Claim each item in queue.
    while ($item = $queue->claimItem()) {
      $retrieved_items[] = [
        'data' => [$item->data['title'], $item->data['cod_ths'], $item->item_id],
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
      'header' => [$this->t('Title'), $this->t('COD_THS'), $this->t('ID')],
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
