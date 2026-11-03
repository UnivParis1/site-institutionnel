<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\KeyValueStore\MemoryStorage;
use Drupal\Core\Queue\Memory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\up1_pages\Cache\FallbackCache;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Manager\HalManager;
use Drupal\up1_pages\Manager\HalPublications;
use Drupal\user\UserInterface;
use Drupal\up1_pages\Http\CircuitBreaker;
use Drupal\up1_pages\Http\WsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Log\NullLogger;

/**
 * Doublures communes : horloge réglable, clé-valeur en mémoire, Guzzle simulé.
 */
trait WsTestTrait {

  protected object $clock;

  protected KeyValueExpirableFactoryInterface $keyValue;

  protected MockHandler $httpMock;

  protected array $httpHistory = [];

  protected array $settings = [
    'url_ws' => 'https://ws.example.org/',
    'search_user' => 'searchUser?x=1',
    'search_user_page' => 'searchUser?x=1',
    'filtre_faculty' => '&filter_student=no',
    'filtre_student' => '&filter_student=only',
    'other_filters' => '',
    'url_userphoto' => 'https://photos.example.org/',
    'url_hal_api' => 'https://hal.example.org/search',
    'ws.wsgroups_enabled' => TRUE,
    'ws.hal_enabled' => TRUE,
    'ws.failure_threshold' => 2,
    'ws.cooldown' => 300,
    'ws.connect_timeout' => 1,
    'ws.timeout' => 1,
    'ws.list_timeout' => 1,
    'ws.sync_timeout' => 1,
    'ws.cache_ttl' => 3600,
    'ws.stale_ttl' => 86400,
  ];

  protected function setUpWs(): void {
    $this->clock = new class implements TimeInterface {

      public int $now = 1_000_000;

      public function getRequestTime(): int {
        return $this->now;
      }

      public function getRequestMicroTime(): float {
        return $this->now;
      }

      public function getCurrentTime(): int {
        return $this->now;
      }

      public function getCurrentMicroTime(): float {
        return $this->now;
      }

    };

    $this->keyValue = new class implements KeyValueExpirableFactoryInterface {

      private array $stores = [];

      public function get($collection) {
        return $this->stores[$collection] ??= new class($collection) extends MemoryStorage implements KeyValueStoreExpirableInterface {

          public function setWithExpire($key, $value, $expire) {
            $this->set($key, $value);
          }

          public function setWithExpireIfNotExists($key, $value, $expire) {
            return $this->setIfNotExists($key, $value);
          }

          public function setMultipleWithExpire(array $data, $expire) {
            $this->setMultiple($data);
          }

        };
      }

    };

    $this->httpMock = new MockHandler();
    $this->httpHistory = [];
  }

  protected function configFactory(): mixed {
    return $this->getConfigFactoryStub(['up1_pages.settings' => $this->settings]);
  }

  protected function breaker(): CircuitBreaker {
    return new CircuitBreaker($this->keyValue, $this->configFactory(), $this->clock, new NullLogger());
  }

  protected function wsClient(?CircuitBreaker $breaker = NULL): WsClient {
    $stack = HandlerStack::create($this->httpMock);
    $stack->push(Middleware::history($this->httpHistory));

    return new WsClient(new Client(['handler' => $stack]), $breaker ?? $this->breaker(), $this->configFactory());
  }

  protected function fallbackCache(): FallbackCache {
    return new FallbackCache($this->keyValue, $this->configFactory(), $this->clock);
  }

  /**
   * File d'attente en mémoire, partagée par tous les services du test.
   */
  protected ?Memory $queue = NULL;

  /**
   * Étiquettes de cache invalidées.
   */
  protected array $invalidatedTags = [];

  protected ?KeyValueMemoryFactory $permanentKeyValue = NULL;

  protected array $stateValues = [];

  protected function halPublications(EntityTypeManagerInterface $entity_type_manager): HalPublications {
    $client = $this->wsClient();
    $config = $this->configFactory();
    // La file en mémoire de Drupal lit l'heure dans le conteneur.
    $container = new ContainerBuilder();
    $container->set('datetime.time', $this->clock);
    \Drupal::setContainer($container);
    $this->queue ??= new Memory(HalPublications::QUEUE);
    $this->permanentKeyValue ??= new KeyValueMemoryFactory();

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')->willReturn($this->queue);
    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->method('invalidateTags')->willReturnCallback(function (array $tags) {
      $this->invalidatedTags = array_merge($this->invalidatedTags, $tags);
    });
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturnCallback(fn($key, $default = NULL) => $this->stateValues[$key] ?? $default);
    $state->method('set')->willReturnCallback(function ($key, $value) {
      $this->stateValues[$key] = $value;
    });

    return new HalPublications(
      new HalManager($client, $config, new PhpTransliteration()),
      new ComptexManager($client, $this->fallbackCache(), $config, $entity_type_manager),
      $this->permanentKeyValue,
      $this->keyValue,
      $queue_factory,
      $entity_type_manager,
      $invalidator,
      $state,
      $config,
      $this->clock,
      new NullLogger(),
    );
  }

  /**
   * Page personnelle de « ada », nid 12.
   */
  protected function pagePerso(string $method, string $id_hal = ''): NodeInterface {
    $owner = $this->createMock(UserInterface::class);
    $owner->method('getAccountName')->willReturn('ada');

    $values = ['field_my_hal_publications' => $method, 'field_id_hal' => $id_hal];
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page_personnelle');
    $node->method('id')->willReturn(12);
    $node->method('getOwner')->willReturn($owner);
    $node->method('hasField')->willReturn(TRUE);
    $node->method('get')->willReturnCallback(function (string $name) use ($values) {
      $field = $this->createMock(FieldItemListInterface::class);
      $field->method('__get')->willReturn($values[$name] ?? NULL);
      return $field;
    });

    return $node;
  }

  /**
   * Gestionnaire d'entités dont le stockage des nœuds charge $node.
   */
  protected function entityTypeManagerWith(?NodeInterface $node = NULL): EntityTypeManagerInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($node);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    return $entity_type_manager;
  }

}
