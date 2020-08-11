<?php

namespace Drupal\up1_data_import\Service;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media\Entity\Media;

class DataHelper {

  /**
   * The theses service.
   *
   * @var \Drupal\up1_theses\Service\ThesesService
   */
  protected $dataService;

  /**
   * Constructs a MyClass object.
   *
   * @param \Drupal\up1_data_import\Service\DataService
   */
  public function __construct(DataService $import_service) {
    $this->dataService = $import_service;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\up1_data_import\Service\DataHelper
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('data_news.service')
    );
  }

  /**
   * Transform Json data to array.
   *
   * return void
   */
  public function newsJsonToArray() {
    try {
      $json = file_get_contents($this->dataService->getNewsUrl());
      $dataArray = json_decode($json, TRUE);
      if (!empty($dataArray)) {
        return $dataArray;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('up1_news_import', $e);
    }
  }

  /**
   * Transform Json data to array.
   *
   * return void
   */
  public function eventJsonToArray() {
    try {
      $json = file_get_contents($this->dataService->getEventsUrl());
      $dataArray = json_decode($json, TRUE);
      if (!empty($dataArray)) {
        return $dataArray;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('up1_event_import', $e);
    }
  }

  /**
   * @return array
   */
  public function getExistingNodes() {
    $query = \Drupal::database()->select('up1_data_import', 'd')
      ->fields('d', ['old_uuid']);

    return $query->execute()->fetchCol();
  }

  /**
   * Create file then media
   *
   * @param $url_image
   *
   * @return object $media
   */
  public function createMedia($url_image, $uid, $bundle = 'news') {
    $media = [];

    $file_data = file_get_contents("https://pantheonsorbonne.fr$url_image");
    $filename = preg_split('/\//', $url_image);
    if ($bundle == 'event') {
      $file_date = $filename[4];
      $file_name = $filename[5];
    }
    else {
      $file_date = $filename[5];
      $file_name = $filename[6];
    }

    $file = file_save_data($file_data, "public://$file_date/$file_name",
      FileSystemInterface::EXISTS_REPLACE);

    if ($file) {
      $media = Media::create([
        'bundle'           => 'image',
        'uid'              => $uid,
        'field_media_image' => $file,
      ]);

      $media->setName($file_name)->setPublished(TRUE)->save();
    }

    return $media;
  }

  public function getTaxonomyTerm($string, $vocabulary = NULL) {
    $terms = [];
    $old_terms = explode('|', $string);
    foreach ($old_terms as $old_term) {
      $taxonomyEntity = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term');
      $old_term_entity = $taxonomyEntity->loadByProperties([
        'name' => $old_term,
        'vid' => $vocabulary
      ]);
      if (isset($old_term_entity) && !empty($old_term_entity)) {
        $term = reset($old_term_entity);
        $terms[] = $term->id();
      }
    }
    return $terms;
  }

  /**
   * Create nodes news2018 from json.
   */
  public function createNews2018Nodes() {
    $new_nodes = [];
    $data = $this->newsJsonToArray();

    if (!empty($data)) {
      foreach ($data as $key => $old_node) {
        $uuid = $old_node['uuid'];
        $ok = FALSE;
        $existingNodes = $this->getExistingNodes();

        if (!empty($new_nodes)) {
          foreach ($new_nodes as $new_node) {
            if ($uuid == $new_node['uuid']) {
              $ok = TRUE;
              break;
            }
          }
        }

        if (!$ok && !in_array($uuid, $existingNodes)) {
          $uuid = $old_node['uuid'];
          $uid = $this->dataService->getAuthorUid($old_node['name']);
          $categories = !empty($old_node['field_categorie_news']) ?
            $this->getTaxonomyTerm($old_node['field_categorie_news'], 'categories') : [];

          $topics = !empty($old_node['field_type_actualite']) ?
            $this->getTaxonomyTerm($old_node['field_type_actualite'], 'news_topics') : [];

          $media = $this->createMedia($old_node['field_image'], $uid);

          $temp_node = [
            'nid' => $old_node['nid'],
            'uuid' => $uuid,
            'title' => $old_node['title'],
            'type' => 'news2018',
            'langcode' => 'fr',
            'uid' => $uid,
            'status' => 1,
            'field_categories' => $categories,
            'field_lead' => $old_node['field_accroche'],
            'field_label_color' => $old_node['field_couleur_slide'],
            'field_news_date' => $old_node['field_date_de_l_evenement'],
            'body' => [
              'value' => $old_node['body'],
              'format' => 'full_html'
            ],
            'field_news_topic' => $topics,
          ];
          if (!empty($media)) {
            $temp_node['field_media'] = $media;
          }
          $new_nodes[] = $temp_node;
        }
      }
    }
    return $new_nodes;

  }

  /**
   * Create nodes event from json.
   */
  public function createEventNodes() {
    $events = [];
    $data = $this->eventJsonToArray();

    if (!empty($data)) {
      foreach ($data as $key => $old_event) {
        $uuid = $old_event['uuid'];
        $ok = FALSE;
        $existingNodes = $this->getExistingNodes();

        if (!empty($events)) {
          foreach ($events as $event) {
            if ($uuid == $event['uuid']) {
              $ok = TRUE;
              break;
            }
          }
        }

        if (!$ok && !in_array($uuid, $existingNodes)) {
          $uuid = $old_event['uuid'];
          $uid = $this->dataService->getAuthorUid($old_event['name']);
          $categories = !empty($old_event['field_categorie_news']) ?
            $this->getTaxonomyTerm($old_event['field_categorie_news'], 'categories') : [];

          $event_type = !empty($old_event['field_type_evenement']) ?
            $this->getTaxonomyTerm($old_event['field_type_evenement'], 'events_types') : [];

          $media = $this->createMedia($old_event['field_image'], $uid, 'event');

          $temp_node = [
            'nid' => $old_event['nid'],
            'uuid' => $uuid,
            'title' => $old_event['title'],
            'type' => 'event',
            'langcode' => $old_event['langcode'],
            'created' => $old_event['created'],
            'uid' => $uid,
            'status' => 1,
            'field_event_address' => $old_event['field_texte_adresse'],
            'field_categories' => $categories,
            'field_address_map' => [
              'lat' => $old_event['lat'],
              'lon' => $old_event['long'],
            ],
            'field_event_date' => [
              [
                'value' => $old_event['field_date_debut_evenement'],
                'end_value' => $old_event['field_date_fin_evenement']
              ]
            ],
            'field_subscription_link' => $old_event['field_lien_inscription'],
            'body' => [
              'value' => $old_event['body'],
              'format' => 'full_html'
            ],
            'field_event_type' => $event_type,
          ];
          if (!empty($media)) {
            $temp_node['field_media'] = $media;
          }
          $events[] = $temp_node;
        }
      }
    }
    return $events;

  }
}
