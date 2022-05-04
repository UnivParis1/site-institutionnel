<?php

namespace Drupal\cmis_extensions\Nxql;

class AndChain {
  private $clauses;

  public function __construct() {
    $this->clauses = [];
  }

  public function where($lhs) {
    $clause = new WhereClause($lhs);
    $this->clauses[] = $clause;

    return $clause;
  }

  public function toNxql() {
    $clauses = array_map(function($clause) {
      return $clause->toNxql();
    }, $this->clauses);

    return "(" . join(" AND ", $clauses) . ")";
  }
}
