<?php
/**
 * Provides a Block
 *
 * @Block(
 *   id = "SorbonneTvTimeLapseFiltersBlock",
 *   admin_label = @Translation("Sorbonne TV Page Title"),
 *
 * )
 */

namespace Drupal\sorbonne_tv\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

class SorbonneTvTimeLapseFiltersBlock extends BlockBase {

  /**
    * {@inheritdoc}
  */
  public function build() {
    $build = [];
    $content = [];
    $config = $this->getConfiguration();
    $color_gradients = [];

    if(isset($config['color'])) {
      $color_gradients[] = $config['color'];
      $color_gradients[] = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($config['color'], 0.8);
      $color_gradients[] = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($config['color'], 0.6);
      $color_gradients[] = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($config['color'], 0.4);
      $color_gradients[] = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($config['color'], 0.32);
      $color_gradients[] = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($config['color'], 0.2);
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page_sorbonne_tv');
    if (isset($field_definitions['field_sorbonne_tv_time_lapse'])) {
      $allowed_options = options_allowed_values($field_definitions['field_sorbonne_tv_time_lapse']->getFieldStorageDefinition());

      $duration_items = [];
      $i = 0;
      foreach($allowed_options as $k => $val) {
        if(isset($color_gradients[$i])) {
          $bgcolor_style = 'background-color: '. $color_gradients[$i] .';';
        }
        elseif(isset($color_gradients[0])) { // Par defaut couleur
          $bgcolor_style = 'background-color: '. $color_gradients[0] .';';
        }
        else {
          $bgcolor_style = FALSE;
        }

        $searchQuery['timelapse'] = $k;
        $timelapse_filter_mk = Markup::create('<span class="timelapse_icon bi bi-clock"></span><span class="timelapse_txt">'. $val .'</span>');
        $timelapse_filter_link_str = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->set_views_filter_link('view.sorbonne_tv_search.stv_search_page', $searchQuery, 'link_string', $timelapse_filter_mk);

        $duration_items[$k] = Markup::create('<div class="timelapse"'. ($bgcolor_style ? ' style="'. $bgcolor_style .'"' : '') .'>'. $timelapse_filter_link_str .'</div>');

        $i++;
      }

      $content = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#attributes' => ['class' => ['timelapse_items']],
        '#items' => $duration_items,
      ];
    }

    if(!empty($content)) {
      $build = [
        '#type' => 'container',
        '#attributes' => [
          'class'=> [
            'time_lapse_filters_blk',
          ]
        ],
        '#cache' => [
          'contexts' => [
            'route',
          ]
        ],
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'class'=> [
              'blk-content',
            ]
          ],
          $content,
        ]
      ];
    }

    return $build;
  }
}
