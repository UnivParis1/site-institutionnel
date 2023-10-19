<?php

namespace Drupal\up1_search_api\Plugin\search_api\processor;

use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\SearchApiException;
use Drupal\up1_search_api\Plugin\search_api\processor\Property\AggregatedDateProperty;

/**
 * * Adds customized aggregations of existing fields to the index.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Property\AggregatedFieldProperty
 * Aggregate dates.
 *
 * @SearchApiProcessor(
 *   id = "aggregated_dates",
 *   label = @Translation("Aggregated dates fields."),
 *   description = @Translation("Aggregate dates in an only field to ease searches on dates fields. "),
 *   stages = {
 *    "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AggregatedDateField extends ProcessorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if ($datasource) {
      $definition = [
        'label' => $this->t('Aggregated dates'),
        'description' => $this->t('Aggregate dates in an only field.'),
        'type' => 'date',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];

      $properties['aggregated_dates'] = new AggregatedDateProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->index->getFields();
    $aggregated_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'aggregated_dates');
    $required_properties_by_datasource = [
      NULL => [],
      $item->getDatasourceId() => [],
    ];
    foreach ($aggregated_fields as $field) {
      foreach ($field->getConfiguration()['fields'] as $combined_id) {
        list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
        $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
      }
    }

    $property_values = $this->getFieldsHelper()
      ->extractItemValues([$item], $required_properties_by_datasource)[0];

    $aggregated_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'aggregated_dates');
    foreach ($aggregated_fields as $aggregated_field) {
      $values = [];
      $configuration = $aggregated_field->getConfiguration();
      foreach ($configuration['fields'] as $combined_id) {
        if (!empty($property_values[$combined_id])) {
          $values = array_merge($values, $property_values[$combined_id]);
        }
      }

      if (count($values) == 1) {
        $values = [reset($values)];
      }
      else {
        switch ($configuration['type']) {
          case 'first':
            $values = [current($values)];
            break;

          case 'older':
            $values = [end($values)];
            break;
        }
      }
      // Do not use setValues(), since that doesn't preprocess the values
      // according to their data type.
      foreach ($values as $value) {
        $aggregated_field->addValue($value);
      }
    }
  }
}
