<?php

namespace Drupal\micro_site_pickup\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site_pickup\Event\MicroSitePickupEvents;
use Drupal\micro_site_pickup\Event\PickupContentAccessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Access check for pickup content view.
 */
class PickupContentAccessCheck implements AccessInterface, PickupContentAccessCheckInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;


  /**
   * ComposeTeamAccessCheck constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, AccountProxyInterface $current_user) {
    $this->eventDispatcher = $event_dispatcher;
    $this->currentUser = $current_user;
  }

  /**
   * Access check for pickup content view.
   *
   * @param SiteInterface $site
   *   The site entity.
   * @param \Drupal\Core\Session\AccountProxyInterface|NULL $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(SiteInterface $site, AccountProxyInterface $account = NULL) {
    $access_denied = AccessResult::forbidden()->addCacheableDependency($site)->cachePerPermissions();
    $access_allowed = AccessResult::allowed()->addCacheableDependency($site)->cachePerPermissions();
    $access = $access_denied;
    if (!$account) {
      $account = $this->currentUser;
    }

    if ($account->hasPermission('administer site entities')) {
      $access = $access_allowed;
    }
    elseif ($account->hasPermission('pickup content micro sites')) {
      $admin_users = $site->getAdminUsersId();
      if (in_array($account->id(), $admin_users)) {
        $access = $access_allowed;
      }
    }
    $context = [
      'site' => $site,
      'account' => $account,
    ];
    $pickup_content_access = new PickupContentAccessEvent($access, $context);
    $this->eventDispatcher->dispatch(MicroSitePickupEvents::PICKUP_CONTENT_ACCESS_CHECK, $pickup_content_access);
    $access = $pickup_content_access->getAccess();
    return $access;
  }
}
