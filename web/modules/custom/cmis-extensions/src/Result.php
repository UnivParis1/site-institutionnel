<?php

namespace Drupal\cmis_extensions;

class Result {
  private $id;

  private $title;

  private $size;

  private $lastModified;

  private $parent;

  public function __construct($id, $title, $size, $lastModified, $parent) {
    $this->id = $id;
    $this->title = $title;
    $this->size = $size;

    $this->lastModified = $lastModified;

    $this->parent = $parent;
  }

  public function getId() {
    return $this->id;
  }

  public function getTitle() {
    return $this->title;
  }

  public function getSize() {
    return $this->size;
  }

  public function getLastModified() {
    return $this->lastModified;
  }

  public function getParent() {
    return $this->parent;
  }

  public function getDownloadUrl() {
    $config = \Drupal::config('cmis_extensions.settings');
    return
      $config->get('nuxeo_base_url') ."/nuxeo/json/cmis/default/root?objectId=" . config->get('nuxeo_folder_id');
  }
}
