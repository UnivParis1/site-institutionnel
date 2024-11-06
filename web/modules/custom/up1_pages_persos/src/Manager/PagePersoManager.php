<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Manager;

use Drupal\up1_pages_persos\Gateway\PagesPersosGatewayInterface;

/**
 * Returns responses for UP1 Pages Persos routes.
 */
final class PagePersoManager {

  const SERVICE_NAME = 'up1_pages_persos.pages_persos_manager';

  public function __construct(
    private readonly PagesPersosGatewayInterface $pagesPersosGateway
  ) {
  }

  public static function me(): self {
    return \Drupal::service(self::SERVICE_NAME);
  }
  public function updatePagePersosStatus($username, $from_status, $to_status) {
    $this->pagesPersosGateway->updatePagePersoStatus($username, $from_status, $to_status);
  }

  public function isPagePersoPublished($username) {
    $this->pagesPersosGateway->isPagePublished($username);
  }

  public function hasPagePersoOnWebsite($username) {
   return $this->pagesPersosGateway->hasPagePerso($username);
  }

  public function getPagePersoWebsite($username) {
   return $this->pagesPersosGateway->getPagePerso($username);
  }

  public function getPagesPersosAutocomplete($query) {
    return $this->pagesPersosGateway->getPagesPersosAutocomplete($query);
  }

  public function getUnassignedUsers() {
    return $this->pagesPersosGateway->getUnassignedUsers();
  }

  public function getEnseignantsDoctorants() {
    return $this->pagesPersosGateway->getEnseignantsDoctorants();
  }

  public function is_student_or_teacher($username) {
    return $this->pagesPersosGateway->is_student_or_teacher($username);
  }
}
