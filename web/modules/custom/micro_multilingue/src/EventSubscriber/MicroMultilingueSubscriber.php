<?php

namespace Drupal\micro_multilingue\EventSubscriber;

use Drupal\Core\Language\LanguageInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_multilingue\LanguageValidatorInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    $default_language = $this->languageManager->getDefaultLanguage();
    $current_language = $this->languageManager->getCurrentLanguage();
    /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
    $negotiator = \Drupal::service('micro_site.negotiator');
    if(!$this->languageValidator->isAvailableLanguage()) {
      $site = $negotiator->getActiveSite();
      // Check on the site the best default language available.
      if ($site instanceof SiteInterface) {
        $site_language = $site->language();
        if ($site_language instanceof LanguageInterface) {
          $default_language = $site_language;
        }
        else {
          foreach ($site->get('active_language') as $language) {
            $default_language = $language->entity;
            break;
          }
        }
        // The default language fallback is still the current language.
        // Try to get the first available language of the site.
        if ($default_language->getId() === $current_language->getId()) {
          foreach ($site->get('active_language') as $language) {
            $default_language = $language->entity;
            break;
          }
        }
      }

      // The default language is still the current language. Current language not
      // available for the mini site. We can't do anything anymore. Stop.
      if ($default_language->getId() === $current_language->getId()) {
        // In theory we should never enter here.
        return;
      }

      $route_match = RouteMatch::createFromRequest($request);
      $route_name = $route_match->getRouteName();

      $excluded_route_names = [
      'image.style_public',
      ];
      if (in_array($route_name, $excluded_route_names)) {
	      return;
      }

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

  }



  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 10],
    ];
  }

}
