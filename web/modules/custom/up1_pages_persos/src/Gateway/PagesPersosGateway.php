<?php declare(strict_types=1);

namespace Drupal\up1_pages_persos\Gateway;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\up1_pages_persos\Entity\Node\PagePersoInterface;
use Drupal\user\Entity\User;

final class PagesPersosGateway implements PagesPersosGatewayInterface {

  private NodeStorageInterface $nodeStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    /** @var NodeStorageInterface $nodeStorage */
    $nodeStorage = $entityTypeManager->getStorage('node');
    $this->nodeStorage = $nodeStorage;
  }

  /**
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  protected function getPagesPersosBaseQuery(): QueryInterface {
    return $this->nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', PagePersoInterface::BUNDLE);
  }

  public function getStudentsPagesPersos(): array {
    $query = $this->getPagesPersosBaseQuery()
      ->condition(PagePersoInterface::FIELD_IS_TEACHER, FALSE);

    $result = $query->execute();
    return $this->nodeStorage->loadMultiple($result);
  }

  public function getTeachersPagesPersos(): array {
    $query = $this->getPagesPersosBaseQuery()
    ->condition(PagePersoInterface::FIELD_IS_TEACHER, TRUE);

    $result = $query->execute();
    return $this->nodeStorage->loadMultiple($result);
  }

  public function isPagePublished($username): bool {
    $query = $this->getPagesPersosBaseQuery()
      ->condition('uid', $username)
      ->condition('type', 'page_personnelle')
      ->accessCheck(FALSE)
    ->condition(PagePersoInterface::FIELD_STATUS, NodeInterface::PUBLISHED);

    $result = $query->execute();
    $node = $this->nodeStorage->load($result);

    if ($node && $node->isPublished()) {
      return TRUE;
    }

    return FALSE;
  }

  public function getPagesPersosByStatus($status): array {
    $query = $this->getPagesPersosBaseQuery()
      ->condition(PagePersoInterface::FIELD_STATUS, $status);

    $result = $query->execute();

    return $this->nodeStorage->loadMultiple($result);
  }

  public function hasPagePerso(string $username) {
    $query = $this->getPagesPersosBaseQuery()
      ->condition(PagePersoInterface::FIELD_STATUS, NodeInterface::PUBLISHED)
      ->condition(PagePersoInterface::FIELD_UID_LDAP, $username);

    $result = $query->execute();
    $nodes = $this->nodeStorage->loadMultiple($result);

    return count($nodes) > 0;
  }

  public function getPagePerso(string $username) {
    $query = $this->getPagesPersosBaseQuery()
      ->condition(PagePersoInterface::FIELD_STATUS, NodeInterface::PUBLISHED)
      ->condition(PagePersoInterface::FIELD_UID_LDAP, $username);

    $result = $query->execute();

    return $this->nodeStorage->loadMultiple($result);
  }

  public function getPagesPersosAutocomplete($query): array {
    $query = $this->getPagesPersosBaseQuery()
      ->condition('type', 'page_personnelle')
      ->accessCheck(TRUE)
      ->condition('title', $query, 'CONTAINS')
      ->condition(PagePersoInterface::FIELD_STATUS, NodeInterface::PUBLISHED)
      ->sort(PagePersoInterface::FIELD_NAME, 'ASC' )
      ->range(0,16);

    $nid = $query->execute();

    return $nid ? $this->nodeStorage->loadMultiple($nid) : [];
  }

  public function getUnassignedUsers(): array {
    $query = \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->condition('status', 1);
    $query->notExists('roles');

    $uids = $query->execute();


    return $this->getUsernames($uids);
  }

  public function getEnseignantsDoctorants(): array {
    $query = \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->condition('status', 1)
    ->condition('roles', 'enseignant_doctorant');

    $uids = $query->execute();

    return $this->getUsernames($uids);
  }

  public function updatePagePersoStatus($username, $from_status, $to_status) {
  }

  public function updatePagePersoFields(string $username, array $data) {

  }

  private function getUsernames($uids): array {
    $users = User::loadMultiple($uids);
    $usernames = [];

    if ( !empty($users) ) {
      foreach ($users as $user) {
        $usernames[$user->id()] = $user->get('name')->value;
      }
    }

    return $usernames;
  }

  public function is_student_or_teacher($username): bool {
    if (is_numeric($username)) {
     $user = User::load($username);
    } else {
      $user = user_load_by_name($username);
    }

    return ($user && $user->hasRole('enseignant_doctorant'));
  }

}
