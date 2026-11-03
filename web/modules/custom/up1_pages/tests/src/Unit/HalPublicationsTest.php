<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Manager\HalPublications;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Encart « Publications HAL » : affichage enregistré, rafraîchi par le cron.
 *
 * @group up1_pages
 */
class HalPublicationsTest extends UnitTestCase {

  use WsTestTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
  }

  private function hal(string $html): Response {
    return new Response(200, [], '<div id="res_script">' . $html . '</div>');
  }

  public function testEncartIsUpdatedOnlyWhenContentDiffers(): void {
    $node = $this->pagePerso('idhal', '1234');
    $publications = $this->halPublications($this->entityTypeManagerWith($node));
    $this->httpMock->append($this->hal('PUB 1'), $this->hal('PUB 1'), $this->hal('PUB 2'));

    $this->assertSame(HalPublications::UPDATED, $publications->refresh(12));
    $this->assertStringContainsString('PUB 1', $publications->get($node));
    $this->assertSame(['up1_pages_hal:12'], $this->invalidatedTags);

    // Même contenu : rien n'est réécrit, aucun cache vidé.
    $this->assertSame(HalPublications::UNCHANGED, $publications->refresh(12));
    $this->assertCount(1, $this->invalidatedTags);

    $this->assertSame(HalPublications::UPDATED, $publications->refresh(12));
    $this->assertStringContainsString('PUB 2', $publications->get($node));
    $this->assertCount(2, $this->invalidatedTags);
  }

  /**
   * @dataProvider unavailableHal
   */
  public function testEncartKeptWhenHalIsUnavailable(mixed $response): void {
    $node = $this->pagePerso('idhal', '1234');
    $publications = $this->halPublications($this->entityTypeManagerWith($node));
    $this->httpMock->append($this->hal('PUB'), $response);
    $publications->refresh(12);

    try {
      $publications->refresh(12);
      $this->fail('Une exception était attendue.');
    }
    catch (WsUnavailableException) {
    }
    $this->assertStringContainsString('PUB', $publications->get($node));
    $this->assertCount(1, $this->invalidatedTags);
  }

  public static function unavailableHal(): array {
    return [
      'injoignable' => [new ConnectException('down', new Request('GET', '/'))],
      'erreur 500' => [new Response(500, [], 'error')],
      'page de maintenance' => [new Response(200, [], '<html>Maintenance</html>')],
    ];
  }

  public function testEmptyListDoesNotReplacePublications(): void {
    $node = $this->pagePerso('idhal', '1234');
    $publications = $this->halPublications($this->entityTypeManagerWith($node));
    $this->httpMock->append($this->hal('PUB'), $this->hal('&nbsp;'));

    $publications->refresh(12);
    $this->assertSame(HalPublications::KEPT, $publications->refresh(12));
    $this->assertStringContainsString('PUB', $publications->get($node));
  }

  public function testNameSearchUsesWsgroupsNames(): void {
    $node = $this->pagePerso('nomprenom');
    $publications = $this->halPublications($this->entityTypeManagerWith($node));
    $this->httpMock->append(
      new Response(200, [], '[{"uid":"ada","givenName":"Ada","sn":"Lovelace"}]'),
      $this->hal('PUB'),
    );

    $this->assertSame(HalPublications::UPDATED, $publications->refresh(12));
    $this->assertStringContainsString('auteur_exp=Ada+Lovelace', $this->httpHistory[1]['request']->getUri()->getQuery());
  }

  public function testPageWithoutHalSearchIsSkipped(): void {
    $publications = $this->halPublications($this->entityTypeManagerWith($this->pagePerso('nul')));

    $this->assertSame(HalPublications::SKIPPED, $publications->refresh(12));
    $this->assertCount(0, $this->httpHistory);
  }

  public function testMissingEncartIsQueuedOnce(): void {
    $node = $this->pagePerso('idhal', '1234');
    $publications = $this->halPublications($this->entityTypeManagerWith($node));

    $this->assertNull($publications->get($node));
    $this->assertNull($publications->get($node));
    $this->assertSame(1, $this->queue->numberOfItems());
    $this->assertCount(0, $this->httpHistory);
  }

  public function testCronEnqueuesOncePerIntervalAndOnlyIfHalIsUp(): void {
    $this->settings['hal.refresh_interval'] = 86400;
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([12 => 12, 13 => 13]);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $this->settings['ws.hal_enabled'] = FALSE;
    $this->assertNull($this->halPublications($entity_type_manager)->enqueueIfDue());
    $this->assertSame(0, $this->queue->numberOfItems());

    $this->settings['ws.hal_enabled'] = TRUE;
    $publications = $this->halPublications($entity_type_manager);
    $this->assertSame(2, $publications->enqueueIfDue());
    $this->assertNull($publications->enqueueIfDue());
    $this->assertSame(2, $this->queue->numberOfItems());
  }

}
