<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Gateway\WsGroupsDirectoryGateway;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Drupal\up1_pages\StatusReport;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Comptes enregistrés par la synchro (SQLite en mémoire).
 *
 * @group up1_pages
 * @requires extension pdo_sqlite
 */
class SyncStatisticsTest extends UnitTestCase {

  use WsTestTrait;

  private static int $connection = 0;

  private Connection $db;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();

    $key = 'up1_pages_test_' . ++self::$connection;
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
    $schema->createTable('node_field_data', ['fields' => [
      'nid' => ['type' => 'int'],
      'uid' => ['type' => 'int'],
      'type' => ['type' => 'varchar', 'length' => 32],
      'status' => ['type' => 'int'],
      'title' => ['type' => 'varchar', 'length' => 255],
      'default_langcode' => ['type' => 'int'],
    ]]);

    $users = [1 => 'ada', 2 => 'doc1', 3 => 'both', 4 => 'gone', 5 => 'dup'];
    foreach ($users as $uid => $name) {
      $this->db->insert('users_field_data')->fields(['uid' => $uid, 'name' => $name, 'default_langcode' => 1])->execute();
    }
    foreach ([1, 2, 4] as $uid) {
      $this->db->insert('user__roles')->fields(['entity_id' => $uid, 'roles_target_id' => 'enseignant_doctorant'])->execute();
    }
    // nid => [uid, publiée].
    $pages = [10 => [1, 1], 20 => [2, 0], 30 => [3, 1], 40 => [4, 1], 50 => [5, 1], 51 => [5, 1]];
    foreach ($pages as $nid => [$uid, $status]) {
      $this->db->insert('node_field_data')->fields([
        'nid' => $nid,
        'uid' => $uid,
        'type' => 'page_personnelle',
        'status' => $status,
        'title' => '',
        'default_langcode' => 1,
      ])->execute();
    }
  }

  private function syncManager(): PagePersoSyncManager {
    $client = $this->wsClient();
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturnCallback(fn($key, $default = NULL) => $this->stateValues[$key] ?? $default);
    $state->method('set')->willReturnCallback(function ($key, $value) {
      $this->stateValues[$key] = $value;
    });

    return new PagePersoSyncManager(
      new WsGroupsDirectoryGateway($client, $this->configFactory(), $entity_type_manager),
      $this->breaker(),
      $entity_type_manager,
      $this->db,
      $this->createMock(QueueFactory::class),
      $this->createMock(CasUserManager::class),
      $state,
      $this->configFactory(),
      $this->clock,
      new NullLogger(),
    );
  }

  public function testCountsAreRecordedAtComparison(): void {
    $this->httpMock->append(
      new Response(200, [], '[{"uid":"ada"},{"uid":"both"}]'),
      new Response(200, [], '[{"uid":"doc1"},{"uid":"both"}]'),
    );
    $sync = $this->syncManager();
    $sync->buildPlan();
    $result = $sync->getStatistics();
    $stats = $result['statistics'];

    $this->assertSame(['faculty' => 2, 'student' => 2, 'both' => 1, 'total' => 3], $stats['wsgroups']);
    $this->assertSame([
      'role' => 3,
      'published' => 5,
      'published_faculty' => 1,
      'published_student' => 0,
      'published_both' => 1,
      'published_absent' => 3,
      'unpublished' => 1,
      'duplicates' => 1,
    ], $stats['drupal']);
    $this->assertSame(['gone' => 1, 'publish' => 1, 'role' => 1], $stats['plan']);
    $this->assertSame('ok', $result['last_result']['status']);

    $sections = StatusReport::sections($result, [
      'with_search' => 0, 'stored' => 0, 'empty' => 0, 'missing' => 0, 'queued' => 0, 'last_enqueue' => 0,
    ], static fn($t) => 'date');
    $this->assertContains(['Doctorants ayant droit à une page', 2], $sections['wsgroups (au date)']);
    $this->assertContains(['Pages à republier', 1], $sections['Écarts trouvés (au date)']);
  }

  public function testFailedSyncKeepsLastCountsAndRecordsReason(): void {
    $this->httpMock->append(
      new Response(200, [], '[{"uid":"ada"},{"uid":"both"}]'),
      new Response(200, [], '[{"uid":"doc1"},{"uid":"both"}]'),
      new ConnectException('timeout', new Request('GET', '/')),
    );
    $sync = $this->syncManager();
    $sync->buildPlan();
    $before = $sync->getStatistics()['statistics'];

    $this->clock->now += 86400;
    try {
      $sync->buildPlan();
    }
    catch (\Exception) {
    }
    $result = $sync->getStatistics();

    $this->assertSame($before, $result['statistics']);
    $this->assertSame('aborted', $result['last_result']['status']);
    $this->assertStringContainsString('timeout', $result['last_result']['message']);
  }

}
