<?php

declare(strict_types=1);

namespace Drupal\up1_pages_persos\Manager;

use Drupal\up1_pages_persos\Gateway\PagesPersosGatewayInterface;

/**
 * Returns responses for UP1 Pages Persos routes.
 */
final class PagePersoManager {

  const SERVICE_NAME = 'up1_pages_persos.pages_persos_gateway';

  public function __construct(
    private readonly PagesPersosGatewayInterface $pagesPersosGateway
  ) {
  }

  public function me(): self {
    return \Drupal::service(self::SERVICE_NAME);
  }
  public function updatePagesPersosStatuses() {}

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
}
