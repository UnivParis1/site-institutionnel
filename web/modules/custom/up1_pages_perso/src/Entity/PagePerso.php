<?php

namespace Drupal\up1_pages_perso\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\link\LinkItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Page perso entity.
 *
 * @ingroup up1_pages_perso
 *
 * @ContentEntityType(
 *   id = "page_perso",
 *   label = @Translation("Page perso"),
 *   bundle_label = @Translation("Page perso type"),
 *   handlers = {
 *     "storage" = "Drupal\up1_pages_perso\PagePersoStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\up1_pages_perso\PagePersoListBuilder",
 *     "views_data" = "Drupal\up1_pages_perso\Entity\PagePersoViewsData",
 *     "translation" = "Drupal\up1_pages_perso\PagePersoTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\up1_pages_perso\Form\PagePersoForm",
 *       "add" = "Drupal\up1_pages_perso\Form\PagePersoForm",
 *       "edit" = "Drupal\up1_pages_perso\Form\PagePersoForm",
 *       "delete" = "Drupal\up1_pages_perso\Form\PagePersoDeleteForm",
 *     },
 *     "access" = "Drupal\up1_pages_perso\PagePersoAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\up1_pages_perso\PagePersoHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "page_perso",
 *   data_table = "page_perso_field_data",
 *   revision_table = "page_perso_revision",
 *   revision_data_table = "page_perso_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer page perso entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/page-perso/{page_perso}",
 *     "add-page" = "/admin/structure/types/page_perso/add",
 *     "add-form" = "/admin/structure/types/page_perso/add/{page_perso_type}",
 *     "edit-form" = "/admin/structure/types/page_perso/{page_perso}/edit",
 *     "delete-form" = "/admin/structure/types/page_perso/{page_perso}/delete",
 *     "version-history" = "/admin/structure/types/page_perso/{page_perso}/revisions",
 *     "revision" = "/admin/structure/types/page_perso/{page_perso}/revisions/{page_perso_revision}/view",
 *     "revision_revert" = "/admin/structure/types/page_perso/{page_perso}/revisions/{page_perso_revision}/revert",
 *     "revision_delete" = "/admin/structure/types/page_perso/{page_perso}/revisions/{page_perso_revision}/delete",
 *     "translation_revert" = "/admin/structure/types/page_perso/{page_perso}/revisions/{page_perso_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/types/page_perso",
 *   },
 *   bundle_entity_type = "page_perso_type",
 *   field_ui_base_route = "entity.page_perso_type.edit_form"
 * )
 */
class PagePerso extends RevisionableContentEntityBase implements PagePersoInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the page_perso owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Page perso entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Page perso entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Page perso is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
