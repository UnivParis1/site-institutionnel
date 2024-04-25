<?php

namespace Drupal\micro_homepage\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Micro Homepage event subscriber.
 */
class HomepageAsNodeSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The negotiator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *    The current user.
   */
  public function __construct(SiteNegotiatorInterface $negotiator, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->negotiator = $negotiator;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();

    if ($this->currentUser->hasPermission('administer nodes')) {
      return;
    }

   $route_name = \Drupal::routeMatch()->getRouteName();  

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }
//    $active_site = $this->negotiator->getActiveSite();
//    if ($active_site instanceof SiteInterface) {
//      return;
//    }

    $node = $request->get('node');
    if (!$node instanceof NodeInterface) {
      return;
    }

    $query = $this->entityTypeManager->getStorage('site')->getQuery()->accessCheck(FALSE);
    $query->condition('status', TRUE);
    $query->condition('registered', TRUE);
    $query->condition('homepage', $node->id());
    $site_ids = $query->execute();

    if (!empty($site_ids)) {
      $site_id = reset($site_ids);
      $route_name = \Drupal::routeMatch()->getRouteName();  
      $site = $this->entityTypeManager->getStorage('site')->load($site_id);
      if ($site instanceof SiteInterface && $route_name != 'entity.node.edit_form') {
        $url = $site->getSitePath();
        $response = new TrustedRedirectResponse($url);
        $event->setResponse($response);
      }
    }
  }

   /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest',10]
    ];
  }

}
