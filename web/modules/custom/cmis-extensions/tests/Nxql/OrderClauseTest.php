<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Nxql;
use PHPUnit\Framework\TestCase;

final class OrderClauseTest extends TestCase {
  public function testDesc() : void {
    $clause = new Nxql\OrderClause("dc:title");
    $clause->desc();

    $this->assertEquals(
      "dc:title DESC",
      $clause->toNxql()
    );
  }

  public function testAsc() : void {
    $clause = new Nxql\OrderClause("dc:title");
    $clause->asc();

    $this->assertEquals(
      "dc:title ASC",
      $clause->toNxql()
    );
  }
}
