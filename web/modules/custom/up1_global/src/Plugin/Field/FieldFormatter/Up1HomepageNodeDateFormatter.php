<?php

namespace Drupal\up1_global\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'homepage_daterange_up1' formatter.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "homepage_daterange_up1",
 *   label = @Translation("Up1 Date formatter for Node event on Homepage"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class Up1HomepageNodeDateFormatter extends DateTimeCustomFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        $start_date->setTimezone(timezone_open(date_default_timezone_get()));
          $elements[$delta] = [
            'date' => [
              '#markup' => "<div class='date-day-entry'><span>" . $start_date->format('j F Y') . "</span></div>",
            ],
          ];
        }
      }

    return $elements;
  }

}