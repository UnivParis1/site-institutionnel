<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Hook;

/**
 * Reprise de up1_pages_personnelles et up1_pages_persos par ce module.
 *
 * En trois temps, sans perte de données :
 * - requirements() : refuse l'installation si du code (thème, autre module)
 *   utilise encore les anciens modules, ou si de la configuration inattendue
 *   en dépend ;
 * - prepare() (hook_install) : réglages repris, champs des mini-sites et
 *   dépendances des rôles et blocs transférés à up1_pages ;
 * - finish() (hook_modules_installed) : désinstallation des anciens modules.
 *
 * Utilise \Drupal directement : pendant les vérifications, les services de
 * ce module n'existent pas encore.
 */
final class LegacyTakeover {

  public const MODULES = ['up1_pages_personnelles', 'up1_pages_persos'];

  /**
   * Après la reprise, ce module déclare les champs des mini-sites même si un
   * ancien module, encore installé, les déclare aussi.
   */
  public const STATE_FIELDS_CLAIMED = 'up1_pages.site_fields_claimed';

  /**
   * Configurations dépendant des anciens modules supprimées avec eux.
   */
  private const DISPOSABLE = [
    'ultimate_cron.job.up1_pages_personnelles_cron',
    'ultimate_cron.job.up1_pages_persos_cron',
    // Republication des pages dépubliées à tort : la synchro de up1_pages
    // fait la même chose.
    'ultimate_cron.job.up1_pages_personnelles_republish',
  ];

  /**
   * Configurations dont la dépendance est transférée à up1_pages : droit
   * « has labeled URI » et bloc de recherche, repris sous les mêmes noms.
   */
  private const TRANSFERABLE_PREFIXES = ['user.role.', 'block.block.'];

  /**
   * Anciennes files d'attente, vidées à la reprise.
   */
  private const LEGACY_QUEUES = [
    'up1_pages_persos_sync',
    'up1_page_perso_queue',
    'up1_page_perso_node_creation_queue',
    'up1_typo3_data_queue',
    'up1_typo3_resume_queue',
    'up1_typo3_publications_queue',
    'up1_typo3_last_fields_queue',
    'up1_remove_enseignant_doctorant_role_queue',
  ];

  /**
   * Anciens modules encore installés.
   *
   * @return string[]
   */
  public static function installedModules(): array {
    $module_handler = \Drupal::moduleHandler();

    return array_values(array_filter(self::MODULES, [$module_handler, 'moduleExists']));
  }

  /**
   * Erreurs bloquant l'installation.
   *
   * @return string[]
   */
  public static function requirements(): array {
    $legacy = self::installedModules();
    if (!$legacy) {
      return [];
    }

    $errors = [];
    if ($references = self::codeReferences()) {
      $errors[] = 'Du code utilise encore up1_pages_personnelles ou up1_pages_persos (classes, bibliothèques) et casserait à leur désinstallation : ' . implode(' ; ', $references);
    }
    if ($blocking = array_diff(self::dependents($legacy), self::DISPOSABLE, self::transferable($legacy))) {
      $errors[] = 'Ces configurations dépendent des anciens modules et seraient supprimées à leur désinstallation : ' . implode(', ', $blocking);
    }

    return $errors;
  }

  /**
   * Transfère réglages, champs et dépendances (hook_install).
   */
  public static function prepare(): void {
    $legacy = self::installedModules();
    if (!$legacy) {
      return;
    }

    // 1. Réglages : ceux de up1_pages_persos, plus récents, à défaut ceux de
    // up1_pages_personnelles. Les nouvelles clés gardent leur valeur par
    // défaut.
    $config_factory = \Drupal::configFactory();
    $settings = $config_factory->getEditable('up1_pages.settings');
    foreach (['up1_pages_persos.settings', 'up1_pages_personnelles.settings'] as $name) {
      $old = $config_factory->get($name);
      if ($old->isNew()) {
        continue;
      }
      foreach (['url_ws', 'search_user', 'filtre_faculty', 'filtre_student', 'other_filters', 'search_user_page', 'url_hal_api', 'url_userphoto'] as $key) {
        if ((string) $old->get($key) !== '') {
          $settings->set($key, (string) $old->get($key));
        }
      }
      $settings->set('activate_maintenance', (bool) $old->get('activate_maintenance'));
      break;
    }
    $settings->save();

    // 2. Champs des mini-sites : ils changent de module propriétaire. Sans
    // cela, désinstaller l'ancien module supprimerait les colonnes et leurs
    // données.
    // Les mises à jour 8001 à 8007 de up1_pages_personnelles ont enregistré
    // « site » comme propriétaire de la plupart de ces champs : on les reprend
    // aussi, par leur nom.
    $repository = \Drupal::service('entity.last_installed_schema.repository');
    $definitions = $repository->getLastInstalledFieldStorageDefinitions('site');
    foreach ($definitions as $name => $definition) {
      if (in_array($definition->getProvider(), $legacy, TRUE) || isset(SiteFields::FORM_GROUPS[$name])) {
        $definition->setProvider('up1_pages');
      }
    }
    $repository->setLastInstalledFieldStorageDefinitions('site', $definitions);
    \Drupal::state()->set(self::STATE_FIELDS_CLAIMED, TRUE);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // 3. Rôles (droit « has labeled URI ») et blocs (recherche) : on fait
    // pointer leur dépendance vers up1_pages, qui fournit le même droit et le
    // même bloc. Sans cela, Drupal retirerait le droit aux rôles, et
    // supprimerait les blocs, en désinstallant l'ancien module.
    foreach (self::transferable($legacy) as $name) {
      $config = $config_factory->getEditable($name);
      $modules = array_diff((array) $config->get('dependencies.module'), $legacy);
      $modules[] = 'up1_pages';
      $modules = array_values(array_unique($modules));
      sort($modules);
      $config->set('dependencies.module', $modules)->save(TRUE);
    }
    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();
    \Drupal::entityTypeManager()->getStorage('block')->resetCache();
  }

