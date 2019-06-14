<?php

namespace Drupal\micro_multilingue\EventSubscriber;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_multilingue\LanguageValidatorInterface;
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
   * @var LanguageValidatorInterface
   */
  protected $languageValidator;


  /**
   * Constructs event subscriber.
   *
   *
   */
  public function __construct(AccountInterface $currentUser, LanguageManagerInterface $languageManager,
                              LanguageValidatorInterface $languageValidator ) {
    $this->currentUser = $currentUser;
    $this->languageManager = $languageManager;
    $this->languageValidator = $languageValidator;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    $default_language = $this->languageManager->getDefaultLanguage();
    if(!$this->languageValidator->isAvailableLanguage()) {
      $route_match = RouteMatch::createFromRequest($request);
      $route_name = $route_match->getRouteName();
      $parameters = $route_match->getRawParameters()->all();

      if(\Drupal::service('path.matcher')->isFrontPage()) {
        $url = Url::fromRoute('<front>', [], ['language' => $default_language]);
      }
      else {
        $url = Url::fromRoute($route_name, $parameters, ['language' => $default_language]);
      }
      $new_response = new RedirectResponse($url->toString(), '302');
      $event->setResponse($new_response);
    }

//    /**
//     * @var $node Node;
//     */
//    $node = $request->get('node');
//
//    if (!empty($node) && !$node->hasTranslation($active_language->getId())) {
//      $route_match = RouteMatch::createFromRequest($request);
//      $route_name = $route_match->getRouteName();
//      $parameters = $route_match->getRawParameters()->all();
//      $url = Url::fromRoute($route_name, $parameters, ['language' => $default_language]);
//      $new_response = new RedirectResponse($url->toString(), '302');
//      $event->setResponse($new_response);
//    }

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
