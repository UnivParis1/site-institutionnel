<?php

namespace Drupal\sorbonne_tv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Class SorbonneTvSharelinksController.
 */
class SorbonneTvSharelinksController extends ControllerBase
{
  public function add($nid) {
    $favorites = isset($_COOKIE['sorbonnefavorites']) ? json_decode($_COOKIE['sorbonnefavorites'], TRUE) : [];
    if (!in_array($nid, $favorites)) {
      $favorites[] = $nid;
      setcookie('sorbonnefavorites', json_encode($favorites), time() + (86400 * 365), '/'); // 1 year expiration.
      $message = t('Video added to favorites.');

      $action = 'add';
    }
    else {
      $key = array_search($nid, $favorites);
      unset($favorites[$key]);
      setcookie('sorbonnefavorites', json_encode($favorites), time() + (86400 * 365), '/'); // 1 year expiration.
      $message = t('Video removed from favorites.');

      $action = 'remove';
    }
    return new JsonResponse(['status' => 'success', 'action' => $action, 'message' => $message]);
  }

  public function list() {
    $list_render = [];

    $favorites = isset($_COOKIE['sorbonnefavorites']) ? json_decode($_COOKIE['sorbonnefavorites'], TRUE) : [];

    $nodes = Node::loadMultiple($favorites);

    if($nodes && count($nodes) > 0) {
      $items = [];
      $prg_title_str = '<h2 class="bullet-title"><span class="title-txt">'. t('Ma liste') .'</span><span class="title-bullet"></span></h2>';

      $output = '<ul>';
      foreach ($nodes as $node) {
        $nid = $node->id();

        $entityType = 'node';
        //$viewMode = 'sorbonne_tv_playlist';
        $viewMode = 'sorbonne_tv_mosaic';
        $storage = \Drupal::entityTypeManager()->getStorage($entityType);
        $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entityType);
        $article = $viewBuilder->view($node, $viewMode);
        $date_depot = $node->get('field_sorb_tv_date_depot')->getValue();
        $time_depot = strtotime($date_depot[0]['value']);

        $items[$time_depot] = $article;
        //$items[$time_depot]['#attributes']['class'] = ['video-item-row', 'field--item'];
      }

      krsort($items);

      // Playlist
      /*
      $list_render = [
        '#theme' => 'sorbonne_tv_favorites_playlist',
        '#title' => Markup::create($prg_title_str),
        '#playlist' => [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#attributes' => ['class' => ['prg-stv-playlist-wrapper']],
          '#items' => $items,
        ],
        '#attached' => [
          'library' => [
            'sorbonne_tv_lmc/slick'
          ]
        ]
      ];
      */

      // Mosaïque
      $list_render = [
        '#theme' => 'sorbonne_tv_favorites_mosaic',
        '#title' => Markup::create($prg_title_str),
        '#mosaic' => [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#attributes' => ['class' => ['search-items', 'videoscollec-list']],
          '#items' => $items,
        ],
        '#cache' => [
          'contexts' => [
            'user',
            'cookies:sorbonnefavorites'
          ]
        ]
      ];

    }

    return $list_render;
  }
}
