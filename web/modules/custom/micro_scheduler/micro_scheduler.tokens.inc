<?php

/**
 * @file
 * Builds placeholder replacement tokens micro site data.
 *
 * This file handles tokens for micro scheduler tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
/**
 * Implements hook_token_info_alter().
 */
function micro_scheduler_token_info_alter(&$data)
{

  $data['tokens']['micro_site']['administrator-list'] = [
    'name' => t("Administrator List"),
    'description' => t("List of Site Administrator."),
  ];
  $data['tokens']['micro_site']['remaining-days'] = [
    'name' => t("Remaining Days"),
    'description' => t("Remaining days before unpublishing."),
  ];
}



/**
 * Implements hook_tokens().
 */
function micro_scheduler_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
  $negotiator = \Drupal::service('micro_site.negotiator');
  /** @var \Drupal\micro_site\Entity\SiteInterface $site */
  $site = $negotiator->getActiveSite();

  if ($type == 'micro_site' && $site instanceof SiteInterface) {
    $bubbleable_metadata->addCacheableDependency($site);
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'administrator-list':
          $site_administrators = $site->getUsers();
          $replacements[$original] = micro_scheduler_site_admin_list($site_administrators);
          break;
        case 'remaining-days':
          $replacements[$original] = $data['remaining_days'];
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info_alter().
 */
function micro_scheduler_site_admin_list(array $users) {
  $site_administrators_list = '';
  foreach ($users as $user) {
    $site_administrators_list .= $user->getDisplayName() .  ' - ' . $user->getEmail() . PHP_EOL;
  }

  return $site_administrators_list;
}




