<?php

namespace Drupal\micro_site_pickup\Access;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Access check for pickup content view.
 */
interface PickupContentAccessCheckInterface {

  /**
   * Checks access to the bat controller.
   *
   * @param SiteInterface $site
   *   The site entity.
   * @param \Drupal\Core\Session\AccountProxyInterface|NULL $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(SiteInterface $site, AccountProxyInterface $account = NULL);

}
