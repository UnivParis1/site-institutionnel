<?php

namespace Drupal\micro_homepage\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\micro_site\SiteNegotiator;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MicroRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class MicroRouteSubscriber extends RouteSubscriberBase {

  protected $negotiator;


  public function __construct(SiteNegotiatorInterface $negotiator)
  {
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {}
}
