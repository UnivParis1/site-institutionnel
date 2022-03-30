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
    $results = $view->result;
    $allResults = [];
    $allTitles = [];

    foreach ($results as $result) {
      $allTitles[] = _entity->field_title->value;
      $allResults[] = $result;
    }
    $serialized = array_map('serialize', $allResults);
    $unique = array_unique($serialized);
    Drupal::logger('up1_global_view_pre_render')->info(print_r(array_intersect_key($allResults, $unique), 1));
  }
}
