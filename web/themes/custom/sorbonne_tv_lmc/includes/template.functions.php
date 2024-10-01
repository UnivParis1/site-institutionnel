<?php

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Views;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_HOOK() for HTML document templates.
 */
function sorbonne_tv_lmc_preprocess_html(&$variables) {
  $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $variables['attributes']['class'][] = 'site-lang-'. $language;
  $currentRoute = \Drupal::service('current_route_match')->getRouteName();
  $current_node = \Drupal::routeMatch()->getParameter('node');

  // Ajout de classes pour le body
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
  $is_front_mini_site = FALSE;

  if(!$is_front) {
    if (preg_match('/site/', $variables['root_path']) && !isset($variables['node_type']) && !preg_match('/sitemap/', $variables['root_path'])) {
      $is_front_mini_site = TRUE;
    }
  }
  $variables['is_front_mini_site'] = $is_front_mini_site;

  $variables['attributes']['class'][] = ($is_front || $is_front_mini_site ? 'is-home-page' : 'not-home-page');

  /*if($currentRoute == 'sorbonne_tv.grille_programmes') {
    $variables['attributes']['class'][] = 'programs_page';
  }
  elseif($currentRoute == 'view.sorbonne_tv_search.stv_search_page') {
    $variables['attributes']['class'][] = 'search_videos_page';
  }*/

  switch ($currentRoute) {
    case 'sorbonne_tv.grille_programmes':
      $variables['attributes']['class'][] = 'programs_page';
    break;

    case 'view.sorbonne_tv_search.stv_search_page':
      $variables['attributes']['class'][] = 'search_videos_page';
    break;

    case 'entity.webform.canonical':
      $variables['attributes']['class'][] = 'sorbonnetv-page-form';
    break;

    case 'user.login':
      $variables['attributes']['class'][] = 'user-login-page';
    break;

    case 'user.register':
      $variables['attributes']['class'][] = 'user-register-page';
    break;

    case 'user.password':
    case 'user.pass':
      $variables['attributes']['class'][] = 'user-password-page';
    break;

    case 'system.403':
      $variables['attributes']['class'][] = 'access-denied-page';
    break;

    default:
    break;
  }

  if($current_node && $current_node->getType() == 'page_sorbonne_tv') {
    $ss_type = (isset($current_node->field_sorb_tv_type->value) ? $current_node->field_sorb_tv_type->value : FALSE);

    if($ss_type) {
      $variables['attributes']['class'][] = 'stv-sstype-'. $ss_type;

      if($ss_type == 'mosaic') {
        // Si présence bloc intro
        if(isset($current_node->body->value) || isset($current_node->field_media->entity)) {
          $variables['attributes']['class'][] = 'mosaic_w_intro';
        }
      }
    }
  }

}

/**
 * Implements hook_preprocess_HOOK() for region.html.twig.
 */
