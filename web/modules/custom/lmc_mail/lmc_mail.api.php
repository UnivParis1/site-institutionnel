<?php

/**
 * @file
 * Document all supported APIs.
 */

/**
 * Alter the key lists for emails
 *
 * @param array $keys
 */
function hook_lmc_mail_keys_alter(array &$keys, array &$context) {
  // Add custom key
  $keys['lmc_mail_custom_key'] = t('LMC mail custom label');
}
