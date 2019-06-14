<?php

namespace Drupal\up1_pages_perso;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\up1_pages_perso\Entity\PagePersoInterface;

/**
 * Defines the storage handler class for Page perso entities.
 *
 * This extends the base storage class, adding required special handling for
 * Page perso entities.
 *
 * @ingroup up1_pages_perso
 */
class PagePersoStorage extends SqlContentEntityStorage implements PagePersoStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PagePersoInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {page_perso_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {page_perso_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PagePersoInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {page_perso_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('page_perso_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
