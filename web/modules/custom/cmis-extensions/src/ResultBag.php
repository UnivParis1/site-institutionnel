<?php

namespace Drupal\cmis_extensions;

class ResultBag {
  private $results;

  private $total;
  private $nbOfPages;

  private $queryParams;

  public static function empty() {
    return new ResultBag([], 0, 1, []);
  }

  public function __construct($results, $total, $nbOfPages, $queryParams) {
    $this->results = $results;
    $this->total = $total;
    $this->nbOfPages = (int)$nbOfPages;
    $this->queryParams = $queryParams;

    $this->page = isset($queryParams["page"]) ? (int)$queryParams["page"] : 1;
  }

  public function getResults() {
    return $this->results;
  }

  public function getTotal() {
    return $this->total;
  }

  public function getNbOfPages() {
    return $this->nbOfPages;
  }

  public function getHasMoreItems() {
    return $this->page < $this->nbOfPages;
  }

  public function getPage() {
    return $this->page;
  }

  public function isEmpty() {
    return empty($this->results);
  }

  public function getPagesRange() {
    $nbOfPages = $this->nbOfPages;
    $page = $this->page;

    if ($nbOfPages < 10) {
      return range(1, $nbOfPages);
    } else {

      if ($page > 2 && $page < $nbOfPages - 2) {
        $before = $page == 3 ? [] : [1, '...'];
        $middle = range($page - 2, $page + 2);
        $after = ['...', $nbOfPages];

      } else if ($page >= $nbOfPages - 2) {
        $before = range(1, 3);
        $middle = ['...'];

        if ($page == $nbOfPages - 2) {
          $after = range($nbOfPages - 3, $nbOfPages);
        } else {
          $after = range($nbOfPages - 2, $nbOfPages);
        }

      } else {
        $before = array_merge(range(1, 3));
        $middle = ['...'];
        $after = range($nbOfPages - 2, $nbOfPages);
      }

      return array_merge(
        $before,
        $middle,
        $after);
    }
  }

  public function getPrevPageUrl() {
    return $this->getPageUrl($this->page - 1);
  }

  public function getNextPageUrl() {
    return $this->getPageUrl($this->page + 1);
  }

  public function getPageUrl($page) {
    $queryParams = $this->queryParams;
    $currentPath = \Drupal::service('path.current')->getPath();

    $queryParams["page"] = $page;

    $queryString = http_build_query($queryParams);

    return $currentPath . "?" . $queryString;
  }
}
