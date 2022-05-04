<?php

namespace Drupal\cmis_extensions\Nxql;

class OrChain {
  private $clauses;

  public function __construct() {
    $this->clauses = [];
  }

  public function where($lhs) {
    $clause = new WhereClause($lhs);
    $this->clauses[] = $clause;

    return $clause;
  }

  public function and() {
    $chain = new AndChain();
    $this->clauses[] = $chain;

    return $chain;
  }

  public function toNxql() {
    $clauses = array_map(function($clause) {
      return $clause->toNxql();
    }, $this->clauses);

    return "(" . join(" OR ", $clauses) . ")";
  }
}
