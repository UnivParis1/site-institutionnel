<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Component\Serialization\Json;
use Drupal\node\NodeInterface;

/**
 * Balise « robots » des pages personnelles selon leur publication.
 *
 * Page dépubliée : noindex, nofollow. Page republiée : index, follow. Les
 * autres valeurs de la balise (max-snippet…) et les autres balises sont
 * gardées. Sans champ metatag sur le type de contenu, rien n'est fait.
 */
final class PagePersoRobots {

  public const HIDDEN = ['noindex', 'nofollow'];

  public const VISIBLE = ['index', 'follow'];

  /**
   * Met à jour la balise de chaque traduction avant enregistrement.
   *
   * @return bool
   *   TRUE si une valeur a changé.
   */
  public static function apply(NodeInterface $node, ?NodeInterface $original): bool {
    if ($node->bundle() !== PagePersoManager::BUNDLE || !($field = self::field($node))) {
      return FALSE;
    }

    $changed = FALSE;

    foreach (array_keys($node->getTranslationLanguages()) as $langcode) {
      $translation = $node->getTranslation($langcode);
      $was_published = $original && $original->hasTranslation($langcode)
        ? $original->getTranslation($langcode)->isPublished()
        : NULL;

      if (!$translation->isPublished()) {
        $changed = self::set($translation, $field, self::HIDDEN) || $changed;
      }
      // Seulement à la republication : un noindex choisi à la main sur une
      // page publiée est respecté.
      elseif ($was_published === FALSE) {
        $changed = self::set($translation, $field, self::VISIBLE) || $changed;
      }
    }

    return $changed;
  }

  /**
   * Premier champ metatag du type de contenu.
   */
  public static function field(NodeInterface $node): ?string {
    foreach ($node->getFieldDefinitions() as $name => $definition) {
      if ($definition->getType() === 'metatag') {
        return $name;
      }
    }

    return NULL;
  }

  private static function set(NodeInterface $translation, string $field, array $directives): bool {
    $tags = self::decode((string) $translation->get($field)->value);
    $kept = array_filter(
      array_map('trim', explode(',', (string) ($tags['robots'] ?? ''))),
      static fn(string $value) => $value !== '' && !in_array($value, [...self::HIDDEN, ...self::VISIBLE], TRUE)
    );
    $robots = implode(', ', [...$directives, ...$kept]);
    if (($tags['robots'] ?? NULL) === $robots) {
      return FALSE;
    }

    $tags['robots'] = $robots;
    $translation->get($field)->value = Json::encode($tags);

    return TRUE;
  }

  /**
   * Valeur du champ : JSON (Metatag 2) ou tableau sérialisé (Metatag 1).
   */
  private static function decode(string $value): array {
    $tags = match (TRUE) {
      str_starts_with($value, '{') => Json::decode($value),
      str_starts_with($value, 'a:') => @unserialize($value, ['allowed_classes' => FALSE]),
      default => [],
    };

    return is_array($tags) ? $tags : [];
  }

}
