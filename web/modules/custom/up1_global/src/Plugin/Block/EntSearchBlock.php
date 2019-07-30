<?php

namespace Drupal\up1_global\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Ent & Search" block.
 * @Block(
 *   id="up1_ent_search_block",
 *   admin_label = @Translation("ENT Search block"),
 *   category = @Translation("Panthéon-Sorbonne"),
 * )
 *
 * @package Drupal\up1_global\Plugin\Block
 */
class EntSearchBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function build() {

    $host = \Drupal::request()->getSchemeAndHttpHost();

    $theme = \Drupal::theme()->getActiveTheme();

    $config = $this->getConfiguration();

    return [
      '#theme' => 'up1_ent_search_block',
      '#url' => $config['ent_url'],
      '#theme_url' => "//" . \Drupal::request()->getHost() . "/" . $theme->getPath(),
      ];
  }

  /**
   * {@inheritDoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['ent_url'] = [
      '#type' => 'link',
      '#title' => $this->t('URL of ENT platform'),
      '#description' => $this->t('Type the URL of the ENT platform. Must start with https://'),
      '#default_value' => isset($config['ent_url']) ? $config['ent_url'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['ent_url'] = $values['ent_url'];
    $this->configuration['ent_label'] = $values['ent_label'];
  }
}
