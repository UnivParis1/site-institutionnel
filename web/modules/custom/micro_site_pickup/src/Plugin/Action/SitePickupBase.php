<?php

namespace Drupal\micro_site_pickup\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_node\MicroNodeFields;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\micro_site_pickup\Access\PickupContentAccessCheckInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Class for pickup actions.
 */
abstract class SitePickupBase extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The site negotiator service.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The pickup content access check service.
   *
   * @var \Drupal\micro_site_pickup\Access\PickupContentAccessCheckInterface
   */
  protected $pickupContentAccessCheck;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ModerationOptOutPublish constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The site negotiator service.
   * @param \Drupal\micro_site_pickup\Access\PickupContentAccessCheckInterface $pickup_content_access_check
   *   The pickup content access check service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SiteNegotiatorInterface $negotiator, PickupContentAccessCheckInterface $pickup_content_access_check, $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->negotiator = $negotiator;
    $this->pickupContentAccessCheck = $pickup_content_access_check;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('micro_site.negotiator'),
      $container->get('access_check.pickup_content'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $site = $this->negotiator->loadFromRequest();
    if (!$site instanceof SiteInterface) {
      $result = AccessResult::forbidden('No site found in the route.');
    }
    else {
      $result = $this->pickupContentAccessCheck->access($site);
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Gets all the referenced site IDs from a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to scan for references.
   *
   * @return array
   *   Array of unique ids, empty if there are no references or the field
   *   does not exist on $entity.
   */
  protected function getSitesIds(ContentEntityInterface $entity) {
    $ids = [];
    if ($entity->hasField(MicroNodeFields::NODE_SITES)) {
      foreach ($entity->get(MicroNodeFields::NODE_SITES)->getValue() as $delta => $reference) {
        $ids[$delta] = $reference['target_id'];
      }
    }
    return array_unique(array_filter($ids));
  }

}
