<?php

namespace Drupal\micro_site_pickup\Plugin\EntityReferenceSelection;

use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\micro_node\MicroNodeFields;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Overrides SiteSelection plugin.
 */
class PickupSiteSelection extends DefaultSelection {

  /**
   * Sets the context for the alter hook.
   *
   * @var string
   */
  protected $field_type = 'editor';

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $referer_node = NULL;
    $referer_route_name = '';
    $referer = \Drupal::request()->server->get('HTTP_REFERER');
    $router = \Drupal::service('router');
    if ($router instanceof AccessAwareRouterInterface) {
      try {
        $route_info = $router->match($referer);
        $referer_node = $route_info['node'] ?? NULL;
        $referer_route_name = $route_info['_route'] ?? '';
      }
      catch (AccessDeniedHttpException $e) {

      }
    }
    $query = parent::buildEntityQuery($match, $match_operator);
    $info = $query->getMetaData('entity_reference_selection_handler');
    // Let administrators do anything.
    if ($this->currentUser->hasPermission('administer site entities')) {
      return $query;
    }

    // Filter sites by the user's assignments, which are controlled by other
    // modules. Those modules must know what type of entity they are dealing
    // with, so look up the entity type and bundle.
    $info = $query->getMetaData('entity_reference_selection_handler');

    $context['field_type'] = $this->field_type;
    if (!empty($info->configuration['entity'])) {
      $context['entity_type'] = $info->configuration['entity']->getEntityTypeId();
      $context['bundle'] = $info->configuration['entity']->bundle();
    }

    // Load the current user.
    $account = User::load($this->currentUser->id());

    if ($context['field_type'] == 'editor') {
      if ($account->hasPermission('publish on any site')) {
        return $query;
      }

      elseif ($account->hasPermission('publish on any assigned site')) {
        /** @var \Drupal\micro_node\MicroNodeManagerInterface $micro_node_manager */
        $micro_node_manager = \Drupal::service('micro_node.manager');
        $allowed = $micro_node_manager->getSitesUserCanReference($account);
        $condition_or = $query->orConditionGroup();
        $condition_or->condition('id', $allowed, 'IN');

        if ($referer_route_name === 'entity.node.edit_form'
          && $referer_node instanceof NodeInterface
          && $referer_node->hasField(MicroNodeFields::NODE_SITES)
          && !$referer_node->get(MicroNodeFields::NODE_SITES)->isEmpty()) {
          $sites = $referer_node->get(MicroNodeFields::NODE_SITES)->getValue();
          $site_ids = array_column($sites, 'target_id');
          $condition_or->condition('id', $site_ids, 'IN');
        }

        $query->condition($condition_or);
      }

      else {
        // Remove all options.
        $query->condition('id', '-no-possible-match-');
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $selection_handler_settings = $this->configuration;

    // Merge-in default values.
    $selection_handler_settings += array(
      // For the 'target_bundles' setting, a NULL value is equivalent to "allow
      // entities from any bundle to be referenced" and an empty array value is
      // equivalent to "no entities from any bundle can be referenced".
      'target_bundles' => NULL,
      'sort' => array(
        'field' => 'id',
        'direction' => 'ASC',
      ),
      'auto_create' => FALSE,
      'default_selection' => 'current',
    );

    $form['target_bundles'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );

    $fields = array(
      'id' => $this->t('Site ID'),
      'name' => $this->t('Name'),
      'site_url' => $this->t('Site URL'),
    );

    $form['sort']['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => $fields,
      '#ajax' => FALSE,
      '#default_value' => $selection_handler_settings['sort']['field'],
    );

    $form['sort']['direction'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sort direction'),
      '#required' => TRUE,
      '#options' => array(
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ),
      '#default_value' => $selection_handler_settings['sort']['direction'],
    );

    return $form;
  }

}
