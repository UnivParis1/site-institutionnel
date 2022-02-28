<?php

namespace Drupal\up1_global\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'UP1' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "startend_date_up1",
 *   label = @Translation("Up1 start & end date range"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class Up1StartEndDateRangeFormatter extends DateTimeCustomFormatter {

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
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        $start_date->setTimezone(timezone_open(date_default_timezone_get()));

        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;
        $end_date->setTimezone(timezone_open(date_default_timezone_get()));

        $start_hour = $start_date->format('H:i');
        $end_hour = $end_date->format('H:i');

        if ($start_date->format('d-m-Y') !== $end_date->format('d-m-Y')) {
          $elements[$delta] = [
            'date' => [
              '#markup' => "<div class='event-date'>
              <div class='start-date'>
                <span class=''>" . t("From: ") . "</span> " .
                $start_date->format('l j F Y') . "<i class='fa fa-arrow-right'>
                <span class='sr-only'>" . t( ' at ') . "</span></i> $start_hour
              </div>
              <div class='end-date'>
              <span class=''>" . t("To: ") . "</span> " .
                $end_date->format('l j F Y ') . "<i class='fa fa-arrow-right'>
              <span class='sr-only'>" . t( ' at ') . "</span> </i> $end_hour </div>",
            ],
          ];
        }
        elseif ($start_date->getTimestamp() === $end_date->getTimestamp()) {
          $elements[$delta] = [
            'date' => [
              '#markup' => "<div>" . $start_date->format('d/m/Y') . "</div>",
            ],
          ];
        }
        else {
          $elements[$delta] = [
            'date' => [
              '#markup' => "<div>" . $start_date->format('l j F Y') . "</div>
              <div><span>$start_hour</span>
              <i class='fa fa-arrow-right'><span class='sr-only'>" .  $this->t(' at ') . "</span></i>
              <span>$end_hour</span></div>",
            ],
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('The string to begin date range'),
      '#default_value' => $this->t($this->getSetting('prefix')),
    ];
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->t($this->getSetting('separator')),
    ];
    $form['icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Hours separator'),
      '#description' => $this->t('The string to separate the start and end hours'),
      '#default_value' => $this->getSetting('icon'),
      '#options' => [
        'arrow-right' => $this->t('Right arrow'),
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($prefix = $this->getSetting('prefix')) {
      $summary[] = $this->t('Prefix: %prefix', ['%prefix' => $prefix]);
    }
    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }
    if ($icon = $this->getSetting('icon')) {
      $summary[] = $this->t('Icon: %icon', ['%icon' => $icon]);
    }

    return $summary;
  }

}
