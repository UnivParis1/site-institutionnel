<?php

namespace Drupal\up1_theses\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\up1_theses\Service\ThesesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

class ThesesController extends ControllerBase {

  /**
   * The theses helper used to get settings from.
   *
   * @var \Drupal\up1_theses\Service\ThesesHelper
   */
  protected $thesesHelper;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Array that contains all formated data.
   * @var $theses
   */
  protected $theses;

  /**
   * Constructor.
   *
   * @param \Drupal\up1_theses\Service\ThesesHelper $theses_helper
   *   The ThesesHelper to get data.
   */
  public function __construct(ThesesHelper $theses_helper) {
    $this->thesesHelper = $theses_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theses.helper')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function listeTheses() {
    return [
      '#markup' => '<div>' . print_r($this->thesesHelper->createNodesFromJson(), 1) . '</div>',
    ];

  }


}