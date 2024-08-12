<?php declare(strict_types=1);

namespace Drupal\up1_pages_persos\Entity\Node;

use Drupal\up1_pages_persos\Manager\PagePersoManager;
use Drupal\node\Entity\Node;

class PagePerso extends Node implements PagePersoInterface {

  public function isPagePersoPublished($username): bool {
    $manager = PagePersoManager::me();

    return $manager->isPagePersoPublished($username);
  }

}