<?php

namespace Drupal\up1_pages_perso\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Page perso type entity.
 *
 * @ConfigEntityType(
 *   id = "page_perso_type",
 *   label = @Translation("Page perso type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\up1_pages_perso\PagePersoTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\up1_pages_perso\Form\PagePersoTypeForm",
 *       "edit" = "Drupal\up1_pages_perso\Form\PagePersoTypeForm",
 *       "delete" = "Drupal\up1_pages_perso\Form\PagePersoTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\up1_pages_perso\PagePersoTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "page_perso_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "page_perso",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/types/page_perso_type/{page_perso_type}",
 *     "add-form" = "/admin/structure/types/page_perso_type/add",
 *     "edit-form" = "/admin/structure/types/page_perso_type/{page_perso_type}/edit",
 *     "delete-form" = "/admin/structure/types/page_perso_type/{page_perso_type}/delete",
 *     "collection" = "/admin/structure/types/page_perso_type"
 *   }
 * )
 */
class PagePersoType extends ConfigEntityBundleBase implements PagePersoTypeInterface {

  /**
   * The Page perso type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Page perso type label.
   *
   * @var string
   */
  protected $label;

}
