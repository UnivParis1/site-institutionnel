<?php

namespace Drupal\up1_search_api\Plugin\facets\processor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MergeNodeTypes what allow merging facet content types.
 *
 * @package Drupal\up1_search_api\Plugin\facets\processor
 *
 * @FacetsProcessor(
 *   id = "up1_search_api_merge_node_types",
 *   label = @Translation("Merge node types together."),
 *   description = @Translation("An integration to force put together node types
facet results into a single one."),
 *   stages = {
 *     "build" = 80
 *   }
 * )
 */
class MergeNodeTypes extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Extract all available node types then map them as valid options.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNodeTypes(): array {
    /** @var array $nodeTypes */
    $nodeTypes = array_map(function ($nodeType) {
      /** @var \Drupal\node\Entity\NodeType $nodeType */
      return $nodeType->label();
    }, $this->entityTypeManager->getStorage('node_type')->loadMultiple());

    return $nodeTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    /** @var array $config */
    $config = $this->getConfiguration()['facet_groups'];

    // Gather the number of groups in the form already.
    $groups = $form_state->get('groups');

    // We have to ensure that there is at least one group.
    if (is_null($groups)) {
      $groups = count($config);
      $form_state->set('groups', $groups);
    }

    // Prepare form widget.
    $build['#tree'] = TRUE;
    $build['container_open']['#markup'] = '<div id="facet-group-fieldset-wrapper">';

    // Iterate same times as groups available.
    for ($i = 0; $i < $groups; $i++) {

      // Build details wrapper on each group.
      $build['facet_groups'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Facet group'),
        '#open' => FALSE,
      ];

      // Include field to overwrite facet name.
      $build['facet_groups'][$i]['facet_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Facet name'),
        '#default_value' => $config[$i]['facet_name'] ?? NULL,
      ];

      // Expose all possible content types available.
      $build['facet_groups'][$i]['content_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Content types to be grouped.'),
        '#options' => $this->getNodeTypes(),
        '#default_value' => $config[$i]['content_types'] ?? [],
      ];
    }

    // Close container element.
    $build['container_close']['#markup'] = '</div>';

    // Setup $.ajax buttons.
    $build['actions'] = [
      '#type' => 'actions',
    ];
    $build['actions']['add_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => [
        [$this, 'addOne'],
      ],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'facet-group-fieldset-wrapper',
      ],
    ];

    // If there is more than one group, add the remove button.
    if ($groups > 1) {
      $build['actions']['remove_group'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => [
          [$this, 'removeOne'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addMoreCallback'],
          'wrapper' => 'facet-group-fieldset-wrapper',
        ],
      ];
    }

    return $build;
  }

  /**
   * Submit handler for the "Add one more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('groups');
    $add_button = $groups + 1;
    $form_state->set('groups', $add_button);

    // Since our buildForm() method relies on the value of 'groups' to
    // generate 'facet_groups' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeOne(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('groups');
    if ($groups > 1) {
      $remove_button = $groups - 1;
      $form_state->set('groups', $remove_button);
    }

    // Since our buildForm() method relies on the value of 'groups' to
    // generate 'facet_groups' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    /** @var array $facet_groups */
    $facet_groups = NestedArray::getValue($form, [
      'facet_settings',
      'up1_search_api_merge_node_types',
      'settings',
      'facet_groups',
    ]);

    // Recreate container wrapper.
    $facet_groups['#prefix'] = '<div id="facet-group-fieldset-wrapper">';
    $facet_groups['#suffix'] = '</div>';

    return $facet_groups;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form_state->unsetValue('actions');
    parent::submitConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'facet_groups' => [
        [
          'facet_name' => '',
          'content_types' => [],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var array $facet_groups */
    $facet_groups = $this->getConfiguration()['facet_groups'];

    /** @var \Drupal\facets\Result\Result[] $facets */
    $facets = array_reduce($results, function ($carry, $item) {
      /** @var \Drupal\facets\Result\Result $item */
      $carry[$item->getRawValue()] = $item;
      return $carry;
    }, []);

    array_walk($facet_groups, function ($config) use ($results, &$facets) {
      /** @var array $types */
      $types = array_filter($config['content_types']);
      if (empty($types)) {
        return;
      }

      /** @var array $filtered */
      $filtered = array_filter($types, function ($type) use ($facets) {
        return array_key_exists($type, $facets);
      });
      if (empty($filtered)) {
        return;
      }

      /** @var string $key */
      $key = array_shift($filtered);
      /** @var \Drupal\facets\Result\Result $first */
      $first = &$facets[$key];

      // Overwrite label if new facet name was defined.
      if (!empty($config['facet_name'])) {
        $first->setDisplayValue($config['facet_name']);
      }

      // Init flag variables.
      $updated = FALSE;

      /** @var \Drupal\Core\Url $url */
      $url = $first->getUrl();
      /** @var array $query */
      $query = $url->getOption('query');

      // Walk-through all remain filtered types.
      foreach ($filtered as $item) {
        // Setup dynamic filter.
        $filter = "content_type:{$item}";

        // Look-up for query string.
        if (!in_array($filter, $query['sitewide'])) {
          // Inject filter to current query.
          $updated = TRUE;
          $query['sitewide'][] = $filter;
        }
        // Verify when current facet is active.
        elseif ($first->isActive()) {
          // Remove duplication filter values.
          $updated = TRUE;
          $query['sitewide'] = array_filter($query['sitewide'], function ($param) use ($filter) {
            return $param != $filter;
          });

          // Remove whole query string when there are not filters.
          if (empty($query['sitewide'])) {
            unset($query['sitewide']);
          }
        }

        // Overwrite URL options then define it back to facet.
        if ($updated) {
          $url->setOption('query', $query);
          $first->setUrl($url);
        }

        // Update facet count value when lab facet was found.
        $first->setCount($first->getCount() + $facets[$item]->getCount());

        // Remove facet instance.
        unset($facets[$item]);
      }
    });

    return array_values($facets);
  }

  /**
   * Setter method to deting entity type manager property.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function setEntityTypeManager(EntityTypeManager $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    // Inject dependency into current plugin's instance.
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }
}
