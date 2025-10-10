<?php 

namespace Drupal\up1_api_job_offer\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\user\Entity\User;

class PostOffreController extends ControllerBase {

  protected $currentUser;

  public function __construct() {
    $this->currentUser = \Drupal::currentUser();
  }

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function post(Request $request) {
    // Vérifie si l'utilisateur est authentifié
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('Authentification requise.');
    }

    // Vérifie les permissions
    if (!$this->currentUser->hasPermission('create offer content')) {
      return new JsonResponse(['error' => 'Permission refusée.'], Response::HTTP_FORBIDDEN);
    }

    // Données JSON envoyées
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['title'])) {
      return new JsonResponse(['error' => 'Champs obligatoires manquants.'], Response::HTTP_BAD_REQUEST);
    }

    // Création du node
    $node = Node::create([
      'type' => 'offres',
      'title' => $data['title'],
      'uid' => $this->currentUser->id(),
      'status' => 1,
    ]);
    $node->save();

    return new JsonResponse([
      'message' => 'Offre créée avec succès.',
      'nid' => $node->id(),
    ], Response::HTTP_CREATED);
  }
}
