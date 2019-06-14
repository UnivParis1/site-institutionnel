<?php

namespace Drupal\up1_pages_perso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Page perso entities.
 *
 * @ingroup up1_pages_perso
 */
interface PagePersoInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Page perso name.
   *
   * @return string
   *   Name of the Page perso.
   */
  public function getName();

  /**
   * Sets the Page perso name.
   *
   * @param string $name
   *   The Page perso name.
   *
   * @return \Drupal\up1_pages_perso\Entity\PagePersoInterface
   *   The called Page perso entity.
   */
  public function setName($name);

  /**
   * Gets the Page perso creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Page perso.
   */
  public function getCreatedTime();

  /**
   * Sets the Page perso creation timestamp.
   *
   * @param int $timestamp
   *   The Page perso creation timestamp.
   *
   * @return \Drupal\up1_pages_perso\Entity\PagePersoInterface
   *   The called Page perso entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Page perso published status indicator.
   *
   * Unpublished Page perso are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Page perso is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Page perso.
   *
   * @param bool $published
   *   TRUE to set this Page perso to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\up1_pages_perso\Entity\PagePersoInterface
   *   The called Page perso entity.
   */
  public function setPublished($published);

  /**
   * Gets the Page perso revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Page perso revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\up1_pages_perso\Entity\PagePersoInterface
   *   The called Page perso entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Page perso revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Page perso revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\up1_pages_perso\Entity\PagePersoInterface
   *   The called Page perso entity.
   */
  public function setRevisionUserId($uid);

}
