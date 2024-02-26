<?php

namespace Drupal\micro_homepage\EventSubscriber;

use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Micro Homepage event subscriber.
 */
class MicroHomepageSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   */
  public function __construct(SiteNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    $site = $event->getRequest()->get('site');
    if(!empty($site)&& !empty($site->get('homepage')->entity)) {
      /**
       * @var Node $node
       */
      $node = $site->get('homepage')->entity;
      $active_language = \Drupal::languageManager()->getCurrentLanguage();
      if($node->hasTranslation($active_language->getId())) {
        $node = $site->get('homepage')->entity->getTranslation($active_language->getId());
      }

      $event->getRequest()->attributes->set('_entity' , $node);
    }
  }

   /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest',1]

      ];
  }

}
