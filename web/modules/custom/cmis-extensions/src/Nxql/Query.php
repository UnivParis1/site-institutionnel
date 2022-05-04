<?php

namespace Drupal\cmis_extensions\Nxql;

class Query {
  private $wheres;
  private $orders;

  public function __construct() {
    $this->wheres = [];
    $this->orders = [];
  }

  public function where($identifier) {
    $clause = new WhereClause($identifier);
    array_push($this->wheres, $clause);

    return $clause;
  }

  public function or() {
    $clause = new OrChain();
    array_push($this->wheres, $clause);

    return $clause;
  }

  public function orderBy($identifier) {
    $clause = new OrderClause($identifier);
    array_push($this->orders, $clause);

    return $clause;
  }

  public function toNxql($urlify = false) {
    $nxql = "SELECT * FROM File";

    if (!empty($this->wheres)) {
      $nxql .= " WHERE ";

      $clauses = array_map([$this, "translate"], $this->wheres);
      $nxql .= join(" AND ", $clauses);
    }

    if (!empty($this->orders)) {
      $nxql .= " ORDER BY ";

      $clauses = array_map([$this, "translate"], $this->orders);
      $nxql .= join(", ", $clauses);
    }

    return $urlify ? $this->urlify($nxql) : $nxql;
  }

  public function isEmpty() {
    return empty($this->wheres);
  }

  private function translate($clause) {
    return $clause->toNxql();
  }

  private function urlify($query) {
    $result = str_replace(" ", "+", $query);

    return $result;
  }
}
