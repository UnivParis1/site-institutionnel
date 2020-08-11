<?php

namespace Drupal\up1_data_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;


/**
 * Class Up1DataImportSettings.
 *
 * @ingroup up1_data_import
 */
class Up1DataImportSettings extends ConfigFormBase {

  /**
   *
   * Constructs a \Drupal\up1_data_import\Form\Up1TDataImportSettings object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['up1_data_import.settings'];
  }

  public function getFormId() {
    return 'up1_data_import_settings';
  }

}
