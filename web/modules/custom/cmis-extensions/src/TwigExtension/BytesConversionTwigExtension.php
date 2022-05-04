<?php

namespace Drupal\cmis_extensions\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\Environment;

class BytesConversionTwigExtension extends AbstractExtension {
  public function getFilters() {
    return [
      'human_bytes' => new TwigFilter('human_bytes', [
        'Drupal\\cmis_extensions\\TwigExtension\\BytesConversionTwigExtension',
        'humanBytes'
      ], ['needs_environment' => true])
    ];
  }

  public function getName() {
    return 'cmis_extensions.twig_extension';
  }

  public static function humanBytes(Environment $env, $bytes, $precision = 1) {
    $bytes = intval(str_replace(" ", "", $bytes));
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    $translationKey = "@value " . $units[$pow];

    $value = round($bytes, $precision);

    if (class_exists("Drupal")) {
      $currentLocale = \Drupal::languageManager()
        ->getCurrentLanguage()
        ->getId();

      if ($currentLocale == "fr")
        $value = str_replace(".", ",", $value);
    }

    return $env
      ->getFilter("t")
      ->getCallable()($translationKey, ['@value' => $value]);
  }
}
