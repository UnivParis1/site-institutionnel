<?php

namespace Drupal\up1_global\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Utility\Bytes;

/**
 * Plugin implementation of the 'UP1' formatter for 'file size' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "filesize_up1",
 *   label = @Translation("Up1 file size"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */

class Up1FileSizeFormatter extends IntegerFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'thousand_separator' => '',
        'prefix_suffix' => TRUE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number, $langcode = NULL) {
    $abs_size = abs($number);
    if ($abs_size < Bytes::KILOBYTE) {
      return \Drupal::translation()->formatPlural($number, '1 byte', '@count bytes', [], ['langcode' => $langcode]);
    }
    // Create a multiplier to preserve the sign of $number.
    $sign = $abs_size / $number;
    foreach (['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] as $unit) {
      $abs_size /= Bytes::KILOBYTE;
      $round_size = round($abs_size, 0);
      if ($round_size < Bytes::KILOBYTE) {
        break;
      }
    }
    $args = ['@size' => $round_size * $sign];
    $options = ['langcode' => $langcode];
    switch ($unit) {
      case 'KB':
        return new TranslatableMarkup('@size KB', $args, $options);

      case 'MB':
        return new TranslatableMarkup('@size MB', $args, $options);

      case 'GB':
        return new TranslatableMarkup('@size GB', $args, $options);

      case 'TB':
        return new TranslatableMarkup('@size TB', $args, $options);

      case 'PB':
        return new TranslatableMarkup('@size PB', $args, $options);

      case 'EB':
        return new TranslatableMarkup('@size EB', $args, $options);

      case 'ZB':
        return new TranslatableMarkup('@size ZB', $args, $options);

      case 'YB':
        return new TranslatableMarkup('@size YB', $args, $options);
    }

    return format_size($number, 0, '', $this->getSetting('thousand_separator'));
  }
}
