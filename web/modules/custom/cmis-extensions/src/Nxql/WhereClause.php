<?php

namespace Drupal\cmis_extensions\Nxql;

class WhereClause {
  private $lhs;
  private $operator;
  private $rhs;

  public function __construct($lhs) {
    $this->lhs = $lhs;
  }

  public function like($rhs) {
    $this->operator = "ILIKE";
    $this->rhs = "'%" . $rhs . "%'";
  }

  public function strictLike($rhs) {
    $this->operator = "LIKE";
    $this->rhs = "'" . $rhs . "'";
  }

  public function eq($value) {
    $this->operator = "=";
    $this->rhs = "'" . addslashes($value) . "'";
  }

  public function toNxql() {
    return join(" ", [$this->lhs, $this->operator, $this->rhs]);
  }
}
