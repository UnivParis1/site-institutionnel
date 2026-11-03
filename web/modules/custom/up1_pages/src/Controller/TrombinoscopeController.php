<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\NodeInterface;
use Drupal\up1_pages\Cache\FallbackCache;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Gateway\WsGroupsDirectoryGateway;
use Drupal\up1_pages\Manager\PagePersoManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Pages « Équipe » des mini-sites : trombinoscopes des EC et des doctorants.
 */
final class TrombinoscopeController extends ControllerBase {

  /**
   * Nœud « Pages personnelles » du site principal (recherche d'une personne).
   */
  private const DIRECTORY_NODE = 745;

  /**
   * Mini-site de l'Observatoire de l'IA (export).
   */
  private const OBSIA_SITE = 114;

  /**
   * Groupes dont l'annuaire repose sur un consentement et non une structure.
   */
  private const CONSENT_GROUPS = ['observatoireIA', 'sante-shs'];

  /**
   * Durée de cache de la page quand wsgroups est indisponible : on retente
   * vite plutôt que de figer une page vide ou ancienne.
   */
  private const DEGRADED_MAX_AGE = 300;

  public function __construct(
    private readonly WsGroupsDirectoryGateway $wsGroupsDirectory,
    private readonly PagePersoManager $pagePersoManager,
    private readonly FallbackCache $fallbackCache,
    #[Autowire(service: 'micro_site.negotiator')]
    private readonly SiteNegotiatorInterface $negotiator,
  ) {
  }

  /**
   * /page-perso : renvoie vers la page de recherche du site principal.
   */
  public function gotoPagesPersonnelles(): RedirectResponse {
    return new RedirectResponse(
      Url::fromRoute('entity.node.canonical', ['node' => self::DIRECTORY_NODE])->toString()
    );
  }

  /**
   * /equipe : EC si l'annuaire EC est activé, sinon doctorants.
   */
  public function equipeDispatch() {
    $site = $this->getCurrentSite();
    if ($site && $site->get('ec_enabled')->value) {
      return $this->microFacultyList();
    }
    if ($site && $site->get('doc_enabled')->value) {
      return $this->microStudentList();
    }
    throw new NotFoundHttpException();
  }

  /**
   * Enseignants-chercheurs d'une structure / d'un mini-site.
   */
  public function microFacultyList($letter = NULL) {
    $site = $this->getCurrentSite();
    if (!$site || !$site->get('ec_enabled')->value) {
      throw new NotFoundHttpException();
    }
    if (!$site->get('trombi_ec_enable')->value) {
      return $this->gotoPagesPersonnelles();
    }

    return $this->buildTrombinoscope('faculty', $site);
  }

  /**
   * Doctorants d'une structure / d'un mini-site.
   */
  public function microStudentList($letter = NULL) {
    $site = $this->getCurrentSite();
    if (!$site || !$site->get('doc_enabled')->value) {
      throw new NotFoundHttpException();
    }
    if (!$site->get('trombi_students_enable')->value) {
      return $this->gotoPagesPersonnelles();
    }

    return $this->buildTrombinoscope('student', $site);
  }

  public function getPageEquipeTitle() {
    $site = $this->getCurrentSite();
    if ($site && $site->get('ec_enabled')->value) {
      return 'Pages personnelles enseignants-chercheurs';
    }
    if ($site && $site->get('doc_enabled')->value) {
      return 'Pages personnelles doctorants';
    }
    throw new NotFoundHttpException();
  }

