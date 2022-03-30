<?php

/**
 * @file
 * Provide views runtime hooks for up1_global_moderation.module.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Implements hook_views_query_substitutions().
 */
function up1_global_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return [
    '***BYPASS_NODE_ACCESS***' => intval($account->hasPermission('bypass node access')),
  ];
}

/**
 * Implements hook_views_query_alter().
 */
function up1_global_views_query_alter(ViewExecutable $view, QueryPluginBase $query)
{
  if ($view->id() == 'actualite') {
    \Drupal::logger('up1_global_views_query')->info("We're IN");
    $is = "IS";
    $isnot = "IS NOT";

    $news_global = "node_field_data.promote IN (0,1)";
    $news_micro = "site_id IS NOT :site_id AND node_field_data.promote = 1";

    $snippet = "($news_global) OR ($news_micro)";

    $query->addWhereExpression('', $snippet, $argument = [":site_id" => NULL]);
  }
}

/**
 *
 */
function up1_global_views_pre_render(ViewExecutable $view) {
  if ($view->id() === 'patrimoine_immobilier') {
    Drupal::logger('up1_global_view_pre_render')->info("Total rows : $view->total_rows");
    $results = $view->result;
    $allResults = [];
    $allTitles = [];
    $filteredResults = [];

    foreach ($results as $result) {
      $allTitles[$result->id] = $result->_entity->field_title->value;
    }
    Drupal::logger('up1_global_view_pre_render')->info(print_r(array_unique($allTitles), 1));
    $allTitles = array_unique($allTitles);
    $keys = array_keys($allTitles);
    foreach ($results as $result) {
      if (in_array($result->id, $keys)) {
        $filteredResults[] = $result;
      }
    }
    foreach ($filteredResults as $key => &$row) {
      $row->index = $key;
    }

    Drupal::logger('up1_global_view_pre_render')->info("Total after : " . count($filteredResults));
  }
}
