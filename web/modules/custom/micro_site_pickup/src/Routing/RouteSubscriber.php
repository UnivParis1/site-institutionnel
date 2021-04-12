<?php

namespace Drupal\micro_site_pickup\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add custom access on site pickup content view.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add as custom access requirement on site pickup content view per site.
    $views = [
      'view.pickup_content.page_1',
      'view.pickup_content.page_2',
    ];
    foreach ($views as $view) {
      $route = $collection->get($view);
      if ($route) {
        $route->addRequirements([
          'site' => '\d+',
          '_pickup_content' => 'TRUE',
        ]);

        $options = [
          '_admin_route' => TRUE,
          'parameters' => [
            'site' => [
              'type' => 'entity:site',
              'with_config_overrides' => TRUE,
            ],
          ],
        ];
        $route->addOptions($options);
      }
    }
  }

}