function sorbonne_tv_lmc_preprocess_region(&$variables) {
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
	$currentRoute = \Drupal::service('current_route_match')->getRouteName();
  $theme_path = \Drupal::theme()->getActiveTheme()->getPath();
  $region = $variables['region'];

  $variables['attributes']['class'][] = 'region_'. $region;
  $variables['attributes']['id'] = 'region_'. $region;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function sorbonne_tv_lmc_theme_suggestions_node_alter(array &$suggestions, array $variables) {
  $elements = $variables['elements'];
  $sanitized_view_mode = strtr($elements['#view_mode'], '.', '_');
  $original_theme_hook = (isset($variables['theme_hook_original']) ? $variables['theme_hook_original'] : 'node');


  if(isset($elements['#node'])) {
    $node = $elements['#node'];
    $node_type = $node->getType();

    if($node_type == 'page_sorbonne_tv') {
      // Suggestions by sorbonne tv sstype
      $ss_type = (isset($node->field_sorb_tv_type->value) ? $node->field_sorb_tv_type->value : FALSE);
      $suggestions[] = $original_theme_hook . '__' . $node_type . '__' . $ss_type;
      $suggestions[] = $original_theme_hook . '__' . $node_type . '__' . $ss_type . '__' . $sanitized_view_mode;
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for block templates.
 */
function sorbonne_tv_lmc_preprocess_block(&$variables) {
  $route_name = \Drupal::routeMatch()->getRouteName();
  $plugin_id  = $variables['plugin_id'];
  $block_id = $variables['attributes']['id'];
  $elmt_id = $variables['elements']['#id'];

  if($block_id == 'block-stv-search-page-sort-filters') {
    $variables['attributes']['class'][] = 'container';
    $variables['attributes']['class'][] = 'search-sort-filters-blk';
  }

  if($block_id == 'block-sorbonne-tv-lmc-menusorbonnetv') {
    $search_form_blk = \Drupal::formBuilder()->getForm('\Drupal\sorbonne_tv\Form\SorbonneTvSearchForm');

    if($search_form_blk) {
      $variables['search_form_blk'] = $search_form_blk;
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function sorbonne_tv_lmc_preprocess_node(&$variables) {
  $currentRoute = \Drupal::service('current_route_match')->getRouteName();
  $view_mode = $variables['view_mode'];
  $node = $variables['node'];
  $node_type = $node->getType();

  if($node_type == 'page_sorbonne_tv') {
    $variables['attributes']['class'][] = $node_type;
    $variables['attributes']['class'][] = $view_mode;

    // get sorbonne tv sstype
    $ss_type = (isset($node->field_sorb_tv_type->value) ? $node->field_sorb_tv_type->value : FALSE);
    $variables['ss_type'] = $ss_type;
    $variables['attributes']['class'][] = $node_type .'_'. $ss_type;

    switch($ss_type) {
      case 'collection':
        if(isset($node->field_media->target_id)) {
          $notice_collection_img = $node->field_media->view('sorbonne_tv_notice_collection');
          $variables['notice_collection_img'] = $notice_collection_img;
        }

        $body_only = FALSE;
        $img_only = FALSE;
        if(!isset($node->field_media->entity) && isset($node->body->value)) {
          $body_only = TRUE;
        }
        if(isset($node->field_media->entity) && !isset($node->body->value)) {
          $img_only = TRUE;
        }
        $variables['body_only'] = $body_only;
        $variables['img_only'] = $img_only;

        // Bloc des videos de la collection
        $variables['collection_videos_blk'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['collection_videos_blk']
          ],
          /*
          'block_title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => '<span class="title-txt">'. t('Recommandé pour vous') .'</span><span class="title-bullet"></span>',
            '#attributes' => [
              'class' => [
                'bullet-title'
              ],
            ],
          ],
          */
          'block_content' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['blk-content']
            ],
            '#type' => 'view',
            '#name' => 'sorbonne_tv_collection',
            '#display_id' => 'stv_collection_videos',
            '#arguments' => [
              $node->id(),
            ],
          ],
        ];

        // Get Disciplines
        $disciplinesFilter = \Drupal::service('sorbonne_tv.videos_service')->getVideoDisciplinesContextualFiltersFormat($node);

        // Bloc Recommande pour vous
        $variables['collection_recommanded_videos_blk'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['collection_recommanded_videos_blk']
          ],
          'block_title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => '<span class="title-txt">'. t('Recommandé pour vous') .'</span><span class="title-bullet"></span>',
            '#attributes' => [
              'class' => [
                'bullet-title'
              ],
            ],
          ],
          'block_content' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['blk-content']
            ],
            '#type' => 'view',
            '#name' => 'sorbonne_tv_collection',
            '#display_id' => 'recommanded_noticecollection_blk',
            '#arguments' => [
              $node->id(),
            ],
            '#attached' => [
              'library' => [
                'sorbonne_tv_lmc/slick'
              ]
            ]
          ],
        ];

        if($disciplinesFilter && !empty($disciplinesFilter)) {
          $variables['collection_recommanded_videos_blk']['block_content']['#arguments'][] = $disciplinesFilter;
        }

        if($view_mode == 'sorbonne_tv_numbered_list') {
          $loaded_prg = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
            'field_stv_prg_video' => $node->id(),
          ]);
          $parent_prg = reset($loaded_prg);

          if($parent_prg) {
            $stc_prg_lighten = 0.3;

            if(isset($parent_prg->field_sorbonne_tv_prg_color->value)) {
              $empty_thumb_attr = new Attribute();
              $prg_color = $parent_prg->field_sorbonne_tv_prg_color->value;
              $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);

              $empty_thumb_attr->setAttribute('style', 'background-color: '. $prg_lighten_color .';');

              $variables['empty_thumb_attr'] = $empty_thumb_attr;
            }
          }
          elseif($currentRoute == 'sorbonne_tv.grille_programmes') {
            // Si on est sur la sidebar de la grille de programme
            $stc_prg_lighten = 0.3;
            $empty_thumb_attr = new Attribute();
            $prg_color = '#ffd661';
            $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);

            $empty_thumb_attr->setAttribute('style', 'background-color: '. $prg_lighten_color .';');

            $variables['empty_thumb_attr'] = $empty_thumb_attr;
          }
        }
      break;

      case 'video':
        // Video Thumb
        $video_thumb = FALSE;
        if ($node_couv_media = $node->field_media->entity) {
          if ($node_couv_file = $node_couv_media->field_media_image->entity) {
            if ($node_couv_file_uri = $node_couv_file->getFileUri()) {
              $video_thumb = ImageStyle::load('sorbonne_tv_video_thumb')->buildUrl($node_couv_file_uri);
            }
          }
        }
        $variables['video_thumb'] = $video_thumb;

        // Pour le format "Recommande pour vous" et "Liste num"
        $video_thumb_recom_img = FALSE;
        $variables['video_thumb_recom_img'] = $video_thumb_recom_img;

        if($view_mode == 'sorbonne_tv_video_notice_recommanded') {
          if(isset($node->field_media->target_id)) {
            $video_thumb_recom_img = $node->field_media->view('sorbonne_tv_video_notice_recommanded');

            $variables['video_thumb_recom_img'] = $video_thumb_recom_img;
          }
        }
        elseif($view_mode == 'sorbonne_tv_numbered_list') {
          if(isset($node->field_media->target_id)) {
            $video_thumb_recom_img = $node->field_media->view('sorbonne_tv_numbered_list');

            $variables['video_thumb_recom_img'] = $video_thumb_recom_img;
          }
        }
        elseif($view_mode == 'sorbonne_tv_playlist') {
          if(isset($node->field_media->target_id)) {
            $video_thumb_recom_img = $node->field_media->view('sorbonne_tv_playlist');

            $variables['video_thumb_recom_img'] = $video_thumb_recom_img;
          }
        }
        elseif ($view_mode == 'sorbonne_tv_mosaique_collection') {
          $collections = $node->field_collections->referencedEntities();
          $collection = reset($collections);

          // Mosaic regroupée par collections : on remplace les variables du template par les valeurs de la collection du noeud video
          if ($collection) {
            // thumbnail
            if(isset($collection->field_media->target_id)) {
              $collec_thumb_mosaic_img = $collection->field_media->view('sorbonne_tv_video_grid');

              $variables['collect_thumb'] = $collec_thumb_mosaic_img;
            }
            else {
              $stc_lighten = 0.3;
              $empty_color = '#ffd661';
              $lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($empty_color, $stc_lighten);
              $theme_path = \Drupal::service('extension.list.theme')->getPath('sorbonne_tv_lmc');

              // TODO : Essayer si possible de générer une img avec style d'image à partir d'image source dans le theme
              $empty_img_url = '';
              $empty_img = '';

              /*
              $image_relative_path = 'collection_empty_thumb.png';
              $image_uri = 'public://' . $image_relative_path;
              $empty_img_arr = [
                '#theme' => 'image_style',
                '#style_name' => 'sorbonne_tv_notice_collection_video_list_large',
                '#uri' => '/'. $theme_path .'/img/elements/collection_empty_thumb.png',
              ];
              $empty_img = \Drupal::service('renderer')->render($empty_img_arr);
              */

              /*
              $original_image = '/'. $theme_path .'/assets/img/elements/collection_empty_thumb.png';
              //$oi_uri = \Drupal::service('file_url_generator')->generateAbsoluteString($original_image);
              $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('sorbonne_tv_notice_collection_video_list_large');
              $destination = $style->buildUri($original_image);
              if (!file_exists($destination)) {
                $style->createDerivative($original_image, $destination);
              }
              $empty_img_url = $style->buildUrl($original_image);
              */
              

              $collection_empty_thumb = '<div class="video-thumb empty_thumb" style="background-color: '. $lighten_color .';"><div class="empty_thumb_inner">'
                .$empty_img
                //.'<img src="'. $empty_img_url .'" />'
              .'</div></div>';
              $variables['collect_thumb'] = Markup::create($collection_empty_thumb);
    
            }
            // titre
            $variables['label'] = $collection->getTitle();
            // lien
            $url = Url::fromRoute('entity.node.canonical', ['node' => $collection->id()], []);
            $variables['url'] = $url->toString();
            // body
            $variables['content']['body'] = $collection->get('body')->view('sorbonne_tv_mosaique_collection');

            // c'est un regroupement
            $variables['collection_tag'] = TRUE;
          }

          $variables['#cache']['max-age'] = 0;
        }

        // Get Disciplines & Collections Filters
        $disciplinesFilter = \Drupal::service('sorbonne_tv.videos_service')->getVideoDisciplinesContextualFiltersFormat($node);
        $collectsFilter = \Drupal::service('sorbonne_tv.videos_service')->getVideoCollectsContextualFiltersFormat($node);
        if($view_mode == 'full') {
          // Compte le resultat des vues afin de savoir si les blocs doivent êtres affichés
          $arg_nid = $node->id();
          $browse_args = [
            $arg_nid,
          ];
          if($collectsFilter && !empty($collectsFilter)) {
            $browse_args[] = $collectsFilter;
          }
          $browse_view = Views::getView('sorbonne_tv_collection');
          $browse_view->setArguments($browse_args);
          $browse_view->build('video_notice_slider');
          $browse_view->execute();
          $browse_view_results = $browse_view->result;
          $browse_query_count = count($browse_view_results);

          $recommanded_coll_args = [
            $arg_nid,
          ];
          if($collectsFilter && !empty($collectsFilter)) {
            $recommanded_coll_args[] = $collectsFilter;
          }
          if($disciplinesFilter && !empty($disciplinesFilter)) {
            $recommanded_coll_args[] = $disciplinesFilter;
          }
          $recommanded_coll_view = Views::getView('sorbonne_tv_collection');
          $recommanded_coll_view->setArguments($recommanded_coll_args);
          $recommanded_coll_view->build('recommanded_noticevideo_blk');
          $recommanded_coll_view->execute();
          $recommanded_coll_view_results = $recommanded_coll_view->result;
          $recommanded_coll_query_count = count($recommanded_coll_view_results);

          if(!empty($collectsFilter) && $browse_query_count > 0) {
            // Bloc "Parcourir la collection"
            $variables['browse_collection_blk'] = [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['browse_collection_blk']
              ],
              'block_title' => [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => '<span class="title-txt">'. t('Parcourir la collection') .'</span><span class="title-bullet"></span>',
                '#attributes' => [
                  'class' => [
                    'bullet-title'
                  ],
                ],
              ],
              'block_content' => [
                '#type' => 'container',
                '#attributes' => [
                  'class' => ['blk-content']
                ],
                '#type' => 'view',
                '#name' => 'sorbonne_tv_collection',
                '#display_id' => 'video_notice_slider',
                '#arguments' => [
                  $node->id(),
                ],
                '#attached' => [
                  'library' => [
                    'sorbonne_tv_lmc/slick'
                  ]
                ]
              ],
            ];

            if($collectsFilter && !empty($collectsFilter)) {
              $variables['browse_collection_blk']['block_content']['#arguments'][] = $collectsFilter;
            }
          }

          if($recommanded_coll_query_count > 0) {
            // Bloc "Recommande pour vous"
            $variables['recommanded_collection_blk'] = [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['recommanded_collection_blk']
              ],
              'block_title' => [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => '<span class="title-txt">'. t('Recommandé pour vous') .'</span><span class="title-bullet"></span>',
                '#attributes' => [
                  'class' => [
                    'bullet-title'
                  ],
                ],
              ],
              'block_content' => [
                '#type' => 'container',
                '#attributes' => [
                  'class' => ['blk-content']
                ],
                '#type' => 'view',
                '#name' => 'sorbonne_tv_collection',
                '#display_id' => 'recommanded_noticevideo_blk',
                '#arguments' => [
                  $node->id(),
                ],
              ],
            ];

            // Filtre : videos de la même collection à exclure
            if($collectsFilter && !empty($collectsFilter)) {
              $variables['recommanded_collection_blk']['block_content']['#arguments'][] = $collectsFilter;
            }

            // Filtre : videos de la même discipline
            if($disciplinesFilter && !empty($disciplinesFilter)) {
              $variables['recommanded_collection_blk']['block_content']['#arguments'][] = $disciplinesFilter;
            }
          }
        }

        // Share block
        $shareMarkup = Markup::create('<a href="#" class="btn-display-socials"><span class="bi bi-share"></span></a>');
        $variables['share_link'] = $shareMarkup;

        $block_manager = \Drupal::service('plugin.manager.block');
        $config = [
          'provider' => 'social_simple',
          'social_networks' => [
            'twitter' => 'twitter',
            'facebook' => 'facebook',
            'linkedin' => 'linkedin',
            'googleplus' => '0',
            'mail' => '0',
            'print' => '0',
            'entity_print_pdf' => '0',
          ]
        ];
        $plugin_block = $block_manager->createInstance('social_simple_block', $config);
        $social_simple_blk = $plugin_block->build();
        if($social_simple_blk && !empty($social_simple_blk)) {
          $variables['social_simple_blk'] = $social_simple_blk;
        }

        // Titres Collections
        $video_collections = [];
        if( $video_collections = \Drupal::service('sorbonne_tv.videos_service')->getVideoCollects($node) ) {
          $variables['video_collections'] = $video_collections;
        }

        // annee de depot
        if(isset($node->field_annee_depot->value)) {
          $anne_depot_tstp = strtotime($node->field_annee_depot->value);
          $anne_depot_ddt = DrupalDateTime::createFromTimestamp($anne_depot_tstp);
          $anne_depot = $anne_depot_ddt->format('Y');
          $variables['anne_depot'] = $anne_depot;
        }

        // Video Langue
        $video_language = FALSE;
        if(isset($node->field_langue->value)) {
          $video_language = sorbonne_tv_lmc_get_video_language($node->field_langue->value);

          // Si pas de correspondance
          if(!$video_language) {
            $video_language = $node->field_langue->value;
          }
        }
        $variables['video_language'] = $video_language;

        // Duree
        $video_duration = FALSE;
        if(isset($node->field_duree->value)) {
          $duree_val = $node->field_duree->value;
          $duree_h = gmdate('H', $duree_val);
          $duree_i = gmdate('i', $duree_val);
          $duree_s = gmdate('s', $duree_val);

          if( (int)$duree_h > 0 ) {
            $video_duration = $duree_h .' h '. $duree_i .' min '. $duree_s .' s';
          }
          elseif( (int)$duree_i > 0 ) {
            $video_duration = $duree_i .' min '. $duree_s .' s';
          }
          else {
            $video_duration = $duree_s .' s';
          }
        }
        $variables['video_duration'] = $video_duration;

        // Podcast
        $is_podcast = FALSE;
        if(isset($node->field_structure_assoc_public->target_id)) {
          $video_type_term = Term::load($node->field_structure_assoc_public->target_id);

          if( strtolower($video_type_term->getName()) == 'podcast') {
            $is_podcast = TRUE;
          }
        }
        $variables['is_podcast'] = $is_podcast;

        // Contenu sensible
        $is_sensitive = FALSE;
        $sensitive_tag = '';
        if(isset($node->field_tag_video->target_id)) {
          if($video_tag_terms = $node->field_tag_video->ReferencedEntities()) {

            foreach($video_tag_terms as $tag_k => $tag) {
              if( strtolower($tag->getName()) == 'contenu sensible') {
                $is_sensitive = TRUE;
                $sensitive_tag = $tag->getName();
                break;
              }
            }
          }
        }
        $variables['is_sensitive'] = $is_sensitive;
        $variables['sensitive_tag'] = $sensitive_tag;

        // Pictos sourds / malentendants
        $has_vtt = FALSE;
        $has_audio_for_video = FALSE;
        if(isset($node->field_sorb_tv_video_vtt->value) && !empty($node->field_sorb_tv_video_vtt->value)) {
          $has_vtt = TRUE;

          $track = $node->field_sorb_tv_video_vtt->value;
          $vtt= explode('~', $track);
          $vtt_kind = (isset($vtt[0]) ? trim($vtt[0]) : '');
          $vtt_srclang = (isset($vtt[1]) ? trim($vtt[1]) : '');
          $vtt_src = (isset($vtt[2]) ? trim($vtt[2]) : '');

          $variables['vtt_src'] = $vtt_src;
          $variables['vtt_kind'] = $vtt_kind;
          $variables['vtt_srclang'] = $vtt_srclang;

        }
        if(isset($node->field_sorb_tv_video_audio->value) && !empty($node->field_sorb_tv_video_audio->value)) {
          $has_audio_for_video = TRUE;
        }
        $variables['has_vtt'] = $has_vtt;
        $variables['has_audio_for_video'] = $has_audio_for_video;

        // Date diffusion
        if(isset($node->field_date_maj->value)) {
          $date_diffu_tstp = strtotime($node->field_date_maj->value);
          $date_diffu_ddt = DrupalDateTime::createFromTimestamp($date_diffu_tstp);
          $date_diffu = $date_diffu_ddt->format('d/m/Y');
          $variables['date_diffu'] = $date_diffu;
        }
      break;

      case 'mosaic':
        if(isset($node->field_media->entity)) {
          $page_header_img = $node->field_media->view('sorbonne_tv_notice_page');

          $page_intro_wrapper['mosaic_introimg_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'mosaic_introimg_wrapper',
                'col-lg-6',
              ],
            ],
            $page_header_img,
          ];
        }

        $page_intro_wrapper['mosaic_intro_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'mosaic_intro_wrapper',
            ],
          ],
        ];

        if(isset($node->field_media->entity)) {
          $page_intro_wrapper['mosaic_intro_wrapper']['#attributes']['class'][] = 'col-lg-6';
        }

        if(isset($node->body->value)) {
          $page_intro_wrapper['mosaic_intro_wrapper']['page-intro'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'page-intro',
              ]
            ],
            'body' => $node->body->view('full'),
          ];
        }

        if(isset($node->body->value) || isset($node->field_media->entity)) {
          $variables['page_intro_wrapper'] = $page_intro_wrapper;

          $stc_lighten = 0.3;
          $empty_color = '#F1F4F6';
          //$empty_color = '#FFD700';
          //$lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($empty_color, $stc_lighten);
          $mosaic_intro_wrapper_style = 'background-color: '. $empty_color .';';

          $variables['mosaic_intro_wrapper_style'] = $mosaic_intro_wrapper_style;
        }
      break;

      default:
      break;
    }

  }
}

