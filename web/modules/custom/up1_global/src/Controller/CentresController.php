<?php

namespace Drupal\up1_global\Controller;

use Drupal\Core\Controller\ControllerBase;

class CentresController extends ControllerBase {

  /**
   * @var \Drupal\up1_global\CentresService
   */
  private $centresService;

  public function __construct() {
    $this->centresService = \Drupal::service('up1_global.centres');
  }

  public function blockCentre($code) {
    $centre = $this->centresService->getACentre($code);

    $build['centre'] = [
      '#theme' => 'details_centre_up1',
      '#centre' => $centre,
    ];

    return $build;
  }
}
