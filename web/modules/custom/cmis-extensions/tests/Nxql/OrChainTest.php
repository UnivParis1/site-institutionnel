<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Nxql;
use PHPUnit\Framework\TestCase;

final class OrChainTest extends TestCase {
  public function testWhere() : void {
    $chain = new Nxql\OrChain();
    $chain->where("foo")
          ->eq("bar");

    $chain->where("foo")
          ->like("bar");

    $this->assertEquals(
      "(foo = 'bar' OR foo ILIKE '%bar%')",
      $chain->toNxql()
    );
  }

  public function testAnd() : void {
    $orChain = new Nxql\OrChain();
    $orChain->where("title")
            ->eq("foo");

    $andChain = $orChain->and();

    $andChain->where("baz")
             ->eq("quux");

    $andChain->where("tag")
             ->eq("foo");

    $this->assertEquals(
      "(title = 'foo' OR (baz = 'quux' AND tag = 'foo'))",
      $orChain->toNxql()
    );
  }
}
