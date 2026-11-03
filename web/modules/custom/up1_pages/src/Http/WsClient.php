<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Http;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\up1_pages\Exception\WsUnavailableException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Seul point d'appel HTTP du module vers wsgroups et HAL.
 *
 * Délais courts, disjoncteur, et une seule façon d'échouer :
 * WsUnavailableException. Aucun appelant n'a à gérer Guzzle ni un JSON
 * invalide.
 */
final class WsClient {

  public const WSGROUPS = 'wsgroups';

  public const HAL = 'hal';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly CircuitBreaker $breaker,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Corps de la réponse décodé depuis JSON.
   *
   * @param string $service
   *   self::WSGROUPS ou self::HAL.
   * @param string $url
   *   Adresse complète, paramètres compris.
   * @param int|null $timeout
   *   Délai de réponse en secondes ; par défaut ws.timeout.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   */
  public function getJson(string $service, string $url, ?int $timeout = NULL): mixed {
    $body = $this->get($service, $url, $timeout);
    try {
      return json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      // Typiquement une page d'erreur HTML renvoyée par un proxy.
      $this->breaker->recordFailure($service, 'réponse non JSON : ' . $e->getMessage());
      throw new WsUnavailableException("$service : réponse non JSON.", $service, $e);
    }
  }

  /**
   * Corps brut de la réponse.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   */
  public function get(string $service, string $url, ?int $timeout = NULL): string {
    if (!$this->breaker->isAvailable($service)) {
      throw new WsUnavailableException($this->breaker->isEnabled($service)
        ? "$service : appels suspendus après une série d'échecs."
        : "$service : désactivé dans les réglages.", $service);
    }

    $config = $this->configFactory->get('up1_pages.settings');
    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => $timeout ?? max(1, (int) $config->get('ws.timeout')),
        'connect_timeout' => max(1, (int) $config->get('ws.connect_timeout')),
        'headers' => ['Accept' => 'application/json, text/html'],
      ]);
      $body = (string) $response->getBody();
    }
    catch (GuzzleException $e) {
      $this->breaker->recordFailure($service, $e->getMessage());
      throw new WsUnavailableException("$service : " . $e->getMessage(), $service, $e);
    }

    $this->breaker->recordSuccess($service);

    return $body;
  }

  public function isAvailable(string $service): bool {
    return $this->breaker->isAvailable($service);
  }

}
