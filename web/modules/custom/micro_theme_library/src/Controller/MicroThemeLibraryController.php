<?php

namespace Drupal\micro_theme_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MicroThemeLibraryController.
 */
class MicroThemeLibraryController extends ControllerBase {

  /**
   * Action supplementaire lors de la soumission du formulaire de config du theme d'un mini site
   * => on enregistre le theme selectionné dans l'entité mini site
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function form_submit(&$form, FormStateInterface $form_state) {
    $theme = $form_state->getUserInput()['theme']['general_theme'];
    if (!empty($theme)) {
      $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
      $currentSite = $siteStorage->load($form_state->getUserInput()['site_id']);
      $currentSite->set('theme', ($theme == '_none' ? NULL : $theme));
      $currentSite->save();
    }
  }

}
