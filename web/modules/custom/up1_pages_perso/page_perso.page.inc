<?php

/**
 * @file
 * Contains page_perso.page.inc.
 *
 * Page callback for Page perso entities.
 */

use Drupal\Core\Render\Element;
use Drupal\up1_pages_perso\Service\HalService;

/**
 * Prepares variables for Page perso templates.
 *
 * Default template: page_perso.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_page_perso(array &$variables) {
  // Fetch PagePerso Entity Object.
  $page_perso = $variables['elements']['#page_perso'];
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
