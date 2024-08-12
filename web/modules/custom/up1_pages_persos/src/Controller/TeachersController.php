<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\up1_pages_persos\Entity\Node\PagePerso;
use Drupal\up1_webservices\Manager\WsGroupsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for UP1 Pages Persos routes.
 */
final class TeachersController extends ControllerBase {

  protected $entityTypeManager;
  public function __construct(
    private readonly WsGroupsManager $wsGroupsManager) {}

  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('up1_webservices.wsgroups_manager')
    );
  }

  public function checkWsGroupsPagePerso(string $username): JsonResponse {
    $hasPagePerso = $this->wsGroupsManager->hasPagePerso($username, 'teacher');



    return new JsonResponse([
      'hasPagePerso' => $hasPagePerso,
    ]);
  }

}
