<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Controller;

use Drupal\cas\Exception\CasLoginException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Manager\PagePersoManager;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Points d'entrée appelés depuis Comptex (adresses et réponses historiques).
 */
final class ComptexController extends ControllerBase {

  /**
   * Page d'information affichée quand la page perso n'est pas modifiable.
   */
  private const UNAVAILABLE_URL = 'https://majtrantor.univ-paris1.fr/miseajourpageperso.html';

  /**
   * Nœud d'aide affiché quand une personne a plusieurs pages personnelles.
   */
  private const DUPLICATES_NODE = 38866;

  public function __construct(
    private readonly ComptexManager $comptex,
    private readonly PagePersoManager $pagePersoManager,
    private readonly PagePersoSyncManager $syncManager,
    #[Autowire(service: 'logger.channel.up1_pages')]
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * « Modifier ma page personnelle » : prépare la page puis ouvre son édition.
   */
  public function editPagePerso(string $username): RedirectResponse {
    if ($this->isMaintenance()) {
      return new TrustedRedirectResponse(self::UNAVAILABLE_URL);
    }

    $this->logger->info('@username trying to edit page perso from comptex.', ['@username' => $username]);

    // Sans wsgroups, impossible de savoir si la personne a droit à une page :
    // on ne crée ni compte ni page.
    if (!$this->comptex->isAvailable()) {
      $this->logger->warning('@username : modification de page perso refusée, wsgroups indisponible.', ['@username' => $username]);
      return new TrustedRedirectResponse(self::UNAVAILABLE_URL);
    }
    if (!$this->comptex->userHasPagePerso($username)) {
      return new TrustedRedirectResponse(self::UNAVAILABLE_URL);
    }

    $person = $this->comptex->getUserAttributes($username, ['supannCivilite', 'displayName', 'mail']);
    try {
      // Même traitement que la synchro nocturne : compte, rôle, page créée ou
      // republiée.
      $nid = $this->syncManager->activate(
        $username,
        $this->syncManager->buildTitle($person),
        is_string($person['mail'] ?? NULL) ? $person['mail'] : ''
      );
    }
    catch (CasLoginException $e) {
      $this->logger->error('CasLoginException when registering user with name %name: %e', [
        '%name' => $username,
        '%e' => $e->getMessage(),
      ]);
      return new TrustedRedirectResponse(self::UNAVAILABLE_URL);
    }

    if ($nid === NULL) {
      $this->logger->error('@username a plusieurs pages personnelles.', ['@username' => $username]);
      return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => self::DUPLICATES_NODE])->toString());
    }

    return new RedirectResponse(Url::fromRoute('entity.node.edit_form', ['node' => $nid])->toString());
  }

  /**
   * Parcours OBSIA d'une personne, pour affichage dans Comptex.
   */
  public function getParcoursObsia(string $username): JsonResponse {
    if ($this->isMaintenance()) {
      return $this->envelope($this->maintenanceMessage());
    }

    $fields = [];
    foreach ($this->pagePersoManager->loadPages($username) as $page) {
      $fields[] = [
        'username' => $username,
        'bio' => $page->get('field_short_bio')->getValue(),
        'formations' => $page->get('field_formations_ia')->getValue(),
        'projets' => $page->get('field_projects_ia')->getValue(),
        'skills' => $page->get('field_ia_skills')->getValue(),
      ];
    }

    return $this->envelope($fields);
  }

  /**
   * Enregistre le parcours OBSIA saisi dans Comptex.
   */
  public function setParcoursObsia(string $username, Request $request): JsonResponse {
    $message = $this->isMaintenance()
      ? $this->maintenanceMessage()
      : $this->updateObsiaFields($username, $this->obsiaQuery($request));

    return $this->envelope(['username' => $username, 'message' => $message]);
  }

  /**
   * Efface le parcours OBSIA (case décochée dans Comptex).
   */
  public function deleteParcoursObsia(string $username): JsonResponse {
    $message = $this->isMaintenance() ? $this->maintenanceMessage() : $this->updateObsiaFields($username);

    return $this->envelope(['username' => $username, 'message' => $message]);
  }

  /**
   * API OBSIA : champs de la page personnelle.
   */
  public function apiGetObsia(string $username): JsonResponse {
    $page = $this->apiPage($username);
    if ($page instanceof JsonResponse) {
      return $page;
    }

    $projects = $page->get('field_projects_ia')->getValue();
    $formations = $page->get('field_formations_ia')->getValue();
    $skills = $page->get('field_ia_skills')->getValue();

    return new JsonResponse([
      'username' => $username,
      'bio' => $page->get('field_short_bio')->value,
      'projets' => $projects[0]['value'] ?? '',
      'formations' => $formations[0]['value'] ?? '',
      'skills' => $skills ?: '',
    ]);
  }

  /**
   * API OBSIA : mise à jour des champs fournis.
   */
  public function apiUpdateObsia(string $username, Request $request): JsonResponse {
    $page = $this->apiPage($username);
    if ($page instanceof JsonResponse) {
      return $page;
    }
    $this->setObsiaFields($page, $this->obsiaQuery($request));

    return new JsonResponse(['success' => $this->t('Page perso of @username updated successfully', ['@username' => $username])]);
  }

  /**
   * API OBSIA : effacement des champs.
   */
  public function apiDeleteObsia(string $username): JsonResponse {
    $page = $this->apiPage($username);
    if ($page instanceof JsonResponse) {
      return $page;
    }
    $this->clearObsiaFields($page);

    return new JsonResponse(['success' => $this->t('Page perso of @username updated successfully', ['@username' => $username])]);
  }

  /**
   * La personne a-t-elle droit à une page personnelle dans wsgroups ?
   */
  public function hasPagePerso(string $username): JsonResponse {
    return new JsonResponse([
      'hasPagePerso' => $this->comptex->userHasPagePerso($username),
      'available' => $this->comptex->isAvailable(),
    ]);
  }

  /**
   * Met à jour les champs OBSIA ; sans valeurs, les vide tous.
   */
  private function updateObsiaFields(string $username, ?array $fields = NULL) {
    if (!$this->pagePersoManager->loadAccount($username)) {
      return $this->t('No user with the username %username exists here. ', ['%username' => $username]);
    }
    $pages = $this->pagePersoManager->loadPages($username);
    if (count($pages) !== 1) {
      return $this->t('%username doesn\'t have page personnelle', ['%username' => $username]);
    }

    $page = reset($pages);
    $fields === NULL ? $this->clearObsiaFields($page) : $this->setObsiaFields($page, $fields);

    return $this->t('The page perso has been successfully updated.');
  }

  private function setObsiaFields(NodeInterface $page, array $fields): void {
    if (!array_filter($fields)) {
      return;
    }
    if (!empty($fields['bio'])) {
      $page->set('field_short_bio', $fields['bio']);
    }
    if (!empty($fields['formations'])) {
      $page->set('field_formations_ia', $fields['formations']);
    }
    if (!empty($fields['projets'])) {
      $page->set('field_projects_ia', $fields['projets']);
    }
    if (!empty($fields['skills'])) {
      $page->set('field_ia_skills', explode(',', $fields['skills']));
    }
    $page->save();
  }

  private function clearObsiaFields(NodeInterface $page): void {
    foreach (['field_short_bio', 'field_formations_ia', 'field_projects_ia', 'field_ia_skills'] as $field) {
      $page->set($field, NULL);
    }
    $page->save();
  }

  private function obsiaQuery(Request $request): array {
    return [
      'bio' => $request->query->get('bio'),
      'formations' => $request->query->get('formations'),
      'projets' => $request->query->get('projets'),
      'skills' => $request->query->get('skills'),
    ];
  }

  /**
   * Page d'un enseignant-chercheur ou doctorant, ou réponse 404.
   */
  private function apiPage(string $username): NodeInterface|JsonResponse {
    if (!$this->pagePersoManager->hasRole($username)) {
      return new JsonResponse(['error' => $this->t('User not found or is not student or teacher')], 404);
    }
    $pages = $this->pagePersoManager->loadPages($username);
    if (!$pages) {
      return new JsonResponse(['error' => $this->t('Page personnelle not found for @username', ['@username' => $username])], 404);
    }

    return reset($pages);
  }

  private function isMaintenance(): bool {
    return (bool) $this->config('up1_pages.settings')->get('activate_maintenance');
  }

  private function maintenanceMessage() {
    return $this->t('Pages personnelles are not available for modifications. Please try later.');
  }

  /**
   * Enveloppe JSON historique attendue par Comptex.
   */
  private function envelope($data): JsonResponse {
    return new JsonResponse(['data' => $data, 'method' => 'GET', 'status' => 200]);
  }

}
