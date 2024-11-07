<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\up1_webservices\Manager\WsGroupsManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for UP1 Pages Persos routes.
 */
final class StudentsController extends ControllerBase {

  public function __construct(private readonly WsGroupsManager $wsGroupsManager) {}

  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('up1_webservices.wsgroups_manager')
    );
  }

  public function checkWsGroupsPagePerso(string $username): JsonResponse {
    $hasPagePerso = $this->wsGroupsManager->hasPagePersoInWsGroups($username);
    $page_perso = Node::load(10709);
    $author = User::load($page_perso->getOwnerId());


    return new JsonResponse([
      'hasPagePerso' => $hasPagePerso,
      'email' => $author->getEmail(),
      'pageDrupal' => $page_perso->id()
    ]);
  }
}