function sorbonne_tv_lmc_get_video_language($lang_id) {
  $lang_title = FALSE;

  $lang_corresp = [
    'fr' => 'Français',
    'en' => 'Anglais',
    'de' => 'Allemand',
  ];

  if( array_key_exists($lang_id, $lang_corresp) ) {
    $lang_title = $lang_corresp[$lang_id];
  }

  return $lang_title;
}

/*
function sorbonne_tv_lmc_preprocess_media(&$variables) {
  $elements = $variables['elements'];
  $the_media = $variables['media'];
  $vm = $elements['#view_mode'];

  if($vm == 'sorbonne_tv_numbered_list') {
    $media_bundle = $the_media->bundle();

    if($media_bundle == 'image') {
    }
  }
}
*/

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function sorbonne_tv_lmc_preprocess_paragraph(&$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  $stc_prg_lighten = 0.3;
  //$stc_prg_darken = 1.3;

  // Get the type.
  $bundle = $paragraph->bundle();

  switch($bundle) {
    // prg edito
    case 'sorbonne_tv_prg_edito':
      $prg_color = FALSE;
      $has_media = FALSE;

      $variables['attributes']['class'][] = 'container';
      $prg_container_attr = new Attribute();
      $prg_container_attr->addClass('prg_inner_container');

      if(isset($paragraph->field_sorbonne_tv_prg_media->target_id)) {
        $has_media = TRUE;
        $variables['attributes']['class'][] = 'has_media';
      }
      $variables['has_media'] = $has_media;

      if(isset($paragraph->field_sorbonne_tv_prg_color->value)) {
        $prg_color = $paragraph->field_sorbonne_tv_prg_color->value;
        $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);

        //$variables['attributes']['style'] = 'background-color: '. $prg_lighten_color .';';
        $prg_container_attr->setAttribute('style', 'background-color: '. $prg_lighten_color .';');
      }

      $variables['content']['graphic_circle'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'class' => [
            'graphic_circle'
          ],
        ],
      ];

      if($prg_color) {
        $variables['content']['graphic_circle']['#attributes']['style'] = 'background-color: '. $prg_color .';';
      }

      $variables['prg_container_attr'] = $prg_container_attr;
    break;

    case 'sorbonne_tv_playlist':
      $variables['#attached']['library'][] = 'sorbonne_tv_lmc/slick';
      $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($paragraph);
      $prg_tpl = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_playlist_tpl($paragraph);
      $variables['prg_tpl'] = $prg_tpl;

      // Couleur de fond
      $prg_styles = '';
      if(isset($paragraph->field_sorbonne_tv_prg_bg_color->value)) {
        $prg_styles = 'background-color: '. $paragraph->field_sorbonne_tv_prg_bg_color->value .';';
        $variables['attributes']['class'][] = 'has_bgcolor';
      }

      if(!empty($prg_styles)) {
        $variables['attributes']['style'] = $prg_styles;
      }

      if($prg_tpl == '2_lines') {
        $edito_title = FALSE;
        $edito_text = FALSE;
        if(isset($paragraph->field_sorbonne_tv_playlist_title->value)) {
          $edito_title = $paragraph->field_sorbonne_tv_playlist_title->value;
        }
        if(isset($paragraph->field_sorbonne_tv_playlist_text->value)) {
          $edito_text = $paragraph->field_sorbonne_tv_playlist_text->value;
        }

        if(!$edito_title && !$edito_text) {
          $variables['attributes']['class'][] = 'playlist_2_lines_noedito';
          $variables['playlist_edito'] = FALSE;
        }
        else {
          $playlist_edito = '<div class="playlist_edito_wrapper">'
            .'<div class="playlist_edito" '. ($prg_color ? 'style="background-color: '. $prg_color .';"' : '') .'>'
              .($edito_title ? '<div class="edito_title">'. $edito_title .'</div>' : '')
              .($edito_text ? '<div class="edito_text">'. $edito_text .'</div>' : '')
            .'</div>'
          .'</div>';

          $variables['playlist_edito'] = Markup::create($playlist_edito);
        }
      }

      $playlist_mode = FALSE;
      if(isset($paragraph->field_sorbonne_tv_playlist_mode->value)) {
        $playlist_mode = $paragraph->field_sorbonne_tv_playlist_mode->value;
      }
      $variables['playlist_mode'] = $playlist_mode;

      $view_display = FALSE;
      $args_spe = FALSE;
      $args_disciplines = FALSE;
      switch($playlist_mode) {
        case 'by_collection':
          $view_display = ($prg_tpl == '2_lines' ? 'stv_playlist_by_collection_2l' : 'stv_playlist_by_collection');

          if(isset($paragraph->field_sorbonne_tv_modecollection->target_id)) {
            $args_spe = $paragraph->field_sorbonne_tv_modecollection->target_id;
          }
        break;

        case 'by_tag':
          $view_display = ($prg_tpl == '2_lines' ? 'stv_playlist_by_tag_2l' : 'stv_playlist_by_tag');

          if(isset($paragraph->field_sorbonne_tv_modetag->target_id)) {
            $args_spe = $paragraph->field_sorbonne_tv_modetag->target_id;
          }
        break;

        case 'by_disciplines':
          //$view_display = ($prg_tpl == '2_lines' ? 'stv_playlist_by_tag_2l' : 'stv_playlist_by_tag');
          $view_display = 'stv_playlist_by_disciplines';

          if(isset($paragraph->field_sorbonne_tv_modediscipline->target_id)) {
            $playlist_disciplines = $paragraph->field_sorbonne_tv_modediscipline->referencedEntities();


            foreach($playlist_disciplines as $pd_k => $pd_val) {
              $args_disciplines[] = $pd_val->id();
            }
          }
        break;

        default:
        break;
      }

      if($view_display) {
        $playlist_blk = [
          '#type' => 'container',
          '#attributes' => [
            'class'=> [
              'playlist_'. $playlist_mode .'_blk',
            ]
          ],
          'block_content' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['blk-content']
            ],
            'playlist' => [
              '#type' => 'view',
              '#name' => 'sorbonne_tv_collection',
              '#display_id' => $view_display,
            ],
          ],
        ];

        if($args_spe) {
          $playlist_blk['block_content']['playlist']['#arguments'][] = $args_spe;
        }

        if($args_disciplines) {
          $playlist_blk['block_content']['playlist']['#arguments'][] = implode(',', $args_disciplines);
        }

        $variables['playlist_blk'] = $playlist_blk;
      }
    break;

    case 'button_grid_item':
      if( $parent_paragraph = $paragraph->getParentEntity() ) {
        $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($parent_paragraph);

        if($prg_color) {
          $variables['attributes']['style'] = 'background-color: '. $prg_color .'; color: #FFFFFF;';
        }
      }
    break;

    case 'sorbonne_tv_filters':
      $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($paragraph);

      // Couleur de fond
      $prg_styles = '';
      if(isset($paragraph->field_sorbonne_tv_prg_bg_color->value)) {
        $prg_styles = 'background-color: '. $paragraph->field_sorbonne_tv_prg_bg_color->value .';';
        $variables['attributes']['class'][] = 'has_bgcolor';
      }

      if(!empty($prg_styles)) {
        $variables['attributes']['style'] = $prg_styles;
      }

      if(isset($paragraph->field_sorbonne_tv_filter_on->value)) {
        $filter_on = $paragraph->field_sorbonne_tv_filter_on->value;
        $variables['attributes']['class'][] = 'filters_on_'. $filter_on;
      }

      if(isset($paragraph->field_sorbonne_tv_filter_type->value)) {
        $filter_by = $paragraph->field_sorbonne_tv_filter_type->value;
        $variables['attributes']['class'][] = 'filters_type_'. $filter_by;

        switch($filter_by) {
          case 'types':
            $variables['#attached']['library'][] = 'sorbonne_tv_lmc/slick';
          break;

          case 'duration':
            $block_manager = \Drupal::service('plugin.manager.block');
            $config = [
              'color' => $prg_color,
            ];
            $plugin_block = $block_manager->createInstance('SorbonneTvTimeLapseFiltersBlock', $config);
            $access_result = $plugin_block->access(\Drupal::currentUser());

            if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
              $variables['duration_filters_blk'] = FALSE;
            }

            $render = $plugin_block->build();
            $variables['duration_filters_blk'] = $render;
          break;

          default:
          break;
        }
      }
    break;

    case 'stv_discipline_filters_item':
      $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($paragraph);

      if($prg_color) {
        $variables['attributes']['style'] = 'background-color: '. $prg_color .';';
      }

      if(isset($paragraph->field_sorbonne_tv_color_txt->value)) {
        $variables['attributes']['style'] .= 'color: '. $paragraph->field_sorbonne_tv_color_txt->value .';';
      }

      $searchQuery = [];
      if($disciplines = $paragraph->field_stv_discipline_filters->ReferencedEntities() ) {
        foreach($disciplines as $dis_k => $discipline) {
          $searchQuery['disciplines'][] = $discipline->id();
        }
      }

      $filter_mk = t('Filter');
      $filter_url_obj = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->set_views_filter_link('view.sorbonne_tv_search.stv_search_page', $searchQuery, 'url_obj', $filter_mk);

      $variables['filter_url'] = $filter_url_obj->toString();
    break;

    case 'bp_simple':
      if( $parent = $paragraph->getParentEntity() ) {
        $parent_type = $parent->getType();
        if($parent_type == 'page_sorbonne_tv') {
          $ss_type = (isset($parent->field_sorb_tv_type->value) ? $parent->field_sorb_tv_type->value : FALSE);

          if($ss_type == 'page') {
            $variables['attributes']['class'][] = 'container';
          }
        }
      }
    break;

    case 'rich_content':
      if( $parent = $paragraph->getParentEntity() ) {
        $parent_type = $parent->getType();
        if($parent_type == 'page_sorbonne_tv') {
          $ss_type = (isset($parent->field_sorb_tv_type->value) ? $parent->field_sorb_tv_type->value : FALSE);

          if($ss_type == 'page') {
            $variables['attributes']['class'][] = 'container';
          }
        }
      }

      // Bon style d'image adaptif en fonction de la position pour la partie Sorbonne TV
      if(isset($paragraph->field_image_position->value)) {
        $img_pos = $paragraph->field_image_position->value;

        if($img_pos != 'row' && $img_pos != 'row-reverse') {
          $variables['content']['field_media_image'] = $paragraph->field_media_image->view('sorbonne_tv_notice_page_img_fullwidth');
        }
        else {
          $variables['content']['field_media_image'] = $paragraph->field_media_image->view('sorbonne_tv_notice_page');
        }
      }
    break;

    case 'sorbonne_tv_prg_numbered_list':
      $variables['#attached']['library'][] = 'sorbonne_tv_lmc/slick';

      if(isset($paragraph->field_stv_prg_numbered_list_type->value)) {
        $numbered_list_type = $paragraph->field_stv_prg_numbered_list_type->value;

        if($numbered_list_type == 'auto') {
          $max_items = 5;
          $top_videos = \Drupal::service('sorbonne_tv.videos_service')->getTopVideosNumberedListItems($max_items);
          $top_videos_mk = '';

          if($top_videos && !empty($top_videos)) {
            foreach($top_videos as $tv_k => $top_video) {
                $top_videos_mk .= \Drupal::service('renderer')->render($top_video);
            }
          }

          $variables['content']['field_stv_prg_video'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['paragraph--type--sorbonne-tv-prg-numbered-list']
            ],
            'top_videos_items_wrapper' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['prg_stv_numbered_list', 'no_slider']],
              '#markup' => Markup::create($top_videos_mk),
            ],
          ];
        }
      }
    break;

    default:
    break;
  }
}

