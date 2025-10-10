<?php

namespace Drupal\up1_api_job_offer\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;

class OffresApiSubscriber implements EventSubscriberInterface {

  protected $userAuth;

  public function __construct(UserAuthInterface $userAuth) {
    $this->userAuth = $userAuth;
  }

  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($path === '/api/post-offre' && $request->getMethod() === 'POST') {
      $auth = $request->headers->get('Authorization');
      if (!$auth || !str_starts_with($auth, 'Basic ')) {
        throw new AccessDeniedHttpException('Authentification requise.');
      }

      $decoded = base64_decode(substr($auth, 6));
      if (!$decoded || !str_contains($decoded, ':')) {
        throw new AccessDeniedHttpException('Format d’authentification invalide.');
      }

      [$username, $password] = explode(':', $decoded, 2);
      $uid = $this->userAuth->authenticate($username, $password);

      if (!$uid) {
        throw new AccessDeniedHttpException('Identifiants invalides.');
      }
      
      $user = \Drupal\user\Entity\User::load($uid);
      \Drupal::currentUser()->setAccount($user);
    }
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
  }
}
