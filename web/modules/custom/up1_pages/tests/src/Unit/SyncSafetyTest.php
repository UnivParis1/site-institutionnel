<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Gateway\WsGroupsDirectoryGateway;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Drupal\up1_pages\Plugin\QueueWorker\PagePersoSyncQueue;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * La synchro ne dépublie rien si wsgroups ne répond pas correctement.
 *
 * @group up1_pages
 */
class SyncSafetyTest extends UnitTestCase {

  use WsTestTrait;

  private EntityTypeManagerInterface $entityTypeManager;

  private Connection $database;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
  }

  private function syncManager(): PagePersoSyncManager {
    $client = $this->wsClient();

    return new PagePersoSyncManager(
      new WsGroupsDirectoryGateway($client, $this->configFactory(), $this->entityTypeManager),
      $this->breaker(),
      $this->entityTypeManager,
      $this->database,
      $this->createMock(QueueFactory::class),
      $this->createMock(CasUserManager::class),
      $this->createMock(StateInterface::class),
      $this->configFactory(),
      $this->clock,
      new NullLogger(),
    );
  }

  private function down(): ConnectException {
    return new ConnectException('timeout', new Request('GET', 'https://ws.example.org/'));
  }

  /**
   * @dataProvider badListProvider
   */
  public function testNoPlanWhenListIsMissingOrDoubtful(array $responses): void {
    $this->httpMock->append(...$responses);
    // Aucune lecture des comptes ou des pages : rien n'est comparé.
    $this->database->expects($this->never())->method('select');

    $this->expectException(WsUnavailableException::class);
    $this->syncManager()->buildPlan();
  }

  public static function badListProvider(): array {
    $ok = new Response(200, [], '[{"uid":"ada"}]');
    return [
      'injoignable' => [[new ConnectException('timeout', new Request('GET', '/'))]],
      'erreur 500' => [[new Response(500, [], 'error')]],
      'JSON invalide' => [[new Response(200, [], '<html>proxy</html>')]],
      'liste vide' => [[new Response(200, [], '[]')]],
      'objet erreur' => [[new Response(200, [], '{"error":"LDAP unavailable"}')]],
      'entrées sans uid' => [[new Response(200, [], '[{"uid":"ada"},{"displayName":"X"}]')]],
      'doctorants injoignables' => [[$ok, new ConnectException('timeout', new Request('GET', '/'))]],
    ];
  }

  public function testDeactivationSkippedWhenPersonIsStillInWsgroups(): void {
    $this->httpMock->append(new Response(200, [], '[{"uid":"Ada"}]'));
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $this->syncManager()->apply(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']]);
  }

  public function testNoDeactivationWhenWsgroupsCannotConfirm(): void {
    $this->httpMock->append($this->down());
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $this->expectException(WsUnavailableException::class);
    $this->syncManager()->apply(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']]);
  }

  public function testQueueSuspendsInsteadOfDeactivating(): void {
    $this->httpMock->append($this->down());
    $this->entityTypeManager->expects($this->never())->method('getStorage');
    $worker = new PagePersoSyncQueue([], 'up1_pages_sync', [], $this->syncManager(), new NullLogger());

    $this->expectException(SuspendQueueException::class);
    $worker->processItem(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']]);
  }

  public function testActivationRepublishesEveryTranslation(): void {
    $account = $this->createMock(UserInterface::class);
    $account->method('hasRole')->willReturn(TRUE);
    $account->method('id')->willReturn(7);
    $users = $this->createMock(EntityStorageInterface::class);
    $users->method('loadByProperties')->willReturn([$account]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([5 => 5]);

    $french = $this->createMock(NodeInterface::class);
    $french->method('isPublished')->willReturn(TRUE);
    $french->expects($this->never())->method('setPublished');
    $english = $this->createMock(NodeInterface::class);
    $english->method('isPublished')->willReturn(FALSE);
    $english->expects($this->once())->method('setPublished');

    $page = $this->createMock(NodeInterface::class);
    $page->method('id')->willReturn(5);
    $page->method('getTitle')->willReturn('Mme Ada Lovelace');
    $page->method('getTranslationLanguages')->willReturn(['fr' => NULL, 'en' => NULL]);
    $page->method('getTranslation')->willReturnMap([['fr', $french], ['en', $english]]);
    $page->expects($this->once())->method('save');

    $nodes = $this->createMock(EntityStorageInterface::class);
    $nodes->method('getQuery')->willReturn($query);
    $nodes->method('load')->with(5)->willReturn($page);

    $this->entityTypeManager->method('getStorage')->willReturnMap([['user', $users], ['node', $nodes]]);

    $this->assertSame(5, $this->syncManager()->activate('ada', 'Mme Ada Lovelace', ''));
  }

  public function testNoConfirmDeactivatesWithoutCallingWsgroups(): void {
    $users = $this->createMock(EntityStorageInterface::class);
    $users->expects($this->once())->method('loadByProperties')->with(['name' => 'ada'])->willReturn([]);
    $this->entityTypeManager->method('getStorage')->willReturn($users);

    $result = $this->syncManager()->apply(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']], FALSE);

    $this->assertSame(PagePersoSyncManager::RESULT_DEACTIVATED, $result);
    $this->assertCount(0, $this->httpHistory);
  }

  public function testDeactivationCancelledIsReported(): void {
    $this->httpMock->append(new Response(200, [], '[{"uid":"ada"}]'));

    $result = $this->syncManager()->apply(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']]);

    $this->assertSame(PagePersoSyncManager::RESULT_KEPT, $result);
  }

  public function testDeactivationAppliedWhenWsgroupsConfirmsAbsence(): void {
    $this->httpMock->append(new Response(200, [], '[]'), new Response(200, [], '[]'));
    $users = $this->createMock(EntityStorageInterface::class);
    $users->expects($this->once())->method('loadByProperties')->with(['name' => 'ada'])->willReturn([]);
    $this->entityTypeManager->expects($this->once())->method('getStorage')->with('user')->willReturn($users);

    $this->syncManager()->apply(['action' => PagePersoSyncManager::ACTION_DEACTIVATE, 'login' => 'ada', 'reasons' => ['gone']]);
    // Une vérification par affiliation (EC puis doctorants).
    $this->assertCount(2, $this->httpHistory);
  }

}