/**
 * Implements hook_preprocess_field().
 */
function sorbonne_tv_lmc_preprocess_field (&$variables, $hook) {
  $field_name = $variables['field_name'];
  $element    = $variables['element'];
  $view_mode = $element['#view_mode'];
  $stc_prg_lighten = 0.3;

  switch($field_name) {
    case 'field_date_maj':

      if(
        $view_mode == 'sorbonne_tv_video_notice_recommanded'
        || $view_mode == 'sorbonne_tv_video_grid'
        || $view_mode == 'sorbonne_tv_numbered_list'
        || $view_mode == 'sorbonne_tv_playlist'
      ) {
        $def_oput = $variables['items'][0]['content'];

        if (isset($element['#object'])) {
          if ($parentEntity = $element['#object']) {
            if(isset($parentEntity->field_date_maj->value)) {
              $date_diffu_tstp = strtotime($parentEntity->field_date_maj->value);
              $date_diffu_ddt = DrupalDateTime::createFromTimestamp($date_diffu_tstp);
              $date_diffu = $date_diffu_ddt->format('d/m/Y');
              $variables['items'][0]['content'] = $date_diffu;
            }
          }
        }
      }

    break;

    case 'field_prg_linked_title':
      if (isset($element['#object'])) {
        if ($parent_paragraph = $element['#object']) {
          $parent_bundle = $parent_paragraph->bundle();

          if(
            $parent_bundle == 'sorbonne_tv_prg_numbered_list'
            || $parent_bundle == 'sorbonne_tv_playlist'
            || $parent_bundle == 'sorbonne_tv_ctas'
            || $parent_bundle == 'sorbonne_tv_filters'
          ) {
            $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($parent_paragraph);
            $prg_lighten_color = FALSE;

            if($prg_color) {
              $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);
            }

            foreach ($variables['items'] as $delta => $item) {
              $def_content = $variables['items'][$delta]['content'];
              $title_link = '<h2 class="bullet-title">'
                .'<span class="title-txt">'. $def_content['#title'] .'</span>'
                .'<span class="title-bullet" '.($prg_color ? 'id="" style="background-color: '. $prg_color .';"' : '') .'"></span>'
              .'<h2>';

              $title_link_mk = Markup::create($title_link);
              $variables['items'][$delta]['content']['#title'] = $title_link_mk;
            }
          }
        }
      }
    break;

    case 'field_title':

      if (isset($element['#object'])) {
        if ($parent_paragraph = $element['#object']) {
          $parent_bundle = $parent_paragraph->bundle();

          if(
            $parent_bundle == 'sorbonne_tv_prg_numbered_list'
            || $parent_bundle == 'sorbonne_tv_playlist'
            || $parent_bundle == 'sorbonne_tv_ctas'
            || $parent_bundle == 'sorbonne_tv_filters'
          ) {

            $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($parent_paragraph);
            $prg_lighten_color = FALSE;

            if($prg_color) {
              $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);
            }

            foreach ($variables['items'] as $delta => $item) {
              $def_content = $variables['items'][$delta]['content'];
              $def_content_rendered = \Drupal::service('renderer')->render($def_content);
              $def_content_str = $def_content_rendered->__toString();

              $bullet_title = '<span class="title-txt">'. $def_content_str .'</span>'
              .'<span class="title-bullet" '.($prg_color ? 'id="" style="background-color: '. $prg_color .';"' : '') .'"></span>';

              $variables['items'][$delta]['content'] = [
                '#type' => 'html_tag',
                '#tag' => 'h2',
                '#value' => Markup::create($bullet_title),
                '#attributes' => [
                  'class' => ['bullet-title']
                ]
              ];
            }

          }
        }
      }
    break;

    case 'field_stv_prg_video':
      if (isset($element['#object'])) {
        if ($parent_paragraph = $element['#object']) {
          $parent_bundle = $parent_paragraph->bundle();

          if($parent_bundle == 'sorbonne_tv_prg_numbered_list') {
            if($granpa_node = $parent_paragraph->getParentEntity()) {
              if($granpa_node->getType() == 'page_sorbonne_tv') {
                $ss_type = (isset($granpa_node->field_sorb_tv_type->value) ? $granpa_node->field_sorb_tv_type->value : FALSE);

                // Sur page d'accueil ce type de prg n'affichera que 3 elmts
                if($ss_type && $ss_type == 'page_hp') {
                  $item_i = 1;
                  $tmp_items_arr = $variables['items'];
                  foreach ($tmp_items_arr as $delta => $item) {
                    if($item_i > 3) {
                      unset($variables['items'][$delta]);
                    }

                    $item_i++;
                  }
                }
              }
            }
          }
        }
      }
    break;

    case 'field_sorbonne_tv_playlist_items':
      if (isset($element['#object'])) {
        if ($parent_paragraph = $element['#object']) {
          $parent_bundle = $parent_paragraph->bundle();

          if($parent_bundle == 'sorbonne_tv_playlist') {
            $prg_tpl = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_playlist_tpl($parent_paragraph);
            $variables['prg_tpl'] = $prg_tpl;

            $prg_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->get_prg_color($parent_paragraph);
            $prg_lighten_color = FALSE;

            if($prg_color) {
              $prg_lighten_color = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->hex2rgba($prg_color, $stc_prg_lighten);
            }

            foreach ($variables['items'] as $delta => $item) {
              if(isset($item['content']['#node']) && $item['content']['#node']->getType() == 'page_sorbonne_tv') {
                $ss_type = (isset($item['content']['#node']->field_sorb_tv_type->value) ? $item['content']['#node']->field_sorb_tv_type->value : FALSE);

                // Couleur de fond pour les items collection des playlists
                if($ss_type == 'collection') {
                  $variables['items'][$delta]['attributes']->addClass('playlist-item-collection');

                  if($prg_color) {
                    $variables['items'][$delta]['attributes']->setAttribute('style', 'background-color: '. $prg_color .'; color: #FFFFFF;');
                  }
                  else {
                    $variables['items'][$delta]['attributes']->setAttribute('style', 'background-color: #00326E; color: #FFFFFF;');
                  }
                }
              }
            }

          }
        }
      }
    break;

    case 'field_sorbonne_tv_videos_types':
      foreach ($variables['items'] as $delta => $item) {
        $def_content = $variables['items'][$delta]['content'];
        $item_term = $def_content['#entity'];
        $item_styles = '';

        if(isset($item_term->field_sorbonne_tv_color->value)) {
          $item_styles .= 'background-color: '. $item_term->field_sorbonne_tv_color->value .';';
        }

        if(isset($item_term->field_sorbonne_tv_color_txt->value)) {
          $item_styles .= 'color: '. $item_term->field_sorbonne_tv_color_txt->value .';';
        }

        if(!empty($item_styles)) {
          $variables['items'][$delta]['attributes']->setAttribute('style', $item_styles);
        }

        $picto = FALSE;
        if (isset($item_term->field_sorbonne_tv_picto->entity->field_media_image->entity)) {
          $picto_obj = $item_term->field_sorbonne_tv_picto->entity->field_media_image->entity;

          if ($picto_obj) {
            $picto_uri = $picto_obj->getFileUri();
            $picto_url = ImageStyle::load('sorbonne_tv_picto')->buildUrl($picto_uri);

            $picto = '<img src="'. $picto_url .'" width="" height="" />';
          }
        }

        $new_output = '<span class="item_icon">'. $picto .'</span><span class="item_title">'. $item_term->getName() .'</span>';

        $searchQuery['type'] = $item_term->id();
        $filter_mk = Markup::create($new_output);
        $filter_link_str = \Drupal::service('sorbonne_tv.sorbonne_tv_service')->set_views_filter_link('view.sorbonne_tv_search.stv_search_page', $searchQuery, 'link_string', $filter_mk);

        $variables['items'][$delta]['content'] = Markup::create($filter_link_str);
      }
    break;

    case 'field_sorbonne_tv_links_filter':
      if (isset($element['#object'])) {
        if ($parent_paragraph = $element['#object']) {
          $parent_bundle = $parent_paragraph->bundle();

          if($parent_bundle == 'sorbonne_tv_links_filter_prg') {
            $picto = FALSE;
            $color = FALSE;
            $bgcolor = FALSE;
            if (isset($parent_paragraph->field_stv_link_filter_picto->entity->field_media_image_3->entity)) {
              $picto_obj = $parent_paragraph->field_stv_link_filter_picto->entity->field_media_image_3->entity;

              if ($picto_obj) {
                $picto_uri = $picto_obj->getFileUri();
                $picto_url = ImageStyle::load('sorbonne_tv_picto')->buildUrl($picto_uri);

                $picto = '<img src="'. $picto_url .'" width="" height="" />';
              }
            }

            if(isset($parent_paragraph->field_sorbonne_tv_color_txt->value)) {
              $color = $parent_paragraph->field_sorbonne_tv_color_txt->value;
            }
            if(isset($parent_paragraph->field_sorbonne_tv_prg_color->value)) {
              $bgcolor = $parent_paragraph->field_sorbonne_tv_prg_color->value;
            }

            foreach ($variables['items'] as $delta => $item) {
              $def_content = $variables['items'][$delta]['content'];

              $new_title = ($picto ? '<span class="item_icon">'. $picto .'</span>' : '') .'<span class="item_title">'. $def_content['#title'] .'</span>';
              $filter_mk = Markup::create($new_title);

              $variables['items'][$delta]['content']['#title'] = $filter_mk;
              $link_style = '';

              if($color) {
                $link_style .= (!empty($link_style) ? ' ' : '') .'color: '. $color .';';
              }
              if($bgcolor) {
                $link_style .= (!empty($link_style) ? ' ' : '') .'background-color: '. $bgcolor .';';
              }

              if(!empty($link_style)) {
                $variables['items'][$delta]['content']['#options']['attributes']['style'] = $link_style;
              }

            }
          }
        }
      }
    break;

    default:
    break;
  }
}

