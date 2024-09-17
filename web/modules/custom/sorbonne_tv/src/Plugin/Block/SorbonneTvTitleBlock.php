<?php
/**
 * Provides a Block
 *
 * @Block(
 *   id = "SorbonneTvTitleBlock",
 *   admin_label = @Translation("Sorbonne TV Page Title"),
 *
 * )
 */

namespace Drupal\sorbonne_tv\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\views\Views;
use Drupal\Core\Form\FormState;

class SorbonneTvTitleBlock extends BlockBase {

  /**
    * {@inheritdoc}
  */
  public function build() {
    $build = [];
    $content = [];

    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    $route = \Drupal::routeMatch()->getRouteObject();
    $currentRoute = \Drupal::service('current_route_match')->getRouteName();
    $current_node = \Drupal::routeMatch()->getParameter('node');

    $display_blk = FALSE;
    $stv_page_header = FALSE;
    $page_title = FALSE;
    $page_intro = FALSE;
    $filters_form = FALSE;
    $additionnal_blk_classes = FALSE;

    if($current_node && $current_node->getType() == 'page_sorbonne_tv') {
      $ss_type = (isset($current_node->field_sorb_tv_type->value) ? $current_node->field_sorb_tv_type->value : FALSE);

      if($ss_type == 'page') {
        $display_blk = TRUE;
        $stv_page_header = TRUE;
        $additionnal_blk_classes = [
          'stv_sst_page_header_blk',
        ];

        $content['page_title_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'page-title-wrapprer',
            ],
          ],
          'page_title' => [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#value' => '<span class="title-bullet"></span><span class="title-txt">'. $current_node->getTitle() .'</span>',
            '#attributes' => [
              'id' => 'page-title',
              'class' => [
                'page-title',
                'bullet-title',
                'bullet-left',
              ],
            ],
          ],
        ];

        if(isset($current_node->body->value)) {
          $content['page_title_wrapper']['page-intro'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'page-intro',
              ]
            ],
            'body' => $current_node->body->view('full'),
          ];
        }

        if(isset($current_node->field_media->entity)) {
          $content['page_title_wrapper']['#attributes']['class'][] = 'col-lg-6';

          $page_header_img = $current_node->field_media->view('sorbonne_tv_notice_page');
          
          $content['page_headerimg_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'page-headerimg-wrapprer',
                'col-lg-6',
              ],
            ],
            $page_header_img,
          ];
        }
      }

      if($ss_type == 'mosaic') {
        $display_blk = TRUE;
        $additionnal_blk_classes = [
          'stv_sst_page_header_blk',
        ];

        $block_manager = \Drupal::service('plugin.manager.block');
        $config = [];
        $plugin_block = $block_manager->createInstance('views_exposed_filter_block:sorbonne_tv_search-stv_mosaique_block', $config);
        $access_result = $plugin_block->access(\Drupal::currentUser());

        $filters_form = $plugin_block->build();
      }
    }
    elseif($currentRoute) {

      switch($currentRoute) {
        case 'sorbonne_tv.grille_programmes':
          $display_blk = TRUE;

          $config = \Drupal::config('sorbonne_tv.settings');
          $programsConf = $config->get('sorbonne_tv.settings.programs');
          $page_intro = (isset($programsConf['intro']) ? $programsConf['intro'] : '');

          $filters_form = \Drupal::formBuilder()->getForm('\Drupal\sorbonne_tv\Form\SorbonneTvProgramsFiltersForm');
        break;

        case 'view.sorbonne_tv_search.stv_search_page':
          $display_blk = TRUE;

          $block_manager = \Drupal::service('plugin.manager.block');
          $config = [];
          $plugin_block = $block_manager->createInstance('views_exposed_filter_block:sorbonne_tv_search-stv_search_page', $config);
          $access_result = $plugin_block->access(\Drupal::currentUser());

          $filters_form = $plugin_block->build();

        break;

        case 'sorbonne_tv.favorite_list':
          $display_blk = TRUE;

          $config = \Drupal::config('sorbonne_tv.settings');
          $favoritesConf = $config->get('sorbonne_tv.settings.favorites');
          $page_intro = (isset($favoritesConf['intro']) ? $favoritesConf['intro'] : '');
        break;

        case 'entity.webform.canonical':
          $display_blk = TRUE;

          $config = \Drupal::config('sorbonne_tv.settings');
          $contactConf = $config->get('sorbonne_tv.settings.contact');
          $page_intro = (isset($contactConf['intro']) ? $contactConf['intro'] : '');
        break;
        
        default:
        break;
      }

    }

    $build = [
      '#cache' => [
        'contexts' => [
          'route',
          'url.query_args',
        ]
      ]
    ];

    if($display_blk) {
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $page_title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());

      if($currentRoute == 'entity.webform.canonical') {
        $route = \Drupal::routeMatch();
        $entity = null;

        if ($route->getRouteObject()) {
          foreach ($route->getParameters() as $name => $parameter) {
              if ($parameter instanceof \Drupal\Core\Entity\EntityInterface) {
                  $entity = $parameter;
                  break;
              }
          }
        }

        if($entity) {
          if($entity_id = $entity->id()) {
            if($entity_id == 'sorbonne_tv_contact') {
              $page_title = t('Contact');
            }
          }
        }
      }
      
      if(!$stv_page_header) { // Pour les pages de base on placera le titre autrement (dans un conteneur supplémentaire)
        $content['page_title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h1',
          '#value' => '<span class="title-bullet"></span><span class="title-txt">'. $page_title .'</span>',
          '#attributes' => [
            'id' => 'page-title',
            'class' => [
              'page-title',
              'bullet-title',
              'bullet-left',
            ],
          ],
        ];
      }

      if($page_intro) {
        $content['page_intro'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $page_intro,
          '#attributes' => [
            'class' => [
              'page-intro',
            ],
          ],
        ];
      }

      if($filters_form) {
        $content['page_filters'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'page-filters',
            ],
          ],
          $filters_form
        ];
      }

    }

    if($display_blk && !empty($content)) {
      $build['#attributes']['class'] = [
        'sorbonne_tv_title_blk',
      ];

      if($additionnal_blk_classes) {
        $build['#attributes']['class'] = array_merge($build['#attributes']['class'], $additionnal_blk_classes);
      }
      
      $build['content'] = [
        '#type' => 'container',
        '#attributes' => [
          'class'=> [
            'blk-content',
            'container',
          ]
        ],
        $content,
      ];

      // Additionnal classes
      if($stv_page_header) {
        $build['content']['#attributes']['class'][] = 'd-lg-flex';
        $build['content']['#attributes']['class'][] = 'align-items-lg-center';
      }
    }

    return $build;
  }
}
