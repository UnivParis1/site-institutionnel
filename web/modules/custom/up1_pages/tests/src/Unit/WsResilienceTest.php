<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Http\WsClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Disjoncteur, client HTTP et cache de secours.
 *
 * @group up1_pages
 */
class WsResilienceTest extends UnitTestCase {

  use WsTestTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
  }

  public function testCircuitOpensAfterThresholdAndStopsCallingTheService(): void {
    $client = $this->wsClient();
    $this->httpMock->append(
      new ConnectException('down', new Request('GET', 'https://ws.example.org/')),
      new ConnectException('down', new Request('GET', 'https://ws.example.org/')),
    );

    foreach ([1, 2, 3] as $attempt) {
      try {
        $client->getJson(WsClient::WSGROUPS, 'https://ws.example.org/');
        $this->fail('Une exception était attendue.');
      }
      catch (WsUnavailableException $e) {
        $this->assertSame(WsClient::WSGROUPS, $e->service);
      }
    }

    // Deux appels réseau seulement : le troisième est coupé par le disjoncteur.
    $this->assertCount(2, $this->httpHistory);
    $this->assertFalse($client->isAvailable(WsClient::WSGROUPS));
    // HAL n'est pas concerné.
    $this->assertTrue($client->isAvailable(WsClient::HAL));
  }

  public function testCircuitRetriesAfterCooldownAndClosesOnSuccess(): void {
    $breaker = $this->breaker();
    $breaker->recordFailure(WsClient::WSGROUPS, 'x');
    $breaker->recordFailure(WsClient::WSGROUPS, 'x');
    $this->assertTrue($breaker->isOpen(WsClient::WSGROUPS));

    $this->clock->now += 301;
    $this->assertTrue($breaker->isAvailable(WsClient::WSGROUPS));

    // Nouvel échec après la pause : réouverture immédiate.
    $breaker->recordFailure(WsClient::WSGROUPS, 'x');
    $this->assertTrue($breaker->isOpen(WsClient::WSGROUPS));

    $this->clock->now += 301;
    $this->httpMock->append(new Response(200, [], '[{"uid":"jdupont"}]'));
    $this->assertSame([['uid' => 'jdupont']], $this->wsClient($breaker)->getJson(WsClient::WSGROUPS, 'https://ws.example.org/'));
    $this->assertSame(0, $breaker->status(WsClient::WSGROUPS)['failures']);
  }

  public function testDisabledServiceIsNeverCalled(): void {
    $this->settings['ws.wsgroups_enabled'] = FALSE;
    $client = $this->wsClient();

    $this->expectException(WsUnavailableException::class);
    try {
      $client->get(WsClient::WSGROUPS, 'https://ws.example.org/');
    }
    finally {
      $this->assertCount(0, $this->httpHistory);
    }
  }

  public function testInvalidJsonCountsAsFailure(): void {
    $breaker = $this->breaker();
    $this->httpMock->append(new Response(200, [], '<html>Proxy error</html>'));

    $this->expectException(WsUnavailableException::class);
    try {
      $this->wsClient($breaker)->getJson(WsClient::WSGROUPS, 'https://ws.example.org/');
    }
    finally {
      $this->assertSame(1, $breaker->status(WsClient::WSGROUPS)['failures']);
    }
  }

  public function testFallbackCacheServesLastKnownValueWhenServiceIsDown(): void {
    $cache = $this->fallbackCache();
    $this->assertSame('v1', $cache->remember('k', fn() => 'v1', $stale));
    $this->assertFalse($stale);

    // Encore frais : le chargeur n'est pas appelé.
    $this->assertSame('v1', $cache->remember('k', fn() => $this->fail('Chargeur appelé.'), $stale));

    $this->clock->now += 3601;
    $down = fn() => throw new WsUnavailableException('down', WsClient::WSGROUPS);
    $this->assertSame('v1', $cache->remember('k', $down, $stale));
    $this->assertTrue($stale);

    $this->expectException(WsUnavailableException::class);
    $cache->remember('inconnue', $down, $stale);
  }

}
