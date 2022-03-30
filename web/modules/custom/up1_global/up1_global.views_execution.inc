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
    $filteredResults = [];

    foreach ($results as $result) {
        $filteredResults[] = $result;
        \Drupal::logger('up1_global_view_pre_render')->info(" Title patimmo : " . print_r($result->_entity->field_title->value, 1));
        \Drupal::logger('up1_global_view_pre_render')->info(" Full Entity : " . print_r($result->paragraph__field_title_field_title_value . ' ' . $result->id(), 1));
    }
  }
}
