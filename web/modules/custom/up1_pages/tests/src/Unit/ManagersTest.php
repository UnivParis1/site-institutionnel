<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Manager\ComptexManager;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Manager\HalManager;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Fiche wsgroups et publications HAL, y compris en cas de panne.
 *
 * @group up1_pages
 */
class ManagersTest extends UnitTestCase {

  use WsTestTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->setUpWs();
  }

  private function comptex(): ComptexManager {
    return new ComptexManager($this->wsClient(), $this->fallbackCache(), $this->configFactory(), $this->createMock(EntityTypeManagerInterface::class));
  }

  private function hal(): HalManager {
    return new HalManager($this->wsClient(), $this->configFactory(), new PhpTransliteration());
  }

  public function testFormatNormalizesFields(): void {
    $result = $this->comptex()->format([
      'uid' => 'u123',
      'telephoneNumber' => ['+33 1 23 45 67 89'],
      'supannCivilite' => ['Mme'],
      'displayName' => ['Ada Lovelace'],
      'sn' => ['Lovelace'],
      'givenName' => ['Ada'],
      'employeeType' => ['Professeur'],
      'eduPersonPrimaryAffiliation' => ['faculty'],
      'supannEntiteAffectation-all' => [
        ['businessCategory' => 'pedagogy', 'description' => 'Département Math', 'labeledURI' => 'https://math.example/'],
        ['businessCategory' => 'research', 'description' => 'Labo <X>', 'labeledURI' => 'https://labo-x.example/'],
      ],
      'supannRoleEntite-all' => [
        ['role' => 'MCF', 'structure' => ['name' => 'UFR Math', 'description' => 'Département Math']],
      ],
      'supannConsentement' => ['{PROJ:OBSIA}CGU'],
    ]);

    $this->assertSame('https://photos.example.org/u123', $result['userPhoto']);
    $this->assertSame('+33 1 23 45 67 89', $result['telephoneNumber']);
    $this->assertSame('Mme', $result['supannCivilite']);
    $this->assertSame('Professeur', $result['employeeType']);
    $this->assertSame('UFR Math', $result['supannRole'][0]['name']);
    // Labo avant UFR, description échappée.
    $this->assertStringStartsWith("<p><a href='https://labo-x.example/' title='Labo &lt;X&gt;'", $result['entites']);
    // Consentement en première position : l'ancien array_search() le ratait.
    $this->assertTrue($result['obsia']);
  }

  public function testFormatStudent(): void {
    $result = $this->comptex()->format([
      'uid' => 'u321',
      'supannCivilite' => ['Mme'],
      'employeeType' => ['ignored'],
      'eduPersonPrimaryAffiliation' => ['student'],
      'supannEntiteAffectation-all' => [
        ['businessCategory' => 'pedagogy', 'description' => 'UFR', 'labeledURI' => 'https://ufr.example/'],
      ],
    ]);

    $this->assertSame('Doctorante', $result['employeeType']);
    $this->assertSame('', $result['entites']);
    $this->assertFalse($result['obsia']);
  }

  public function testUserInformationFallsBackThenEmptiesWithoutBlocking(): void {
    $comptex = $this->comptex();
    $this->httpMock->append(new Response(200, [], '[{"uid":"ada","displayName":"Ada Lovelace","mail":"ada@univ-paris1.fr"}]'));
    $this->assertSame('Ada Lovelace', $comptex->getUserInformation('ada', $stale)['displayName']);
    $this->assertFalse($stale);

    // wsgroups tombe : dernière fiche connue pour ada, fiche vide pour bob.
    $this->clock->now += 3601;
    $down = new ConnectException('down', new Request('GET', 'https://ws.example.org/'));
    $this->httpMock->append($down, $down);
    $this->assertSame('Ada Lovelace', $comptex->getUserInformation('ada', $stale)['displayName']);
    $this->assertTrue($stale);
    $this->assertSame([], $comptex->getUserInformation('bob', $stale));
    $this->assertTrue($stale);

    // Disjoncteur ouvert : plus aucun appel réseau, aucune exception.
    $calls = count($this->httpHistory);
    $this->assertNull($comptex->getUserEmail('carol'));
    $this->assertFalse($comptex->userHasPagePerso('carol'));
    $this->assertFalse($comptex->isAvailable());
    $this->assertCount($calls, $this->httpHistory);
  }

  public function testLoginIsEncodedInUrl(): void {
    $this->httpMock->append(new Response(200, [], '[]'));
    $this->comptex()->getUserEmail('x&filter_student=only');

    $this->assertStringContainsString('&id=x%26filter_student%3Donly&', (string) $this->httpHistory[0]['request']->getUri());
  }

  public function testHalPublicationsAreFiltered(): void {
    $this->httpMock->append(new Response(200, [], '<div><div id="res_script">PUB <script>alert(1)</script>É</div></div>'));

    $html = $this->hal()->fetchPublications('nomprenom', 'René', 'Descartes');
    $this->assertStringContainsString('PUB', $html);
    $this->assertStringContainsString('É', $html);
    $this->assertStringNotContainsString('<script', $html);
    $query = $this->httpHistory[0]['request']->getUri()->getQuery();
    $this->assertStringContainsString('auteur_exp=Rene+Descartes', $query);
    $this->assertStringContainsString('collection_exp=UNIV-PARIS1', $query);
  }

  public function testHalWithoutPublicationsGivesEmptyString(): void {
    $this->httpMock->append(new Response(200, [], '<div id="res_script">&nbsp;</div>'));
    $this->assertSame('', $this->hal()->fetchPublications('idhal', '', '', '1234'));
  }

  /**
   * @dataProvider unusableHalResponses
   */
  public function testUnusableHalResponseIsAnOutage(mixed $response): void {
    $this->httpMock->append($response);
    $this->expectException(WsUnavailableException::class);
    $this->hal()->fetchPublications('idhal', '', '', '1234');
  }

  public static function unusableHalResponses(): array {
    return [
      'injoignable' => [new ConnectException('down', new Request('GET', 'https://hal.example.org/'))],
      'erreur 500' => [new Response(500, [], 'Internal error')],
      'page de maintenance' => [new Response(200, [], '<html><body>Maintenance</body></html>')],
      'réponse vide' => [new Response(200, [], '')],
    ];
  }

  public function testIncompleteSearchIsRejectedWithoutCallingHal(): void {
    $this->expectException(\InvalidArgumentException::class);
    try {
      $this->hal()->fetchPublications('nomprenom', '', 'Lovelace');
    }
    finally {
      $this->assertCount(0, $this->httpHistory);
    }
  }

}
