<?php

namespace Drupal\up1_pages_perso;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface PagePersoStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Page perso revision IDs for a specific Page perso.
   *
   * @param \Drupal\up1_pages_perso\Entity\PagePersoInterface $entity
   *   The Page perso entity.
   *
   * @return int[]
   *   Page perso revision IDs (in ascending order).
   */
  public function revisionIds(PagePersoInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Page perso author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Page perso revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\up1_pages_perso\Entity\PagePersoInterface $entity
   *   The Page perso entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PagePersoInterface $entity);

  /**
   * Unsets the language for all Page perso with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
