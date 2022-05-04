<?php

namespace Drupal\cmis_extensions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class CacheController extends ControllerBase {
  public function inspect() {
    $cache = \Drupal::cache()->get('cmis_extensions:folders');
    $response = new JsonResponse();

    if ($cache) {
      $response->setData($cache->data);
    } else {
      $response->setData([]);
    }

    return $response;
  }
}
