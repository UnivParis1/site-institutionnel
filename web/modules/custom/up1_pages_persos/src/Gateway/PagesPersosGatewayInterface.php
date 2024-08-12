<?php

namespace Drupal\up1_pages_persos\Gateway;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

interface PagesPersosGatewayInterface {

  public function getStudentsPagesPersos(): array;
  public function getTeachersPagesPersos(): array;
  public function isPagePublished($username): bool;
  public function getPagesPersosByStatus($status): array;
  public function hasPagePerso(string $username);
  public function getPagePerso(string $username);
  public function getPagesPersosAutocomplete($query): array;

}
