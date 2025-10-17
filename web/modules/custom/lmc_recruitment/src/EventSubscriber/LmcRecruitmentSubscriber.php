<?php

declare(strict_types=1);

namespace Drupal\lmc_recruitment\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clean up orphaned job offers after an import has been run.
 */
final class LmcRecruitmentSubscriber implements EventSubscriberInterface {


  protected $entityTypeManager;
  protected $migrationPluginManager;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MigrationPluginManagerInterface $migration_plugin_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onPostImport'];
    return $events;
  }


  /**
   * Handle post-import cleanup.
   */
  public function onPostImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $migration_id = $migration->id();
    // Only process specific migrations
    if ($migration_id !== 'recruitment_job') {
      return;
    }
    $this->cleanupOrphanedEntities($migration);
  }

  /**
   * Remove entities that no longer exist in source.
   */
  protected function cleanupOrphanedEntities($migration) {
    $destination_config = $migration->getDestinationConfiguration();

    // Get destination entity type (e.g., 'node')
    $entity_type = $destination_config['plugin'] ?? 'node';
    if (strpos($entity_type, 'entity:') === 0) {
      $entity_type = substr($entity_type, 7);
    }

    $storage = $this->entityTypeManager->getStorage($entity_type);
    $database = \Drupal::database();
    $map_table = 'migrate_map_' . $migration->id();

    // Get all destination IDs that are currently in the map
    $current_dest_ids = $database->select($map_table, 'm')
      ->fields('m', ['destid1'])
      ->condition('source_row_status', 0, '>=') // Not failed
      ->execute()
      ->fetchCol();

    // Get all destination IDs that were EVER migrated by this migration
    // These are all entities with a record in the map table
    $all_migrated_ids = $database->select($map_table, 'm')
      ->fields('m', ['destid1'])
      ->isNotNull('destid1')
      ->execute()
      ->fetchCol();

    // Orphaned = previously migrated but not in current successful imports
    $orphaned_ids = array_diff($all_migrated_ids, $current_dest_ids);

    if (!empty($orphaned_ids)) {
      // Delete orphaned entities
      $entities = $storage->loadMultiple($orphaned_ids);

      foreach ($entities as $node) {
        if ($node instanceof NodeInterface) {
          // Don't do anything if "desync" is check.
          if ($node->field_job_unsync->value !== "1") {
            $node->setNewRevision(TRUE);
            $node->setUnpublished();
            $node->setRevisionTranslationAffected(TRUE);
            $node->set('moderation_state', 'draft');
            $node->save();
          }
        }

        \Drupal::logger('lmc_recruitment')->notice(
          'Unpublished @count entities from migration @migration: @ids',
          [
            '@count' => count($orphaned_ids),
            '@migration' => $migration->id(),
            '@ids' => implode(', ', $orphaned_ids),
          ]
        );
      }
    }
  }
}
