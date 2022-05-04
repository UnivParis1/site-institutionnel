<?php

use Drupal\cmis_extensions\TwigExtension\BytesConversionTwigExtension;
use Twig\Environment;
use PHPUnit\Framework\TestCase;

class BytesConversionTwigExtensionTest extends TestCase {
  public function testBytes() : void {
    $this->assertEquals("500 B", $this->apply(500));
  }

  public function testKilobytes() : void {
    $this->assertEquals("4 KB", $this->apply(4096));
  }

  public function testMegabytes() : void {
    $this->assertEquals("2 MB", $this->apply(2048000));
  }

  public function testGigabytes() : void {
    $this->assertEquals("1 GB", $this->apply(1073741875));
  }

  public function testTerabytes() : void {
    $this->assertEquals("1 TB", $this->apply(1099512000000));
  }

  public function testKilobytesPrecision() : void {
    $value = 4096 + 512 + 256;

    $this->assertEquals("4.8 KB", $this->apply($value));
    $this->assertEquals("4.75 KB", $this->apply($value, 2));
  }

  public function test() : void {
    $value = "1 200 000";

    $this->assertEquals("1.1 MB", $this->apply($value));
  }

  private function apply($input, $precision = 1) : string {
    $env = $this->getMockBuilder(Environment::class)
      ->disableOriginalConstructor()
      ->setMethods(['getFilter'])
      ->getMock();

    $filter = $this->getMockBuilder(stdClass::class)
      ->setMethods(['getCallable'])
      ->getMock();

    $filter->method('getCallable')->willReturn(function($str, $params) {
      return str_replace(["@value"], $params, $str);
    });

    $env->method('getFilter')->willReturn($filter);

    return BytesConversionTwigExtension::humanBytes($env, $input, $precision);
  }
}
