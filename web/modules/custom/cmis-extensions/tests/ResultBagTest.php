<?php

declare(strict_types=1);

use Drupal\cmis_extensions\ResultBag;
use Drupal\cmis_extensions\Result;
use PHPUnit\Framework\TestCase;

final class ResultBagTest extends TestCase {
  public function testConstructorAndGetters() : void {
    $result = new Result("foo", "bar", 256, strtotime("now"), "Foo");
    $bag = new ResultBag([$result], 2, 2, ["page" => 1]);

    $this->assertEquals([$result], $bag->getResults());
    $this->assertEquals(2, $bag->getTotal());
    $this->assertEquals(2, $bag->getNbOfPages());
    $this->assertEquals(1, $bag->getPage());

    $this->assertTrue($bag->getHasMoreItems());
  }

  public function testGetPagesRangeLessThan10() : void {
    $bag = new ResultBag([], 0, 8, ["page" => 1]);

    $this->assertEquals(range(1, 8), $bag->getPagesRange());
  }

  public function testGetPagesRangeBeginning() : void {
    $bag = new ResultBag([], 0, 15, ["page" => 1]);

    $this->assertEquals(
      [1, 2, 3, '...', 13, 14, 15],
      $bag->getPagesRange()
    );
  }

  public function testGetPagesRangeEnding() : void {
    $bag = new ResultBag([], 0, 15, ["page" => 14]);

    $this->assertEquals(
      [1, 2, 3, '...', 13, 14, 15],
      $bag->getPagesRange()
    );
  }

  public function testGetPagesRangeAlmostBeginning() : void {
    $bag = new ResultBag([], 0, 15, ["page" => 3]);

    $this->assertEquals(
      [1, 2, 3, 4, 5, '...', 15],
      $bag->getPagesRange()
    );
  }

  public function testGetPagesRangeAlmostEnding() : void {
    $bag = new ResultBag([], 0, 15, ["page" => 13]);

    $this->assertEquals(
      [1, 2, 3, '...', 12, 13, 14, 15],
      $bag->getPagesRange()
    );
  }

  public function testGetPagesRangeMiddle() : void {
    $bag = new ResultBag([], 0, 15, ["page" => 8]);

    $this->assertEquals(
      [1, '...', 6, 7, 8, 9, 10, '...', 15],
      $bag->getPagesRange()
    );
  }
}
