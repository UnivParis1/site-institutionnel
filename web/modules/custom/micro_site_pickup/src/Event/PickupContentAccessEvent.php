<?php

namespace Drupal\micro_site_pickup\Event;

use Drupal\Core\Access\AccessResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the pickup content access event.
 */
class PickupContentAccessEvent extends Event {

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface
   */
  protected $access;

  /**
   * The context.
   *
   * @var array
   */
  protected $context;

  /**
   * Constructs a new PickupContentAccessEvent.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access object.
   * @param array $context
   *   The context. An array with the site and account objects.
   */
  public function __construct(AccessResultInterface $access, array $context) {
    $this->access = $access;
    $this->context = $context;
  }

  /**
   * Gets the access result.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function getAccess() {
    return $this->access;
  }

  /**
   * Sets the access result.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *
   * @return $this
   */
  public function setAccess(AccessResultInterface $access) {
    $this->access = $access;
    return $this;
  }

  /**
   * Gets the context.
   *
   * @return array
   *   The context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Gets the site.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   Gets the site.
   */
  public function getSite() {
    return $this->context['site'];
  }

  /**
   * Gets the account.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The account.
   */
  public function getAccount() {
    return $this->context['account'];
  }

}
