<?php

namespace Drupal\cmis_extensions\Nxql;

class OrderClause {
  private $value;
  private $sort;

  public function __construct($value) {
    $this->value = $value;
  }

  public function desc() {
    $this->sort = "DESC";
  }

  public function asc() {
    $this->sort = "ASC";
  }

  public function toNxql() {
    return $this->value . " " . (isset($this->sort) ? $this->sort : "ASC");
  }
}
