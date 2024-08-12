<?php declare(strict_types=1);

namespace Drupal\up1_pages_persos\Gateway;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\up1_pages_persos\Entity\Node\PagePersoInterface;

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

}
