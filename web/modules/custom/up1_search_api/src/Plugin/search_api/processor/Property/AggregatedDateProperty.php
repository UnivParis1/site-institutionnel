<?php

namespace Drupal\up1_search_api\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Defines an "aggregated date" property.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\AggregatedFields
 */
class AggregatedDateProperty extends ConfigurablePropertyBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => 'latest',
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $index = $field->getIndex();
    $configuration = $field->getConfiguration();

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Aggregation date type'),
      '#description' => $this->t('All types will result in just a single value.'),
      '#options' => $this->getTypes(),
      '#default_value' => $configuration['type'],
      '#required' => TRUE,
    ];

    foreach ($this->getTypes('description') as $type => $description) {
      $form['type'][$type]['#description'] = $description;
    }

    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Contained fields'),
      '#options' => [],
      '#attributes' => ['class' => ['search-api-checkboxes-list']],
      '#default_value' => $configuration['fields'],
      '#required' => TRUE,
    ];
    $datasource_labels = $this->getDatasourceLabelPrefixes($index);
    $properties = $this->getAvailableProperties($index);
    $field_options = [];
    foreach ($properties as $combined_id => $property) {
      list($datasource_id, $name) = Utility::splitCombinedId($combined_id);
      // Do not include the "aggregated field" property.
      if (!$datasource_id && $name == 'aggregated_dates') {
        continue;
      }
      $label = $datasource_labels[$datasource_id] . $property->getLabel();
      $field_options[$combined_id] = Utility::escapeHtml($label);
      if ($property instanceof ConfigurablePropertyInterface) {
        $description = $property->getFieldDescription($field);
      }
      else {
        $description = $property->getDescription();
      }
      $form['fields'][$combined_id] = [
        '#attributes' => ['title' => $this->t('Machine name: @name', ['@name' => $name])],
        '#description' => $description,
      ];
    }
    // Set the field options in a way that sorts them first by whether they are
    // selected (to quickly see which one are included) and second by their
    // labels.
    asort($field_options, SORT_NATURAL);
    $selected = array_flip($configuration['fields']);
    $form['fields']['#options'] = array_intersect_key($field_options, $selected);
    $form['fields']['#options'] += array_diff_key($field_options, $selected);

    // Make sure we do not remove nested fields (which can be added via config
    // but won't be present in the UI).
    $missing_properties = array_diff($configuration['fields'], array_keys($properties));
    if ($missing_properties) {
      foreach ($missing_properties as $combined_id) {
        list(, $property_path) = Utility::splitCombinedId($combined_id);
        if (strpos($property_path, ':')) {
          $form['fields'][$combined_id] = [
            '#type' => 'value',
            '#value' => $combined_id,
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'type' => $form_state->getValue('type'),
      'fields' => array_keys(array_filter($form_state->getValue('fields'))),
    ];
    $field->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription(FieldInterface $field) {
    $index = $field->getIndex();
    $available_properties = $this->getAvailableProperties($index);
    $datasource_label_prefixes = $this->getDatasourceLabelPrefixes($index);
    $configuration = $field->getConfiguration();

    $fields = [];
    foreach ($configuration['fields'] as $combined_id) {
      list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
      $label = $property_path;
      if (isset($available_properties[$combined_id])) {
        $label = $available_properties[$combined_id]->getLabel();
      }
      $fields[] = $datasource_label_prefixes[$datasource_id] . $label;
    }
    $type = $this->getTypes()[$configuration['type']];

    $arguments = ['@type' => $type, '@fields' => implode(', ', $fields)];

    return $this->t('A @type aggregation of the following fields: @fields.', $arguments);
  }

  /**
   * Retrieves information about available aggregation types.
   *
   * @param string $info
   *   (optional) One of "label" or "description", to indicate what values
   *   should be returned for the types.
   *
   * @return array
   *   An array of the identifiers of the available types mapped to, depending
   *   on $info, their labels, their data types or their descriptions.
   */
  protected function getTypes($info = 'label') {
    switch ($info) {
      case 'label':
        return [
          'latest' => $this->t('Latest'),
          'older' => $this->t('Older'),
        ];

      case 'description':
        return [
          'latest' => $this->t('If there are more than 1 value date, take the latest one.'),
          'older' => $this->t('If there are more than 1 value date, take the older one.'),
        ];

    }
    return [];
  }

  /**
   * Retrieves label prefixes for an index's datasources.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return string[]
   *   An associative array mapping datasource IDs (and an empty string for
   *   datasource-independent properties) to their label prefixes.
   */
  protected function getDatasourceLabelPrefixes(IndexInterface $index) {
    $prefixes = [
      NULL => $this->t('General') . ' » ',
    ];

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $prefixes[$datasource_id] = $datasource->label() . ' » ';
    }

    return $prefixes;
  }

  /**
   * Retrieve all properties available on the index.
   *
   * The properties will be keyed by combined ID, which is a combination of the
   * datasource ID and the property path. This is used internally in this class
   * to easily identify any property on the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   All the properties available on the index, keyed by combined ID.
   *
   * @see \Drupal\search_api\Utility::createCombinedId()
   */
  protected function getAvailableProperties(IndexInterface $index) {
    $properties = [];

    $datasource_ids = $index->getDatasourceIds();
    $datasource_ids[] = NULL;
    foreach ($datasource_ids as $datasource_id) {
      foreach ($index->getPropertyDefinitions($datasource_id) as $property_path => $property) {
        $properties[Utility::createCombinedId($datasource_id, $property_path)] = $property;
      }
    }

    return $properties;
  }

}
