<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Http;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Psr\Log\LoggerInterface;

/**
 * Disjoncteur des web services.
 *
 * Après « failure_threshold » échecs rapprochés, plus aucun appel n'est fait
 * vers le service pendant « cooldown » secondes : les pages s'affichent avec
 * les dernières données connues au lieu d'attendre un délai d'expiration à
 * chaque requête. Passé ce délai, un appel est retenté : s'il échoue, le
 * disjoncteur se rouvre aussitôt ; s'il réussit, tout repart normalement.
 *
 * L'état est partagé entre les processus PHP (stockage clé-valeur en base) et
 * survit à un vidage des caches. Chaque service peut aussi être coupé à la
 * main dans les réglages.
 */
final class CircuitBreaker {

  private const COLLECTION = 'up1_pages.circuit';

  private const SETTINGS = 'up1_pages.settings';

  /**
   * État lu au plus une fois par requête et par service.
   */
  private array $states = [];

  public function __construct(
    private readonly KeyValueExpirableFactoryInterface $keyValueFactory,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Le service peut-il être appelé maintenant ?
   */
  public function isAvailable(string $service): bool {
    return $this->isEnabled($service) && !$this->isOpen($service);
  }

  /**
   * Le service est-il activé dans les réglages ?
   */
  public function isEnabled(string $service): bool {
    return (bool) ($this->configFactory->get(self::SETTINGS)->get("ws.{$service}_enabled") ?? TRUE);
  }

  /**
   * Les appels sont-ils suspendus après une série d'échecs ?
   */
  public function isOpen(string $service): bool {
    return ($this->state($service)['open_until'] ?? 0) > $this->time->getCurrentTime();
  }

  public function recordFailure(string $service, string $reason): void {
    $config = $this->configFactory->get(self::SETTINGS);
    $threshold = max(1, (int) $config->get('ws.failure_threshold'));
    $cooldown = max(30, (int) $config->get('ws.cooldown'));
    $now = $this->time->getCurrentTime();

    $state = $this->state($service);
    $was_open = $this->isOpen($service);
    $state = [
      'failures' => ($state['failures'] ?? 0) + 1,
      'open_until' => $state['open_until'] ?? 0,
      'since' => $state['since'] ?? $now,
      'reason' => mb_substr($reason, 0, 500),
    ];

    if ($state['failures'] >= $threshold) {
      $state['open_until'] = $now + $cooldown;
      if (!$was_open) {
        $this->logger->error('@service indisponible (@nb échecs, dernier : @reason). Appels suspendus pendant @cooldown s, les pages utilisent les dernières données connues.', [
          '@service' => $service,
          '@nb' => $state['failures'],
          '@reason' => $state['reason'],
          '@cooldown' => $cooldown,
        ]);
      }
    }

    // Sans nouvel échec, le compteur s'efface de lui-même : des erreurs
    // isolées et espacées n'ouvrent pas le disjoncteur.
    $this->store()->setWithExpire($service, $state, 2 * $cooldown);
    $this->states[$service] = $state;
  }

  public function recordSuccess(string $service): void {
    $state = $this->state($service);
    if (empty($state['failures'])) {
      return;
    }
    if ($state['failures'] >= max(1, (int) $this->configFactory->get(self::SETTINGS)->get('ws.failure_threshold'))) {
      $this->logger->notice('@service répond de nouveau (indisponible depuis @since).', [
        '@service' => $service,
        '@since' => date('d/m/Y H:i:s', (int) $state['since']),
      ]);
    }
    $this->reset($service);
  }

  /**
   * Referme le disjoncteur (bouton des réglages, commande Drush).
   */
  public function reset(string $service): void {
    $this->store()->delete($service);
    $this->states[$service] = [];
  }

  /**
   * État d'un service, pour l'administration.
   *
   * @return array
   *   enabled, open, open_until, failures, since, reason.
   */
  public function status(string $service): array {
    $state = $this->state($service);

    return [
      'enabled' => $this->isEnabled($service),
      'open' => $this->isOpen($service),
      'open_until' => (int) ($state['open_until'] ?? 0),
      'failures' => (int) ($state['failures'] ?? 0),
      'since' => (int) ($state['since'] ?? 0),
      'reason' => (string) ($state['reason'] ?? ''),
    ];
  }

  private function state(string $service): array {
    return $this->states[$service] ??= (array) $this->store()->get($service, []);
  }

  private function store(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get(self::COLLECTION);
  }

}
