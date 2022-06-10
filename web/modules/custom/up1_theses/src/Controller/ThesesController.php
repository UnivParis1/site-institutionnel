<?php
namespace Drupal\up1_theses\Controller;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\Entity\Node;
use Drupal\up1_theses\Service\ThesesHelper;
use Drupal\up1_theses\Service\ThesesService;

class ThesesController extends ControllerBase {

  /**
   * The theses helper used to get settings from.
   *
   * @var ThesesHelper
   */
  protected $thesesHelper;

  /**
   * @var ThesesService
   */
  protected $thesesService;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var MessengerInterface
   */
  protected $messenger;
  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var ContainerAwareInterface
   */
  protected $queueFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var ClientInterface
   */
  protected $client;
  /**
   *
   */

  /**
   * Inject services.
   *
   * @param ThesesService $theses_service
   * @param ThesesHelper $theses_helper
   * @param QueueFactory $queue
   * @param MessengerInterface $messenger
   * @param ClientInterface $client
   */
  public function __construct(ThesesService $theses_service, ThesesHelper $theses_helper,
                              QueueFactory $queue, MessengerInterface $messenger, ClientInterface $client) {
    $this->thesesService = $theses_service;
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
      $container->get('theses.service'),
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
    $data = $this->thesesHelper->formatDataFromJson();
    $new_these_queue = $this->queueFactory->get('up1_theses_import_queue');
    $updated_these_queue = $this->queueFactory->get('up1_theses_updates_queue');
    $vivas = [];

    if (!$data) {
      \Drupal::logger('up1_theses')->info('No new viva to import from APOGÉE.');
      $message = $this->t('No new viva to import from APOGÉE.');
    }
    else {
      $existingTheses = $this->thesesService->getExistingTheses();
      foreach ($data as $element) {
        $vivas[] = [
          'code' => $element['cod_ths'],
          'title' => $element['title'],
        ];
        if (in_array($element['cod_ths'], $existingTheses)) {
          $query = \Drupal::database()->select('up1_theses_import', 't')
            ->fields('t', ['nid'])
            ->condition('cod_ths', $element['cod_ths']);
          $value = $query->execute()->fetchCol();
          if (!empty($value) && isset($value[0])) {
            $element['nid'] = $value[0];
            $updated_these_queue->createItem($element);
          }
        } else {
          $new_these_queue->createItem($element);
        }
      }
    }

    return new JsonResponse([
      'data' => [
        'new_theses' => $new_these_queue->numberOfItems(),
        'updated_theses' => $updated_these_queue->numberOfItems(),
        'message' => $this->t('@new theses created. @updated theses updated. ',
          [
            '@new' => $new_these_queue->numberOfItems(),
            '@updated' => $updated_these_queue->numberOfItems()
          ]),
        'vivas' => $vivas
      ],
      'method' => 'GET',
      'status'=> 200
    ]);
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
   *
   * Deletes the queue 'up1_theses_queue_import'.
   * @return JsonResponse
   */
  public function deleteTheQueue() {
    $this->queueFactory->get('up1_theses_import_queue')->deleteQueue();
    $this->queueFactory->get('up1_theses_updates_queue')->deleteQueue();

    return new JsonResponse([
      'data' => ['message' => $this->t('Queues "up1_theses_import_queue" & "up1_theses_updates_queue" have been deleted')],
      'method' => 'GET',
      'status' => 200
    ]);
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
