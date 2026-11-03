<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Manager\PagePersoPurger;
use Psr\Log\NullLogger;

/**
 * Purge des pages personnelles dépubliées depuis longtemps (SQLite).
 *
 * @group up1_pages
 * @requires extension pdo_sqlite
 */
class PurgeTest extends UnitTestCase {

  use WsTestTrait;

  private const YEAR = 365 * 86400;

  private static int $connection = 0;

  private Connection $db;

  private KeyValueMemoryFactory $store;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
    $this->store = new KeyValueMemoryFactory();

    $key = 'up1_pages_purge_' . ++self::$connection;
    Database::addConnectionInfo($key, 'default', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'namespace' => 'Drupal\sqlite\Driver\Database\sqlite',
      'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
      'prefix' => '',
    ]);
    $this->db = Database::getConnection('default', $key);
    $schema = $this->db->schema();
    $schema->createTable('users_field_data', ['fields' => [
      'uid' => ['type' => 'int'],
      'name' => ['type' => 'varchar', 'length' => 60],
      'default_langcode' => ['type' => 'int'],
    ]]);
    $schema->createTable('user__roles', ['fields' => [
      'entity_id' => ['type' => 'int'],
      'roles_target_id' => ['type' => 'varchar', 'length' => 32],
    ]]);
    $node_fields = ['fields' => [
      'nid' => ['type' => 'int'],
      'vid' => ['type' => 'int'],
      'uid' => ['type' => 'int'],
      'type' => ['type' => 'varchar', 'length' => 32],
      'status' => ['type' => 'int'],
      'title' => ['type' => 'varchar', 'length' => 255],
      'changed' => ['type' => 'int'],
      'langcode' => ['type' => 'varchar', 'length' => 12],
      'default_langcode' => ['type' => 'int'],
    ]];
    $schema->createTable('node_field_data', $node_fields);
    $schema->createTable('node_field_revision', $node_fields);

    foreach ([1 => 'parti', 2 => 'actif'] as $uid => $name) {
      $this->db->insert('users_field_data')->fields(['uid' => $uid, 'name' => $name, 'default_langcode' => 1])->execute();
    }
    $this->db->insert('user__roles')->fields(['entity_id' => 2, 'roles_target_id' => 'enseignant_doctorant'])->execute();
  }

  /**
   * Une page : ses révisions (version d'origine) [statut, il y a N ans].
   */
  private function page(int $nid, array $revisions, int $uid = 1, array $translation = NULL, string $type = 'page_personnelle'): void {
    $now = $this->clock->now;
    $vid = $nid * 100;
    foreach ($revisions as [$status, $years]) {
      $this->db->insert('node_field_revision')->fields([
        'nid' => $nid, 'vid' => ++$vid, 'uid' => $uid, 'type' => $type, 'status' => $status,
        'title' => "Page $nid", 'changed' => (int) ($now - $years * self::YEAR), 'langcode' => 'fr', 'default_langcode' => 1,
      ])->execute();
    }
    [$status, $years] = end($revisions);
    $rows = [['fr', 1, $status, $years]];
    if ($translation) {
      $rows[] = ['en', 0, $translation[0], $translation[1]];
    }
    foreach ($rows as [$langcode, $default, $row_status, $row_years]) {
      $this->db->insert('node_field_data')->fields([
        'nid' => $nid, 'vid' => $vid, 'uid' => $uid, 'type' => $type, 'status' => $row_status,
        'title' => "Page $nid", 'changed' => (int) ($now - $row_years * self::YEAR), 'langcode' => $langcode, 'default_langcode' => $default,
      ])->execute();
    }
  }

  private function purger(?EntityTypeManagerInterface $entity_type_manager = NULL): PagePersoPurger {
    return new PagePersoPurger(
      $this->db,
      $entity_type_manager ?? $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(MemoryCacheInterface::class),
      $this->store,
      $this->clock,
      new NullLogger(),
    );
  }

  public function testPurgeIsBasedOnUnpublicationDate(): void {
    // Dépubliée il y a 3 ans.
    $this->page(1, [[1, 5], [0, 3]]);
    // Dépubliée il y a 3 ans, modifiée il y a 6 mois : purgée quand même.
    $this->page(2, [[1, 5], [0, 3], [0, 0.5]]);
    // Dépubliée il y a 1 an.
    $this->page(3, [[1, 3], [0, 1]]);
    // Dépubliée sans nouvelle révision il y a 1 an (date de modification
    // de la révision mise à jour) : pas purgée.
    $this->page(4, [[0, 1]]);
    // Auteur toujours enseignant_doctorant.
    $this->page(5, [[1, 5], [0, 3]], 2);
    // Traduction anglaise publiée.
    $this->page(6, [[1, 5], [0, 3]], 1, [1, 3]);
    // Jamais publiée, créée il y a 3 ans.
    $this->page(9, [[0, 3]]);
    // Pas un contenu « page personnelle ».
    $this->page(11, [[1, 5], [0, 3]], 1, NULL, 'article');

    $pages = $this->purger()->findPurgeable($this->clock->now - 2 * self::YEAR);

    $this->assertSame([1, 2, 9], array_map(static fn($page) => (int) $page->nid, $pages));
    $this->assertSame(PagePersoPurger::SOURCE_ESTIMATED, $pages[0]->source);
    $this->assertSame('parti', $pages[0]->login);
    $this->assertSame((int) ($this->clock->now - 3 * self::YEAR), $pages[1]->since);
  }

  public function testTrackedDateWinsOverRevisions(): void {
    // Révisions : dépubliée il y a 1 an ; suivi : il y a 3 ans.
    $this->page(7, [[1, 3], [0, 1]]);
    $this->store->get('up1_pages.unpublished_since')->set(7, (int) ($this->clock->now - 3 * self::YEAR));
    // Révisions : il y a 3 ans ; suivi : republiée puis dépubliée il y a 1 an.
    $this->page(8, [[1, 5], [0, 3]]);
    $this->store->get('up1_pages.unpublished_since')->set(8, (int) ($this->clock->now - self::YEAR));

    $pages = $this->purger()->findPurgeable($this->clock->now - 2 * self::YEAR);

    $this->assertSame([7], array_map(static fn($page) => (int) $page->nid, $pages));
    $this->assertSame(PagePersoPurger::SOURCE_TRACKED, $pages[0]->source);
  }

  public function testUnpublicationDateIsTracked(): void {
    $purger = $this->purger();
    $store = $this->store->get('up1_pages.unpublished_since');
    $published = $this->node(TRUE);
    $unpublished = $this->node(FALSE);

    // Créée dépubliée.
    $purger->trackStatus($unpublished, NULL);
    $this->assertSame($this->clock->now, $store->get(12));

    // Encore dépubliée, modifiée plus tard : la date ne bouge pas.
    $first = $this->clock->now;
    $this->clock->now += 1000;
    $purger->trackStatus($unpublished, $unpublished);
    $this->assertSame($first, $store->get(12));

    // Republiée puis dépubliée : nouvelle date.
    $purger->trackStatus($published, $unpublished);
    $this->assertNull($store->get(12));
    $purger->trackStatus($unpublished, $published);
    $this->assertSame($this->clock->now, $store->get(12));
  }

  public function testPageRepublishedMeanwhileIsNotDeleted(): void {
    $abandoned = $this->node(FALSE);
    $republished = $this->node(TRUE);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([1 => $abandoned, 8 => $republished]);
    $storage->expects($this->once())->method('delete')->with([1 => $abandoned]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $this->assertSame(1, $this->purger($entity_type_manager)->purge([1, 8]));
  }

  private function node(bool $published): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(12);
    $node->method('bundle')->willReturn('page_personnelle');
    $node->method('getTranslationLanguages')->willReturn(['fr' => NULL]);
    $node->method('getTranslation')->willReturn($node);
    $node->method('isPublished')->willReturn($published);

    return $node;
  }

}
