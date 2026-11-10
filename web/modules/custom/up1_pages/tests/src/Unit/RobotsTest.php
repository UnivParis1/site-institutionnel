<?php

declare(strict_types=1);

namespace Drupal\Tests\up1_pages\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\up1_pages\Manager\PagePersoRobots;

/**
 * Balise robots des pages personnelles selon leur publication.
 *
 * @group up1_pages
 */
class RobotsTest extends UnitTestCase {

  /**
   * Valeurs du champ metatag, par langue.
   *
   * @var \stdClass[]
   */
  private array $values = [];

  public function testUnpublishedPageIsHidden(): void {
    $page = $this->page(['fr' => FALSE], ['fr' => '{"robots":"index, follow, max-snippet:50","title":"Ma page"}']);
    PagePersoRobots::apply($page, $this->page(['fr' => TRUE]));

    $this->assertSame(['robots' => 'noindex, nofollow, max-snippet:50', 'title' => 'Ma page'], Json::decode($this->values['fr']->value));
  }

  public function testNewUnpublishedPageWithoutTagsIsHidden(): void {
    $page = $this->page(['fr' => FALSE], ['fr' => NULL]);
    PagePersoRobots::apply($page, NULL);

    $this->assertSame(['robots' => 'noindex, nofollow'], Json::decode($this->values['fr']->value));
  }

  public function testRepublishedPageIsVisibleAgain(): void {
    $page = $this->page(['fr' => TRUE], ['fr' => '{"robots":"noindex, nofollow"}']);
    PagePersoRobots::apply($page, $this->page(['fr' => FALSE]));

    $this->assertSame(['robots' => 'index, follow'], Json::decode($this->values['fr']->value));
  }

  public function testCatchUpOnlyTouchesPagesWithoutTheTag(): void {
    // Rattrapage (drush up1-pages:robots) : la page comparée à elle-même.
    $hidden = $this->page(['fr' => FALSE], ['fr' => '{"robots":"noindex, nofollow"}']);
    $this->assertFalse(PagePersoRobots::apply($hidden, $hidden));

    $visible = $this->page(['fr' => FALSE], ['fr' => '{"robots":"index, follow"}']);
    $this->assertTrue(PagePersoRobots::apply($visible, $visible));
    $this->assertSame(['robots' => 'noindex, nofollow'], Json::decode($this->values['fr']->value));
  }

  public function testManualNoindexOnPublishedPageIsKept(): void {
    $page = $this->page(['fr' => TRUE], ['fr' => '{"robots":"noindex"}']);
    PagePersoRobots::apply($page, $this->page(['fr' => TRUE]));

    $this->assertSame('{"robots":"noindex"}', $this->values['fr']->value);
  }

  public function testEachTranslationFollowsItsOwnStatus(): void {
    $page = $this->page(['fr' => TRUE, 'en' => FALSE], ['fr' => '', 'en' => '']);
    PagePersoRobots::apply($page, $this->page(['fr' => TRUE, 'en' => TRUE]));

    $this->assertSame('', $this->values['fr']->value);
    $this->assertSame(['robots' => 'noindex, nofollow'], Json::decode($this->values['en']->value));
  }

  public function testWithoutMetatagFieldNothingHappens(): void {
    $page = $this->page(['fr' => FALSE], ['fr' => '{"robots":"index, follow"}'], FALSE);
    PagePersoRobots::apply($page, NULL);

    $this->assertSame('{"robots":"index, follow"}', $this->values['fr']->value);
  }

  /**
   * Page personnelle : statut par langue, valeur du champ metatag par langue.
   */
  private function page(array $statuses, array $values = [], bool $with_field = TRUE): NodeInterface {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn($with_field ? 'metatag' : 'string');

    $translations = [];
    foreach ($statuses as $langcode => $published) {
      $translation = $this->createMock(NodeInterface::class);
      $translation->method('isPublished')->willReturn($published);
      if (array_key_exists($langcode, $values)) {
        $this->values[$langcode] = (object) ['value' => $values[$langcode]];
        $translation->method('get')->willReturn($this->values[$langcode]);
      }
      $translations[$langcode] = $translation;
    }

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page_personnelle');
    $node->method('getFieldDefinitions')->willReturn(['field_metatags' => $definition]);
    $node->method('getTranslationLanguages')->willReturn(array_fill_keys(array_keys($statuses), NULL));
    $node->method('hasTranslation')->willReturnCallback(static fn($langcode) => isset($translations[$langcode]));
    $node->method('getTranslation')->willReturnCallback(static fn($langcode) => $translations[$langcode]);

    return $node;
  }

}
