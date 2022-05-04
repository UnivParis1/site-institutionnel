<?php

namespace Drupal\cmis_extensions\Tree;

class KindFolder {
  private $id;
  private $kind;

  public function __construct($id, $kind) {
    $this->id   = $id;
    $this->kind = $kind;
  }

  public function getId() {
    return $this->id;
  }

  public function getKind() {
    return $this->kind;
  }
}