  /**
   * Désinstalle les anciens modules (hook_modules_installed).
   */
  public static function finish(): void {
    $legacy = self::installedModules();
    if (!$legacy) {
      return;
    }

    // Dernière vérification : aucune colonne des mini-sites ne doit encore
    // appartenir à un ancien module.
    $field_manager = \Drupal::service('entity_field.manager');
    $field_manager->clearCachedFieldDefinitions();
    foreach ($field_manager->getFieldStorageDefinitions('site') as $name => $definition) {
      if (in_array($definition->getProvider(), $legacy, TRUE)) {
        \Drupal::logger('up1_pages')->error('Anciens modules non désinstallés : le champ @field des mini-sites appartient encore à @module.', [
          '@field' => $name,
          '@module' => $definition->getProvider(),
        ]);
        return;
      }
    }

    \Drupal::service('module_installer')->uninstall($legacy, FALSE);

    $queue_factory = \Drupal::service('queue');
    foreach (self::LEGACY_QUEUES as $queue) {
      $queue_factory->get($queue)->deleteQueue();
    }

    \Drupal::logger('up1_pages')->notice('@modules repris par up1_pages puis désinstallés.', ['@modules' => implode(', ', $legacy)]);
  }

  /**
   * Configurations qui dépendent directement des anciens modules.
   *
   * Les dépendances indirectes (une vue qui dépend d'un rôle qui dépend d'un
   * ancien module) sont ignorées : le maillon direct est soit transféré à
   * up1_pages, soit signalé lui-même.
   *
   * @return string[]
   */
  private static function dependents(array $legacy): array {
    $config_factory = \Drupal::configFactory();
    $names = array_keys(\Drupal::service('config.manager')->findConfigEntityDependencies('module', $legacy));

    return array_values(array_filter($names, static function (string $name) use ($config_factory, $legacy): bool {
      $dependencies = (array) $config_factory->get($name)->get('dependencies');
      $modules = array_merge((array) ($dependencies['module'] ?? []), (array) ($dependencies['enforced']['module'] ?? []));
      return (bool) array_intersect($modules, $legacy);
    }));
  }

  /**
   * @return string[]
   */
  private static function transferable(array $legacy): array {
    return array_values(array_filter(self::dependents($legacy), static function (string $name): bool {
      foreach (self::TRANSFERABLE_PREFIXES as $prefix) {
        if (str_starts_with($name, $prefix)) {
          return TRUE;
        }
      }
      return FALSE;
    }));
  }

  /**
   * Fichiers des modules et thèmes personnalisés installés qui mentionnent
   * les anciens modules.
   *
   * @return string[]
   *   « chemin:ligne ».
   */
  private static function codeReferences(): array {
    $extensions = array_merge(
      \Drupal::moduleHandler()->getModuleList(),
      \Drupal::service('theme_handler')->listInfo(),
    );
    $root = \Drupal::root();
    $found = [];

    foreach ($extensions as $name => $extension) {
      $path = $extension->getPath();
      if (in_array($name, [...self::MODULES, 'up1_pages'], TRUE) || !str_contains($path, '/custom/')) {
        continue;
      }
      $files = new \RegexIterator(
        new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$root/$path", \FilesystemIterator::SKIP_DOTS)),
        '/\.(php|module|theme|inc|install|twig|yml)$/'
      );
      foreach ($files as $file) {
        foreach (file($file->getPathname()) ?: [] as $index => $line) {
          if (preg_match('/up1_pages_(personnelles|persos)\b/', $line)) {
            $found[] = substr($file->getPathname(), strlen($root) + 1) . ':' . ($index + 1);
          }
        }
      }
    }

    return $found;
  }

}