/**
 * Implements hook_preprocess_views_view().
 */
function sorbonne_tv_lmc_preprocess_views_view(&$variables) {
  $view = $variables['view'];

  $view_id    = $view->storage->id();
  $display_id = $view->current_display;

  if ($view_id == 'sorbonne_tv_search' && $display_id == 'stv_search_page') {
    $variables['attributes']['class'][] = 'programs-page-wrapper';
    $variables['attributes']['class'][] = 'container';
  }
}

function sorbonne_tv_lmc_preprocess_views_view_list(&$variables) {
  $view = $variables['view'];
  $view_id    = $view->storage->id();
  $display_id = $view->current_display;
  $rows = $variables['rows'];

  if ($view_id == 'sorbonne_tv_search' && $display_id == 'stv_mosaique_collection_block') {
    $nb_rows = count($rows); // rows correspond à un regroupement
    if($nb_rows > 1) { // S'il y a plus d'une video dans le regroupement par collection
      foreach ($rows as $id => $row) {
        // Change le display mode du noeud video
        $variables['rows'][$id]['content']['#view_mode'] = 'sorbonne_tv_mosaique_collection';
      }
    }
  }
}

/**
 * Implements hook_preprocess_hook().
 */
function sorbonne_tv_lmc_preprocess_menu(&$variables) {

  if(isset($variables['menu_name'])) {
    $menu_name = $variables['menu_name'];

    if($menu_name == 'site-126') {
      $config = \Drupal::config('sorbonne_tv.settings');
      $api_flux_video = $config->get('sorbonne_tv.settings.api_flux_video');
      $links = [];

      if (!empty($api_flux_video['link1'])) {
        $links['link1'] = $api_flux_video['link1'];
      }
      if (!empty($api_flux_video['link2'])) {
        $links['link2'] = $api_flux_video['link2'];
      }
      if (!empty($api_flux_video['link3'])) {
        $links['link3'] = $api_flux_video['link3'];
      }

      $variables['shortcuts_links'] = $links;
    }
  }
}
