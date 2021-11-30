<?php

namespace Drupal\up1_global\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'UP1' formatter for 'datetime' fields.
 *
 * This formatter renders the date range as plain text like "l j F Y at H:i".
 *
 * @FieldFormatter(
 *   id = "up1_datetime_format",
 *   label = @Translation("Up1 datetime format"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class Up1DatetimeFormatter extends DateTimeCustomFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date)) {
        $start_date = $item->start_date;
        $start_date->setTimezone(timezone_open(date_default_timezone_set("Europe/Paris")));

        $elements[$delta] = [
          'date' => [
            '#markup' => "<div class='event-date'>
              <div class='start-date'>" . t("On: @date at @hour", [
                "@date" => $start_date->format('l j F Y '),
                "@hour" => $start_date->format('H') . "h" . $start_date->format('i'),
              ]) ."</div>",
          ],
        ];
      }
    }

    return $elements;
  }
}
