<?php

declare(strict_types=1);

use Drupal\cmis_extensions\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase {
  public function testConstructorAndGetters() : void {
    $date = strtotime("now");

    $result = new Result(
      "abc-def-123",
      "Hello world",
      256,
      $date,
      "Foo / Bar"
    );

    $this->assertEquals("abc-def-123", $result->getId());
    $this->assertEquals("Hello world", $result->getTitle());
    $this->assertEquals(256, $result->getSize());
    $this->assertEquals($date, $result->getLastModified());
    $this->assertEquals("Foo / Bar", $result->getParent());
  }

  public function testDownloadUrl() : void {
    $id = "abc-def-123";
    $result = new Result($id, "", 0, strtotime("now"), "");

    $this->assertEquals(
      "https://ged.uphf.fr/nuxeo/json/cmis/default/root?objectId=" . $id,
      $result->getDownloadUrl()
    );
  }
}
