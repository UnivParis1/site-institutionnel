<?php

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * @param ViewExecutable $view
 */
function up1_global_views_pre_render(ViewExecutable $view) {
  if ($view->id() === 'patrimoine_immobilier') {
    $results = $view->result;
    $allTitles = [];
    $filteredResults = [];

    foreach ($results as $result) {
      $allTitles[$result->id] = $result->_entity->field_title->value;
    }
    $keys = array_keys(array_unique($allTitles));
    foreach ($results as $result) {
      if (in_array($result->id, $keys)) {
        $filteredResults[] = $result;
      }
    }
    $view->result = $filteredResults;
    $view->total_rows = count($filteredResults);
  }
}
