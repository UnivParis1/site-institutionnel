<?php

declare(strict_types=1);

namespace Drupal\lmc_recruitment\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clean up orphaned job offers after an import has been run.
 */
final class LmcRecruitmentSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the subscriber.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onPostImport'];
    return $events;
  }

  /**
   * Reacts to the POST_IMPORT event.
   */
  public function onPostImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Only act on a specific migration ID. Change 'my_migration' to match yours.
    if ($migration->id() !== 'recruitment_job') {
      return;
    }

    $id_map = $migration->getIdMap();
    $id_map->prepareUpdate();

    // Clone so that any generators aren't initialized prematurely.
    $source = clone $migration->getSourcePlugin();
    $source->rewind();
    $source_id_values = [];

    while ($source->valid()) {
      $source_id_values[] = $source->current()->getSourceIdValues();
      $source->next();
    }
    $id_map->rewind();

    while ($id_map->valid()) {
      $map_source_id = $id_map->currentSource();

      $destination_ids = $id_map->currentDestination();

      if (!empty($destination_ids['nid'])) {
        $storage = $this->entityTypeManager->getStorage('node');
        $node = $storage->load($destination_ids['nid']);

        if ($node instanceof NodeInterface) {
          // Don't do anything if "desync" is check.
          if ($node->field_job_unsync->value !== "1") {
            if (!in_array($map_source_id, $source_id_values, TRUE)) {
              $node->setNewRevision(TRUE);
              $node->setUnpublished();
              $node->setRevisionTranslationAffected(TRUE);
              $node->set('moderation_state', 'draft');
              $node->save();
            }
          }
        }
      }

      $id_map->next();
    }
  }
}
