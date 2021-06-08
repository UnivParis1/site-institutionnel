<?php

namespace Drupal\micro_site_pickup\Plugin\Action;

use Drupal\micro_node\MicroNodeFields;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Action to add a micro site on node as "others sites".
 *
 * Update and edit access are not required on the node.
 *
 * @Action(
 *   id = "site_pick_node",
 *   label = @Translation("Pick and publish content on the micro site"),
 *   type = "node"
 * )
 */
class SitePickNode extends SitePickupBase {


  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL, SiteInterface $site = NULL) {
    if (!$site instanceof SiteInterface) {
      $site = $this->negotiator->loadFromRequest();
    }
    if (!$site instanceof SiteInterface) {
      $this->messenger->addWarning($this->t('Unable to retrieve the current site to add on the content picked.'));
      return;
    }
    $access = $this->pickupContentAccessCheck->access($site);
    if (!$access->isAllowed()) {
      return;
    }
    $sites_ids = $this->getSitesIds($entity);
    if (!in_array($site->id(), $sites_ids)) {
      if ($entity->hasField(MicroNodeFields::NODE_SITES)) {
        $entity->{MicroNodeFields::NODE_SITES}->appendItem($site->id());
        $entity->save();
      }
    }
  }

}
