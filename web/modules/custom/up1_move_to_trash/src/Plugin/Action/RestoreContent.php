<?php

namespace Drupal\up1_move_to_trash\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 *Restore content from trash.
 *
 * @Action(
 *   id = "restore_content",
 *   label = @Translation("Restore content from trash"),
 *   type = "node"
 * )
 */
class RestoreContent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    \Drupal::logger('up1_move_to_trash')->info(print_r($entity->hasField('move_to_trash'), 1));
    if ($entity->hasField('move_to_trash')) {
      $entity->move_to_trash->value = 0;
      if ($entity->hasField('deleted')) {
        $entity->deleted->value = NULL;
      }

      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    $result = $object
      ->access('update', $account, TRUE)
      ->andIf($object->move_to_trash->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
