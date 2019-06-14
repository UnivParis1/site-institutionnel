<?php

namespace Drupal\up1_pages_perso;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Page perso entity.
 *
 * @see \Drupal\up1_pages_perso\Entity\PagePerso.
 */
class PagePersoAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\up1_pages_perso\Entity\PagePersoInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished page perso entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published page perso entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit page perso entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete page perso entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add page perso entities');
  }

}
