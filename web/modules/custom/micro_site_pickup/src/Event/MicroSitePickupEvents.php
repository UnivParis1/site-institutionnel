<?php

namespace Drupal\micro_site_pickup\Event;

/**
 * Define the event names for micro site pickup.
 */
final class MicroSitePickupEvents {

  /**
   * Name of the event fired before returning access check on the pickup content view.
   *
   * @Event
   *
   * @see \Drupal\micro_site_pickup\Event\PickupContentAccessEvent
   */
  const PICKUP_CONTENT_ACCESS_CHECK = 'micro_site_pickup_access_check';

}
