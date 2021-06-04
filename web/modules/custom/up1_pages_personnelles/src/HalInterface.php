<?php

namespace Drupal\up1_pages_personnelles;

/**
 * Interface HalInterface
 *
 * @package Drupal\up1_pages_personnelles
 */
interface HalInterface {
  public function getUserPublications($method, $firstname, $lastname, $id_hal);
}
