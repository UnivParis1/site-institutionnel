<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Gateway\WsGroupsDirectoryGateway;
use Drupal\up1_pages\Hook\PagePersoHooks;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Manager\PagePersoPurger;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Affichage d'une page personnelle quand les web services ne répondent pas.
 *
 * @group up1_pages
 */
class PagePersoHooksTest extends UnitTestCase {

  use WsTestTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
  }

  private function hooks(?StateInterface $state = NULL, ?EntityLastInstalledSchemaRepositoryInterface $schema = NULL, ?ModuleHandlerInterface $modules = NULL): PagePersoHooks {
    $client = $this->wsClient();
    $cache = $this->fallbackCache();
    $config = $this->configFactory();
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $sync = new PagePersoSyncManager(
      new WsGroupsDirectoryGateway($client, $config, $entity_type_manager),
      $this->breaker(),
      $entity_type_manager,
      $this->createMock(Connection::class),
      $this->createMock(QueueFactory::class),
      $this->createMock(CasUserManager::class),
      $this->createMock(StateInterface::class),
      $config,
      $this->clock,
      new NullLogger(),
    );

    return new PagePersoHooks(
      new ComptexManager($client, $cache, $config, $entity_type_manager),
      $this->halPublications($entity_type_manager),
      $sync,
      $config,
      $this->createMock(AccountInterface::class),
      $state ?? $this->createMock(StateInterface::class),
      new NullLogger(),
      $schema ?? $this->createMock(EntityLastInstalledSchemaRepositoryInterface::class),
      $modules ?? $this->createMock(ModuleHandlerInterface::class),
      new PagePersoPurger($this->createMock(Connection::class), $entity_type_manager, $this->createMock(MemoryCacheInterface::class), new KeyValueMemoryFactory(), $this->clock, new NullLogger()),
    );
  }

  public function testPageNeverCallsHal(): void {
    $this->httpMock->append(new Response(200, [], '[{"uid":"ada","givenName":"Ada","sn":"Lovelace","displayName":"Ada Lovelace"}]'));

    $variables = ['node' => $this->pagePerso('nomprenom')];
    $this->hooks()->preprocessNode($variables);

    $this->assertSame('Ada Lovelace', $variables['displayName']);
    // Pas encore d'encart : rien d'affiché, page mise en file pour le cron.
    $this->assertNull($variables['hal_publications']);
    $this->assertSame(1, $this->queue->numberOfItems());
    // Seul wsgroups a été appelé.
    $this->assertCount(1, $this->httpHistory);
    $this->assertContains('up1_pages_hal:12', $variables['#cache']['tags']);
  }

  public function testStoredPublicationsShownWhenEverythingIsDown(): void {
    $this->settings['ws.wsgroups_enabled'] = FALSE;
    $this->settings['ws.hal_enabled'] = FALSE;
    $hooks = $this->hooks();
    $this->permanentKeyValue->get('up1_pages.hal_publications')->set(12, ['html' => '<div>PUB</div>', 'hash' => 'x', 'updated' => 1, 'checked' => 1]);

    $variables = ['node' => $this->pagePerso('idhal', '1234')];
    $hooks->preprocessNode($variables);

    $this->assertSame('<div>PUB</div>', $variables['hal_publications']);
    $this->assertTrue($variables['ws_unavailable']);
    $this->assertArrayNotHasKey('displayName', $variables);
    $this->assertCount(0, $this->httpHistory);
    // Page remise en cache peu de temps, pour se mettre à jour au retour de
    // wsgroups.
    $this->assertSame(300, $variables['#cache']['max-age']);
  }

  public function testNoHalSearchNoPublications(): void {
    $this->settings['ws.wsgroups_enabled'] = FALSE;
    $variables = ['node' => $this->pagePerso('nul')];
    $this->hooks()->preprocessNode($variables);

    $this->assertNull($variables['hal_publications']);
    $this->assertSame(0, $this->queue->numberOfItems());
  }

  public function testSiteFieldsStayWithLegacyOwnerUntilTakeover(): void {
    $type = $this->createMock(EntityTypeInterface::class);
    $type->method("id")->willReturn("site");
    $installed = (new BaseFieldDefinition())->setProvider("up1_pages_persos");
    $schema = $this->createMock(EntityLastInstalledSchemaRepositoryInterface::class);
    $schema->method("getLastInstalledFieldStorageDefinitions")->willReturn(["groups" => $installed]);
    $modules = $this->createMock(ModuleHandlerInterface::class);
    $modules->method("moduleExists")->willReturn(TRUE);

    // Avant la reprise : les colonnes restent à up1_pages_persos.
    $fields = ["groups" => (new BaseFieldDefinition())->setProvider("up1_pages")];
    $this->hooks(NULL, $schema, $modules)->entityBaseFieldInfoAlter($fields, $type);
    $this->assertSame("up1_pages_persos", $fields["groups"]->getProvider());

    // Après la reprise : à up1_pages.
    $state = $this->createMock(StateInterface::class);
    $state->method("get")->willReturn(TRUE);
    $fields = ["groups" => (new BaseFieldDefinition())->setProvider("up1_pages_personnelles")];
    $this->hooks($state, $schema, $modules)->entityBaseFieldInfoAlter($fields, $type);
    $this->assertSame("up1_pages", $fields["groups"]->getProvider());
  }

}
