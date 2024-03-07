<?php

/**
 * @file
 * Builds placeholder replacement tokens for page_personnelle node path.
 */

use Drupal\user\Entity\User;
use Drupal\up1_pages_personnelles\ComptexManager;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function up1_pages_personnelles_token_info() {
  $info['tokens']['node']['unique_usermail'] = [
    'name' => t("Unique usermail"),
    'description' => t("Get a unique username based on user's e-mail address regexp."),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function up1_pages_personnelles_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'node' && !empty($data['node'])) {
    $comptex = new ComptexManager();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      // Find the desired token by name
      switch ($name) {
        case 'unique_usermail':
          $owner = $node->getOwner();
          $user = User::load($owner->id());
          \Drupal::logger($owner->id() . " " . $user->getAccountName());
          if (empty($user->getEmail())) {
            $comptex = new ComptexManager();
            $email = $comptex->getUserEmail($user->getAccountName());
            $user->setEmail($email);
            $user->save();
          }
          $mail = $user->getEmail();
          $replacements[$original] = preg_replace('/(.)?(univ-paris1.fr)|(.)?(pantheonsorbonne.fr)/', '', $mail);
          \Drupal::logger('tokens_pages_persos')->info(print_r($mail, 1));
          break;
      }
    }
  }
  return $replacements;
}
