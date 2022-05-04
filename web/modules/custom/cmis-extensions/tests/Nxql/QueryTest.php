<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Nxql;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase {
  public function testSoleQueryToNxql(): void {
    $query = new Nxql\Query();

    $this->assertEquals(
      "SELECT * FROM File",
      $query->toNxql()
    );
  }

  public function testWhereEq() : void {
    $query = new Nxql\Query();
    $query->where("title")
          ->eq("bar");

    $this->assertEquals(
      "SELECT * FROM File WHERE title = 'bar'",
      $query->toNxql()
    );
  }

  public function testWhereLike() : void {
    $query = new Nxql\Query();
    $query->where("title")
          ->like("bar");

    $this->assertEquals(
      "SELECT * FROM File WHERE title ILIKE '%bar%'",
      $query->toNxql()
    );
  }

  public function testWhereStrictLike() : void {
    $query = new Nxql\Query();
    $query->where("dc:title")
          ->strictLike("foo_%");

    $this->assertEquals(
      "SELECT * FROM File WHERE dc:title LIKE 'foo_%'",
      $query->toNxql()
    );
  }

  public function testMultipleWhere() : void {
    $query = new Nxql\Query();
    $query->where("title")
          ->eq("bar");

    $query->where("abstract")
          ->like("woot");

    $this->assertEquals(
      "SELECT * FROM File WHERE title = 'bar' AND abstract ILIKE '%woot%'",
      $query->toNxql()
    );
  }

  public function testOr() : void {
    $query = new Nxql\Query();

    $query->where("foo")
          ->eq("bar");

    $orChain = $query->or();

    $orChain->where("dc:title")
            ->eq("foo");

    $orChain->where("dc:title")
            ->eq("bar");

    $this->assertEquals(
      "SELECT * FROM File WHERE foo = 'bar' AND (dc:title = 'foo' OR " .
        "dc:title = 'bar')",
      $query->toNxql()
    );
  }

  public function testOrderBy() : void {
    $query = new Nxql\Query();
    $query->orderBy('dc:created');

    $this->assertEquals(
      "SELECT * FROM File ORDER BY dc:created ASC",
      $query->toNxql()
    );
  }

  public function testOrderByExplicitAsc() : void {
    $query = new Nxql\Query();
    $query->orderBy('dc:created')
          ->asc();

    $this->assertEquals(
      "SELECT * FROM File ORDER BY dc:created ASC",
      $query->toNxql()
    );
  }

  public function testOrderByExplicitDesc() : void {
    $query = new Nxql\Query();
    $query->orderBy('dc:created')
          ->desc();

    $this->assertEquals(
      "SELECT * FROM File ORDER BY dc:created DESC",
      $query->toNxql()
    );
  }

  public function testComplexQuery() : void {
    $query = new Nxql\Query();

    $query->where("title")
          ->like("woot");

    $query->where("dc:createdBy")
          ->eq("mdupont");

    $query->orderBy("dc:created");

    $query->orderBy("dc:modified")
          ->desc();

    $this->assertEquals(
      "SELECT * FROM File WHERE title ILIKE '%woot%' " .
        "AND dc:createdBy = 'mdupont' " .
        "ORDER BY dc:created ASC, dc:modified DESC",
      $query->toNxql()
    );
  }

  public function testToNxqlUrlify() : void {
    $query = new Nxql\Query();

    $query->where("title")
          ->eq("bar");

    $this->assertEquals(
      "SELECT+*+FROM+File+WHERE+title+=+'bar'",
      $query->toNxql(true)
    );
  }

  public function testIsEmpty() : void {
    $query = new Nxql\Query();

    $this->assertTrue($query->isEmpty());
  }

  public function testIsEmptyWithWhereClause() : void {
    $query = new Nxql\Query();

    $query->where("foo")
          ->eq("bar");

    $this->assertFalse($query->isEmpty());
  }
}
