<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\up1_pages\Exception\WsUnavailableException;

/**
 * Cache des réponses des web services, avec repli sur la dernière réponse.
 *
 * Une donnée est « fraîche » pendant ws.cache_ttl, puis rechargée. Si le web
 * service est indisponible, la dernière valeur connue est servie, tant
 * qu'elle a moins de ws.stale_ttl.
 *
 * Stockée en clé-valeur et non dans un cache Drupal : un « drush cr » pendant
 * une panne ne doit pas effacer le repli.
 */
final class FallbackCache {

  private const COLLECTION = 'up1_pages.ws_data';

  public function __construct(
    private readonly KeyValueExpirableFactoryInterface $keyValueFactory,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
  ) {
  }

  /**
   * Valeur en cache, rechargée par $loader quand elle n'est plus fraîche.
   *
   * @param string $key
   *   Clé, 128 caractères au plus.
   * @param callable $loader
   *   Appelle le web service ; lève WsUnavailableException en cas d'échec.
   * @param bool|null $stale
   *   Mis à TRUE quand la valeur renvoyée est un repli.
   * @param int|null $ttl
   *   Durée de fraîcheur ; par défaut ws.cache_ttl.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   *   Si le web service est indisponible et qu'aucune valeur n'est connue.
   */
  public function remember(string $key, callable $loader, ?bool &$stale = NULL, ?int $ttl = NULL): mixed {
    $config = $this->configFactory->get('up1_pages.settings');
    $ttl ??= (int) $config->get('ws.cache_ttl');
    $now = $this->time->getCurrentTime();
    $stale = FALSE;

    $entry = $this->store()->get($key);
    if (is_array($entry) && $now - $entry['fetched'] < $ttl) {
      return $entry['data'];
    }

    try {
      $data = $loader();
    }
    catch (WsUnavailableException $e) {
      if (is_array($entry)) {
        $stale = TRUE;
        return $entry['data'];
      }
      throw $e;
    }

    $this->store()->setWithExpire($key, ['fetched' => $now, 'data' => $data], max($ttl, (int) $config->get('ws.stale_ttl')));

    return $data;
  }

  public function delete(string $key): void {
    $this->store()->delete($key);
  }

  private function store(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get(self::COLLECTION);
  }

}
