<?php

namespace Drupal\up1_pages_persos\Entity\Node;

use Drupal\node\NodeInterface;

interface PagePersoInterface extends NodeInterface {

  public const BUNDLE = 'page_personnelle';

  public const FIELD_TITLE = 'title';
  public const FIELD_STATUS = 'status';
  public const FIELD_IS_TEACHER = 'field_is_teacher';
  public const FIELD_UID_LDAP = 'field_uid_ldap';

  public const FIELD_NAME = 'field_name';
  public const FIELD_FIRSTNAME = 'field_firstname';
  public const FIELD_ID_HAL = 'field_id_hal';
  public const FIELD_OTHER_EMAIL = 'field_other_email_address';

  /**
   * @param $username
   *
   * @return bool
   */
  public function isPagePersoPublished($username): bool;
}
