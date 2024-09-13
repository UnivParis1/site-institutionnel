<?php

namespace Drupal\sorbonne_tv\Service;

use Drupal\user\Entity\User;

/**
 * Class SorbonneTvMailingService
 */
class SorbonneTvMailingService
{

  private $config;

  /**
   * Constructor
   *
   */
  public function __construct() {}

  public function getUsersMailByRole(string $role):array {
    $users_mails = [];

    $query = \Drupal::entityQuery('user');
    $query->accessCheck(FALSE);
    $query->condition('status', 1)
    ->condition('roles', $role);

    $users_ids = $query->execute();
    foreach ($users_ids as $id) {
      if($user = User::load($id)) {
        if($user_mail = $user->getEmail()) {
          $users_mails[] = $user_mail;
        }
      }
    }

    return $users_mails;
  }
}
