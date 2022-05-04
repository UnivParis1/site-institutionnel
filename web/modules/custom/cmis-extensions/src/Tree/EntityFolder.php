<?php

namespace Drupal\cmis_extensions\Tree;

class EntityFolder {
  private $name;
  private $id;
  private $kindFolders;

  public function __construct($name, $id, $kindFolders) {
    $this->name = $name;
    $this->id = $id;
    $this->kindFolders = $kindFolders;
  }

  public function getName() {
    return $this->name;
  }

  public function getId() {
    return $this->id;
  }

  public function getKindFolders() {
    return $this->kindFolders;
  }
}
