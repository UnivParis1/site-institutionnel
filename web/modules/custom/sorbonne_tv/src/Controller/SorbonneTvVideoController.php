<?php

namespace Drupal\sorbonne_tv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SorbonneTvVideoController extends ControllerBase {

  public function redirectToNode($videoId) {
    if ($video_node = \Drupal::service('sorbonne_tv.videos_service')->getStvNodeByVideoId($videoId, 'video')) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $video_node->id()]);
      return new RedirectResponse($url->toString());
    }
    else {
      throw new NotFoundHttpException();
    }

    return [];
  }
}
