<?php

namespace Drupal\up1_search_api\Plugin\facets\processor;

use Drupal\facets\Annotation\FacetsProcessor;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Class MergePages which's merging two hard-coded content types.
 *
 * @package Drupal\up1_search_api\Plugin\facets\processor
 *
 * @FacetsProcessor (
 *   id = "up1_search_api_merge_pages",
 *   label = @Translation("Merge Pages and Second Level Pages together. "),
 *   description = @Translation("An integration to force put together Pages and Second Level Pages."),
 *   stages = {
 *    "build" = 60
 *   }
 * )
 */
class MergePages extends ProcessorPluginBase implements BuildProcessorInterface {
  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results)
  {
    /** @var \Drupal\facets\Result\Result[] $facets */
    $facets = array_reduce($results, function ($carry, $item) {
      /** @var \Drupal\facets\Result\Result $item */
      $carry[$item->getRawValue()] = $item;
    }, []);

    /** @var \Drupal\facets\Result\Result $page */
    $page = $facets['page'] ?? NULL;
    /** @var \Drupal\facets\Result\Result $second_page */
    $second_page = $facets['second_level_pages'] ?? NULL;

    if(!is_null($page)) {
      /** @var \Drupal\Core\Url $url */
      $url = $page->getUrl();
      /** @var array $query */
      $query = $url->getOption('query');

      // Init flag variables.
      $updated = FALSE;
      $filter = 'content_type:second_level_pages';

      // Look-up for query string.
      if (!in_array($filter, $query['sitewide'])) {
        // Inject filter to current query.
        $updated = TRUE;
        $query['sitewide'][] = $filter;
      }
      // Verify when current facet is active.
      elseif ($page->isActive()) {
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
        $page->setUrl($url);
      }

      // Update facet count value when article facet was found.
      if (!is_null($second_page)) {
        $page->setCount($page->getCount() + $second_page->getCount());

        // Remove `article` facet instance.
        unset($facets['second_level_pages']);
      }
    }

    return array_values($facets);
  }
}
