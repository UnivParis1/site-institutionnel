<?php

namespace Drupal\micro_multilingue\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * micro_multilingue event subscriber.
 */
class MicroMultilingueSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /*
   * The language Manager
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;


  /**
   * Constructs event subscriber.
   *
   *
   */
  public function __construct(AccountInterface $currentUser, LanguageManagerInterface $languageManager ) {
    $this->currentUser = $currentUser;
    $this->languageManager = $languageManager;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($this->currentUser->hasPermission('access non default language pages')) {
      return;
    }

    $default_language = $this->languageManager->getDefaultLanguage();
    $active_language = $this->languageManager->getCurrentLanguage();

    if( \Drupal::service('path.matcher')->isFrontPage() )


    /**
     * @var $node Node;
     */
    $node = $request->get('node');

    if (!empty($node) && !$node->hasTranslation($active_language->getId())) {
      $route_match = RouteMatch::createFromRequest($request);
      $route_name = $route_match->getRouteName();
      $parameters = $route_match->getRawParameters()->all();
      $url = Url::fromRoute($route_name, $parameters, ['language' => $default_language]);
      $new_response = new RedirectResponse($url->toString(), '302');
      $event->setResponse($new_response);
    }

  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
