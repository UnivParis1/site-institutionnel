<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Nxql;
use PHPUnit\Framework\TestCase;

final class WhereClauseTest extends TestCase {
  public function testLike() : void {
    $clause = new Nxql\WhereClause("foo");
    $clause->like("bar");

    $this->assertEquals(
      "foo ILIKE '%bar%'",
      $clause->toNxql()
    );
  }

  public function testEq() : void {
    $clause = new Nxql\WhereClause("foo");
    $clause->eq("bar");

    $this->assertEquals(
      "foo = 'bar'",
      $clause->toNxql()
    );
  }

  public function testStrictLike() : void {
    $clause = new Nxql\WhereClause("foo");
    $clause->strictLike("bar_%");

    $this->assertEquals(
      "foo LIKE 'bar_%'",
      $clause->toNxql()
    );
  }

}
