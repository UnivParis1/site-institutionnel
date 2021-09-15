<?php

namespace Drupal\up1_pages_personnelles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Typo3Controller extends ControllerBase {
  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new DataSearchController object.
   *
   * @param \Drupal\Core\Database\Connection|null $dataservice
   */
  public function __construct(Connection $dataservice = null) {
    $this->database = $dataservice;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('up1_pages_personnelles.database')
    );
  }

}
