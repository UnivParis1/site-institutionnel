<?php

namespace Drupal\up1_move_to_trash\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Component\Datetime\Time;

/**
 * Move content to trash.
 *
 * @Action(
 *   id = "move_to_trash",
 *   label = @Translation("Move content to trash"),
 *   type = "node"
 * )
 */
class MoveToTrash extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  /*public function execute($entity = NULL) {
    \Drupal::logger('up1_move_to_trash_moderation_state')->info(print_r($entity->hasField('moderation_state'), 1));
    \Drupal::logger('up1_move_to_trash_moderation_state')->info(print_r($entity->moderation_state->value, 1));

    if ($entity->hasField('move_to_trash')) {
      $entity->move_to_trash->value = 1;
      $entity->moderation_state->value = 'moved_to_trash';
      $entity->setUnpublished()->save();
    }

  }*/

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

  protected function getFieldsToUpdate() {
    \Drupal::logger('up1_move_to_trash_getFieldsToUpdate')->info("On rentre là");
    return [
      'move_to_trash' => 1,
      'moderation_state' => "moved_to_trash",
      'deleted' => 1591980814,
      ];
  }
}
