<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Manager\HalPublications;
use Drupal\up1_pages\Manager\PagePersoManager;
use Drupal\up1_pages\Manager\PagePersoPurger;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Psr\Log\LoggerInterface;

/**
 * Hooks du module.
 *
 * Drupal 11.1+ les découvre par leurs attributs #[Hook] ; sous Drupal 10, les
 * fonctions de up1_pages.module (#[LegacyHook]) appellent ces méthodes.
 */
final class PagePersoHooks {

  use StringTranslationTrait;

  /**
   * Durée de cache d'une page perso affichée avec une fiche wsgroups
   * ancienne ou absente : on retente vite.
   */
  private const DEGRADED_MAX_AGE = 300;

  public function __construct(
    private readonly ComptexManager $comptex,
    private readonly HalPublications $halPublications,
    private readonly PagePersoSyncManager $syncManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountInterface $currentUser,
    private readonly StateInterface $state,
    private readonly LoggerInterface $logger,
    private readonly EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchema,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly PagePersoPurger $purger,
  ) {
  }

  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name !== 'help.page.up1_pages') {
      return NULL;
    }

    return '<h3>' . $this->t('About') . '</h3><p>' . $this->t('Gestion des pages personnelles des enseignants-chercheurs et doctorants de Paris 1.') . '</p>';
  }

  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    return $entity_type->id() === 'site' ? SiteFields::definitions() : [];
  }

  /**
   * Propriétaire des champs des mini-sites.
   *
   * Avant la reprise, ils restent à l'ancien module (up1_pages_personnelles
   * ou up1_pages_persos) qui possède les colonnes, même s'il ne les déclare
   * plus : sinon Drupal tenterait de les recréer en installant ce module.
   * Après la reprise, ils sont à ce module, même si un ancien module encore
   * installé les déclare aussi.
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() !== 'site') {
      return;
    }
    $claimed = (bool) $this->state->get(LegacyTakeover::STATE_FIELDS_CLAIMED);
    $installed = $claimed ? [] : $this->lastInstalledSchema->getLastInstalledFieldStorageDefinitions('site');

    foreach (array_keys(SiteFields::FORM_GROUPS) as $name) {
      if (!isset($fields[$name])) {
        continue;
      }
      if ($claimed) {
        $fields[$name]->setProvider('up1_pages');
        continue;
      }
      $owner = isset($installed[$name]) ? $installed[$name]->getProvider() : NULL;
      if (in_array($owner, LegacyTakeover::MODULES, TRUE) && $this->moduleHandler->moduleExists($owner)) {
        $fields[$name]->setProvider($owner);
      }
    }
  }

  /**
   * Fin de la reprise des anciens modules : leur désinstallation.
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, bool $is_syncing): void {
    if (in_array('up1_pages', $modules, TRUE)) {
      LegacyTakeover::finish();
    }
  }

  /**
   * Regroupe les champs ajoutés au formulaire des mini-sites.
   */
  #[Hook('form_site_form_alter')]
  public function formSiteFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $groups = [
      'pages_personnelles' => [$this->t('Directory and Groups'), 'site-form-annuaire'],
      'staff_ec' => [$this->t('Équipe Enseignant-Chercheur'), 'site-form-staff'],
      'staff_doc' => [$this->t('Équipe doctorants'), 'site-form-staff'],
    ];
    foreach ($groups as $key => [$title, $class]) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'advanced',
        '#attributes' => ['class' => [$class]],
        '#weight' => 100,
        '#optional' => TRUE,
      ];
    }
    foreach (SiteFields::FORM_GROUPS as $field => $group) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = $group;
      }
    }
  }

  /**
   * Le login et le titre (tiré de wsgroups) ne se modifient pas à la main.
   */
  #[Hook('form_node_page_personnelle_form_alter')]
  #[Hook('form_node_page_personnelle_edit_form_alter')]
  public function formPagePersoAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!in_array('administrator', $this->currentUser->getRoles(), TRUE)) {
      $form[PagePersoManager::FIELD_UID_LDAP]['#access'] = FALSE;
      $form['title']['#disabled'] = TRUE;
    }
  }

  /**
   * Fiche wsgroups et publications HAL de la page personnelle.
   *
   * Ne bloque jamais l'affichage : sans réponse des web services, la page
   * s'affiche avec les dernières données connues, ou sans elles.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    if (!$node instanceof NodeInterface || $node->bundle() !== PagePersoManager::BUNDLE) {
      return;
    }
    $owner = $node->getOwner();
    if (!$owner) {
      return;
    }

    $information = $this->comptex->getUserInformation($owner->getAccountName(), $stale);
    $cache = CacheableMetadata::createFromRenderArray($variables)
      ->addCacheTags(['config:up1_pages.settings'])
      ->mergeCacheMaxAge($stale ? self::DEGRADED_MAX_AGE : (int) $this->configFactory->get('up1_pages.settings')->get('ws.cache_ttl'));
    $variables['ws_unavailable'] = $stale;

    foreach ($information as $key => $value) {
      $variables[$key] = $value;
    }
    // Numéros de téléphone jamais affichés (GLPI UP1#198753).
    unset($variables['telephoneNumber']);

    $variables['hal_publications'] = $this->halPublications($node, $cache);

    $cache->applyTo($variables);
  }

  #[Hook('theme')]
  public function theme(): array {
    return [
      'list_as_trombinoscope' => [
        'variables' => [
          'users' => [],
          'link' => NULL,
          'affiliation' => NULL,
          'Trusted' => FALSE,
          'trombi_settings' => [],
        ],
      ],
    ];
  }

  /**
   * Synchro avec wsgroups (au plus une fois par sync.interval) et
   * rafraîchissement des publications HAL (au plus une fois par
   * hal.refresh_interval).
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->syncManager->enqueueIfDue();
    $this->halPublications->enqueueIfDue();
  }

  #[Hook('token_info')]
  public function tokenInfo(): array {
    return [
      'tokens' => [
        'node' => [
          'usermailtoken' => [
            'name' => $this->t('User mail token'),
            'description' => $this->t("Adresse électronique de l'auteur d'une page personnelle, abrégée (utilisée dans l'alias des pages)."),
          ],
        ],
      ],
    ];
  }

  /**
   * [node:usermailtoken] : « prenom.nom@ » ou « prenom.nom@sous-domaine ».
   *
   * Utilisé par l'alias pathauto des pages personnelles. Si l'adresse n'est
   * pas connue (wsgroups indisponible), le jeton n'est pas remplacé : pathauto
   * ne génère alors pas d'alias et garde l'ancien.
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];
    if ($type !== 'node' || !isset($tokens['usermailtoken']) || !($data['node'] ?? NULL) instanceof NodeInterface) {
      return $replacements;
    }
    $node = $data['node'];
    if (!$node->hasField(PagePersoManager::FIELD_UID_LDAP)) {
      return $replacements;
    }
    $email = $this->comptex->getUserEmail((string) $node->get(PagePersoManager::FIELD_UID_LDAP)->value);
    if ($email && str_contains($email, '@')) {
      $replacements[$tokens['usermailtoken']] = Markup::create($this->shortenEmail($email));
    }

    return $replacements;
  }

  /**
   * Abrège une adresse de l'université, comme les alias existants.
   */
  public function shortenEmail(string $email): string {
    [$local, $domain] = explode('@', $email, 2);
    if ($domain === 'univ-paris1.fr' || $domain === 'pantheonsorbonne.fr') {
      return $local . '@';
    }
    if (str_ends_with($domain, '.univ-paris1.fr')) {
      return $local . '@' . substr($domain, 0, -strlen('.univ-paris1.fr'));
    }
    // Les sous-domaines de pantheonsorbonne.fr gardent l'adresse complète,
    // comme jusqu'ici : les abréger changerait l'URL de pages existantes.
    return $email;
  }

  /**
   * Recherche HAL modifiée : l'encart est oublié puis recalculé par le cron.
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node): void {
    if ($node->bundle() !== PagePersoManager::BUNDLE) {
      return;
    }
    $original = method_exists($node, 'getOriginal') ? $node->getOriginal() : ($node->original ?? NULL);
    $this->purger->trackStatus($node, $original);

    foreach (['field_my_hal_publications', 'field_id_hal'] as $field) {
      if ($original && $node->hasField($field) && $node->get($field)->value !== $original->get($field)->value) {
        $this->halPublications->forget((int) $node->id());
        $this->halPublications->enqueue((int) $node->id());
        return;
      }
    }
  }

  /**
   * Page créée dépubliée : date de dépublication notée.
   */
  #[Hook('node_insert')]
  public function nodeInsert(NodeInterface $node): void {
    $this->purger->trackStatus($node, NULL);
  }

  #[Hook('node_delete')]
  public function nodeDelete(NodeInterface $node): void {
    if ($node->bundle() === PagePersoManager::BUNDLE) {
      $this->halPublications->forget((int) $node->id());
      $this->purger->forget((int) $node->id());
    }
  }

  /**
   * Encart HAL enregistré : l'affichage n'appelle jamais HAL.
   */
  private function halPublications(NodeInterface $node, CacheableMetadata $cache): ?string {
    $cache->addCacheTags([HalPublications::cacheTag($node->id())]);
    try {
      return $this->halPublications->get($node);
    }
    catch (\Throwable $e) {
      $this->logger->error('Publications HAL de la page @nid non affichées : @message', [
        '@nid' => $node->id(),
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