  /**
   * Export (tableau ou CSV) des parcours de l'Observatoire de l'IA.
   */
  public function exportObsia(Request $request) {
    $site = $this->entityTypeManager()->getStorage('site')->load(self::OBSIA_SITE);
    if (!$site) {
      throw new NotFoundHttpException();
    }

    try {
      $users = $this->getUsers('faculty', $site);
    }
    catch (WsUnavailableException) {
      $this->messenger()->addError($this->t("L'annuaire (wsgroups) est indisponible : export impossible pour le moment."));
      return ['#cache' => ['max-age' => 0]];
    }

    $headers = [
      $this->t('login'),
      $this->t('Nom complet'),
      $this->t('URL page personnelle'),
      $this->t('Affiliation principale'),
      $this->t('Formations'),
      $this->t('Projets'),
      $this->t('Biographie'),
      $this->t('Expertise'),
    ];

    $format = static fn($value) => is_array($value) ? implode(', ', array_filter($value)) : ($value ?? '');

    $pages = $this->pagePersoManager->loadPagesByUsernames(array_column($users, 'uid'));
    $rows = [];
    foreach ($users as $user) {
      $page = $pages[mb_strtolower((string) ($user['uid'] ?? ''))] ?? NULL;
      if (!$page) {
        continue;
      }

      $rows[] = [
        $format($user['uid'] ?? ''),
        $format(trim($format($user['supannCivilite'] ?? '') . ' ' . $format($user['displayName'] ?? ''))),
        $format($user['labeledURI'] ?? ''),
        $format($user['supannEntiteAffectation'] ?? ''),
        $format($page->get('field_formations_ia')->value),
        $format($page->get('field_projects_ia')->value),
        $format($page->get('field_short_bio')->value),
        implode(', ', $this->listLabels($page, 'field_ia_skills')),
      ];
    }

    if ($request->query->get('export') === 'csv') {
      $handle = fopen('php://temp', 'r+');
      // BOM UTF-8 pour une meilleure ouverture dans Excel.
      fwrite($handle, "\xEF\xBB\xBF");
      fputcsv($handle, array_map('strval', $headers), ';');
      foreach ($rows as $row) {
        fputcsv($handle, $row, ';');
      }
      rewind($handle);
      $csv = stream_get_contents($handle);
      fclose($handle);

      return new Response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="export-obsia-' . date('Y-m-d') . '.csv"',
      ]);
    }

    return [
      'export_button' => [
        '#type' => 'link',
        '#title' => $this->t('Exporter en CSV'),
        '#url' => Url::fromRoute('up1_pages.export_obsia', [], ['query' => ['export' => 'csv']]),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => $this->t('Aucun utilisateur trouvé.'),
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

  private function buildTrombinoscope(string $affiliation, $site): array {
    $cache = (new CacheableMetadata())
      ->addCacheableDependency($site)
      ->addCacheTags(['node_list:' . PagePersoManager::BUNDLE])
      ->setCacheMaxAge((int) $this->config('up1_pages.settings')->get('ws.cache_ttl'));

    $settings = $this->getTrombiSettings($affiliation, $site);
    // Les réglages ne demandent des attributs supplémentaires à wsgroups que
    // pour les enseignants-chercheurs.
    try {
      $users = $this->getUsers($affiliation, $site, $affiliation === 'faculty' ? $settings : NULL, $stale);
    }
    catch (WsUnavailableException) {
      $build = [
        '#markup' => '<p class="messages messages--warning">' . $this->t("L'annuaire est momentanément indisponible. Merci de réessayer dans quelques minutes.") . '</p>',
      ];
      $cache->setCacheMaxAge(self::DEGRADED_MAX_AGE)->applyTo($build);
      return $build;
    }
    if ($stale) {
      $cache->setCacheMaxAge(self::DEGRADED_MAX_AGE);
    }

    $is_obsia = $site->get('groups')->value === 'observatoireIA';
    $photo_url = $this->config('up1_pages.settings')->get('url_userphoto');
    // Une seule lecture des pages pour toute la liste. Sans page, la carte
    // n'a que nom et photo.
    $pages = $this->pagePersoManager->loadPagesByUsernames(array_column($users, 'uid'));

    foreach ($users as &$user) {
      $page = $pages[mb_strtolower((string) ($user['uid'] ?? ''))] ?? NULL;

      if ($affiliation === 'faculty') {
        $user['skills'] = $this->trombiValue($is_obsia ? 'skillsIA' : 'skills', $user, $page, $settings);
        $user['about'] = $this->trombiValue($is_obsia ? 'aboutIA' : 'about', $user, $page, $settings);
        $user['research'] = $this->trombiValue('research', $user, $page, $settings);
        $user['pedagogy'] = $this->trombiValue('pedagogy', $user, $page, $settings);
        $user['role'] = $this->trombiValue('role', $user, $page, $settings);
        $user['discipline'] = $this->trombiValue('discipline', $user, $page, $settings);
      }
      else {
        $user['doctoralSchool'] = $this->trombiValue('doctoralSchool', $user, $page, $settings);
        $user['research'] = $this->trombiValue('research', $user, $page, $settings);
        $user['subject'] = $this->trombiValue('subject', $user, $page, $settings);
        $user['director'] = $this->trombiValue('director', $user, $page, $settings);
      }
      $user['photo'] = $photo_url . ($user['uid'] ?? '');
    }
    unset($user);

    // Tri alphabétique qui tient compte des accents (extension intl).
    $collator = new \Collator('fr_FR.UTF-8');
    usort($users, static fn($a, $b) => $collator->compare(self::first($a['sn'] ?? ''), self::first($b['sn'] ?? '')));

    $build = [
      'item_list' => [
        '#theme' => 'list_as_trombinoscope',
        '#users' => $users,
        '#affiliation' => $affiliation,
        '#Trusted' => FALSE,
        '#attached' => ['library' => ['up1_pages/trombi']],
      ],
    ];
    $cache->applyTo($build);

    return $build;
  }

  /**
   * Liste wsgroups d'un mini-site, avec repli sur la dernière liste connue.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si wsgroups est indisponible et qu'aucune liste n'est connue.
   */
  private function getUsers(string $affiliation, $site, ?array $trombi_settings = NULL, ?bool &$stale = NULL): array {
    return $this->fallbackCache->remember('trombi:' . $site->id() . ':' . $affiliation, function () use ($affiliation, $site, $trombi_settings) {
      if (in_array($site->get('groups')->value, self::CONSENT_GROUPS, TRUE)) {
        return $this->wsGroupsDirectory->getUserListWithConsent((string) $site->get('supannConsentement')->value, $trombi_settings);
      }
      return $this->wsGroupsDirectory->getUserList($affiliation, $site->id(), $trombi_settings);
    }, $stale);
  }

  /**
   * Ce que le mini-site a choisi d'afficher sur les cartes.
   */
  private function getTrombiSettings(string $affiliation, $site): array {
    if ($affiliation === 'student') {
      return [
        'supannEntite_doctoralSchool' => 1,
        'supannEntite_research' => 1,
        'display_school' => (int) $site->get('display_doctoral_school')->value,
        'viva_subject' => (int) $site->get('display_viva_subject')->value,
        'viva_director' => (int) $site->get('display_viva_director')->value,
      ];
    }

    return [
      'supannEntite_pedagogy' => $site->get('supannEntite_pedagogy')->value,
      'supannEntite_research' => $site->get('supannEntite_research')->value,
      'discipline_enseignement' => $site->get('discipline_enseignement')->value,
      'skills_lists' => $site->get('skills_lists')->value,
      'supannRole' => $site->get('supannRole')->value,
      'about_me' => $site->get('about_me')->value,
    ];
  }

  /**
   * Une information de la carte, vide si le mini-site ne l'affiche pas.
   */
  private function trombiValue(string $key, array $user, ?NodeInterface $page, array $settings): string {
    if (!$page) {
      return '';
    }

    switch ($key) {
      case 'skills':
        if (empty($settings['skills_lists'])) {
          return '';
        }
        $result = '';
        foreach ($page->get('field_skills')->referencedEntities() as $term) {
          $result .= '<li>' . htmlspecialchars($term->label(), ENT_QUOTES) . '</li>';
        }
        return $result;

      case 'skillsIA':
        if (empty($settings['skills_lists'])) {
          return '';
        }
        $result = '';
        foreach ($this->listLabels($page, 'field_ia_skills') as $label) {
          $result .= '<li>' . htmlspecialchars((string) $label, ENT_QUOTES) . '</li>';
        }
        return $result;

      case 'aboutIA':
        return empty($settings['about_me']) ? '' : (string) $page->get('field_short_bio')->value;

      case 'about':
        return empty($settings['about_me']) ? '' : (string) $page->get('field_about_me')->value;

      case 'research':
        return empty($settings['supannEntite_research']) ? '' : $this->affectation($user, 'research');

      case 'pedagogy':
        return empty($settings['supannEntite_pedagogy']) ? '' : $this->affectation($user, 'pedagogy');

      case 'doctoralSchool':
        return (empty($settings['supannEntite_doctoralSchool']) || empty($settings['display_school']))
          ? '' : $this->affectation($user, 'doctoralSchool');

      case 'role':
        if (empty($settings['supannRole']) || empty($user['supannRoleEntite-all'][0])) {
          return '';
        }
        $role = $user['supannRoleEntite-all'][0];
        return trim(($role['role'] ?? '') . ' ' . ($role['structure']['description'] ?? ''));

      case 'discipline':
        return (empty($settings['discipline_enseignement']) || empty($user['info']))
          ? '' : implode(', ', (array) $user['info']);

      case 'subject':
        return empty($settings['viva_subject']) ? '' : (string) $page->get('field_thesis_subject')->value;

      case 'director':
        return empty($settings['viva_director']) ? '' : (string) $page->get('field_phd_supervisor')->value;
    }

    return '';
  }

  /**
   * Libellé de la première affectation d'une catégorie (research, pedagogy…).
   */
  private function affectation(array $user, string $category): string {
    foreach ($user['supannEntiteAffectation-all'] ?? [] as $item) {
      if (is_array($item) && ($item['businessCategory'] ?? NULL) === $category) {
        return (string) ($item['description'] ?? '');
      }
    }

    return '';
  }

  /**
   * Libellés des valeurs d'un champ liste.
   *
   * @return string[]
   */
  private function listLabels(NodeInterface $page, string $field): array {
    if (!$page->hasField($field)) {
      return [];
    }
    $allowed = $page->get($field)->getSetting('allowed_values') ?? [];
    $labels = [];
    foreach ($page->get($field)->getValue() as $item) {
      if (isset($item['value']) && $item['value'] !== '') {
        $labels[] = $allowed[$item['value']] ?? $item['value'];
      }
    }

    return $labels;
  }

  private static function first($value): string {
    return (string) (is_array($value) ? reset($value) : $value);
  }

  private function getCurrentSite() {
    return $this->negotiator->getActiveSite();
  }

}
