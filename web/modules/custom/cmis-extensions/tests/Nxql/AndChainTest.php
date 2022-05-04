<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Nxql;
use PHPUnit\Framework\TestCase;

final class AndChainTest extends TestCase {
  public function testWhere() : void {
    $chain = new Nxql\AndChain();
    $chain->where("tag")
          ->eq("bar");

    $chain->where("title")
          ->like("bar");

    $this->assertEquals(
      "(tag = 'bar' AND title ILIKE '%bar%')",
      $chain->toNxql()
    );
  }
}
