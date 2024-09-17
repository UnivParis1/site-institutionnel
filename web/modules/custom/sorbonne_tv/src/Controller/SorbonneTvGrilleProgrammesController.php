<?php

namespace Drupal\sorbonne_tv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Link;
use Drupal\Core\Url;

class SorbonneTvGrilleProgrammesController extends ControllerBase {
  
    public function GetGrilleProgrammesTitle() {
        $page_title = t('Programme');

        return $page_title;
    }

    public function GetGrilleProgrammesContent() {
        $content = [];
        $private_files_folder_path = \Drupal::service('file_system')->realpath("private://");
        $json_files_folder = $private_files_folder_path .'/sorbonne-tv/programmes';
        $json_files = scandir($json_files_folder);
        $current_time = \Drupal::time()->getCurrentTime();
        $json_date_time = $current_time; // Par defaut
        $program_items = [];

        $json_date_ddt = DrupalDateTime::createFromTimestamp($json_date_time);
        $json_date = $json_date_ddt->format('Y-m-d');

        // Date si filtre
        if( $SelectedDay = \Drupal::request()->query->get('date') ) {
            $json_date = $SelectedDay;
        }

        // Filtre période
        $json_period = FALSE;
        $SelectedPeriodArr = FALSE;
        $allPeriod = TRUE;
        $periodFiltersVals = [];

        if( $SelectedPeriod = \Drupal::request()->query->get('period') ) {
            $json_period = $SelectedPeriod;
            $SelectedPeriodArr = explode('+', $json_period);

            // Si on a des valeurs selectionnees et si toutes les preriodes sont selectionnees, on affiche tt
            if($SelectedPeriodArr && !empty($SelectedPeriodArr)) {
                if(
                    array_search('morning', $SelectedPeriodArr) !== FALSE 
                    && array_search('noon', $SelectedPeriodArr) !== FALSE 
                    && array_search('evening', $SelectedPeriodArr) !== FALSE
                ) {
                    $allPeriod = TRUE;
                }
                else {
                    $allPeriod = FALSE;
                }
            }

            foreach($SelectedPeriodArr as $per_k => $per_val) {
                switch ($per_val) {
                    case 'morning':
                        $periodFiltersVals[$per_val] = [
                            //'hmin' => 0,
                            'hmin' => 6,
                            'hmax' => 12,
                        ];
                    break;

                    case 'noon':
                        $periodFiltersVals[$per_val] = [
                            'hmin' => 12,
                            'hmax' => 18,
                        ];
                    break;

                    case 'evening':
                        $periodFiltersVals[$per_val] = [
                            'hmin' => 18,
                            'hmax' => 24,
                        ];
                    break;

                    default:
                    break;
                }
            }
        }

        if( $json_k = array_search($json_date .'.json', $json_files) ) {
            $program_file = file_get_contents($json_files_folder .'/'. $json_files[$json_k]);
            $program_datas = json_decode($program_file, true);

            if($program_datas) {
                foreach($program_datas[$json_date] as $datas_key => $data) {
                    $data_title = (isset($data['title']) ? $data['title'] : '');
                    $data_thum = (isset($data['image']) ? $data['image'] : ''); // Par Defaut
                    $data_descr = (isset($data['description']) ? $data['description'] : '');
                    $data_start = (isset($data['start']) ? $data['start'] : '');

                    $item_start_tstp = strtotime($data_start);
                    $item_start_h = date('H', $item_start_tstp);
                    $item_start_g = date('G', $item_start_tstp);
                    $item_start_i = date('i', $item_start_tstp);

                    // Filte par periode
                    $display_item = ($allPeriod ? TRUE : FALSE);
                    if(!$allPeriod && !empty($periodFiltersVals)) {
                        foreach($periodFiltersVals as $pFilterK => $pFilterV) {
                            //gere le cas de 

                            if($item_start_g >= $pFilterV['hmin'] && $item_start_g < $pFilterV['hmax']) {
                                $display_item = TRUE;
                                break;
                            }
                            else {
                                continue;
                            }
                        }
                    }

                    if($display_item) {
                        // Couper la description si trop longue
                        if (!empty($data_descr) && strlen($data_descr) > 120) {
                            $data_descr = substr($data_descr, 0, strrpos(substr($data_descr, 0, 120), ' ')) . ' ...';
                        }

                        $video_nid = FALSE;
                        $video_title = '';
                        $video_node = FALSE;
                        if(isset($data['id'])) {
                            $video_id = str_replace('.mp4', '', $data['id']);
                            if( $video_node = \Drupal::service('sorbonne_tv.videos_service')->getStvNodeByVideoId($video_id, 'video') ) {
                                $video_nid = $video_node->id();
                                $video_title = $video_node->getTitle();

                                if ($node_couv_media = $video_node->field_media->entity) {
                                    if ($node_couv_file = $node_couv_media->field_media_image->entity) {
                                        if ($node_couv_file_uri = $node_couv_file->getFileUri()) {
                                            $image_style_name = 'sorbonne_tv_program_list';

                                            //$data_thum = ImageStyle::load($image_style_name)->buildUrl($node_couv_file_uri);
                                            $data_thum_uri = ImageStyle::load($image_style_name)->buildUri($node_couv_file_uri);
                                            $data_thum = \Drupal::service('file_url_generator')->generateAbsoluteString($data_thum_uri);

                                            // Load the image style.
                                            $style = ImageStyle::load($image_style_name);
                                            
                                            // If the derivative doesn't exist yet (as the image style may have been
                                            // added post launch), create it.
                                            if (!file_exists($data_thum_uri)) {
                                                $style->createDerivative($node_couv_file_uri, $data_thum_uri);
                                            }
                                        }
                                    }
                                }

                            }
                        }

                        if($video_node && !empty($data_thum)) { // Si on a une correspondance video et une image
                            $item_thumb = '<img src="'. $data_thum .'" alt="'. ($data_title ? $data_title : '') .'" />';
                            $item_thumb_markup = Markup::create($item_thumb);
                        }

                        $item_play_btn = '<span class="bi bi-play-circle-fill"></span>';
                        //$item_play_btn = '<span class="btn-icon"></span>';
                        $item_play_btn_markup = Markup::create($item_play_btn);

                        // Set links
                        if($video_nid) {
                            $item_link_url = Url::fromRoute('entity.node.canonical', ['node' => $video_nid], []);
                            $options = [
                                'attributes' => [
                                    'title' => t('Lien vers la page de la video : @video_title', ['@video_title' => $video_title]),
                                ],
                            ];
                            $item_link_url->setOptions($options);
                            
                            $item_thumb = Link::fromTextAndUrl($item_thumb_markup, $item_link_url)->toString();

                            if(!empty($data_title)) {
                                $data_title = Link::fromTextAndUrl($data_title, $item_link_url)->toString();
                            }

                            $item_play_btn = Link::fromTextAndUrl($item_play_btn_markup, $item_link_url)->toString();
                        }

                        // Build html
                        $item_content = (!empty($data_thum) ? '<div class="item-thumb">'. $item_thumb .'</div>' : '')
                        .'<div class="item-main">'
                            .'<div class="item-start">'
                                .'<div class="item-start-titme">'. $item_start_h .'h'. $item_start_i .'</div>'
                                .'<div class="item-play-btn">'. $item_play_btn .'</div>'
                                .'<div class="item-line"></div>'
                            .'</div>'
                            .'<div class="item-fields">'
                                .(!empty($data_title) ? '<h2 class="item-title">'. $data_title .'</h2>' : '')
                                .(!empty($data_descr) ? '<div class="item-descr">'. $data_descr .'</div>' : '')
                            .'</div>'
                        .'</div>';

                        $program_items[$datas_key] = Markup::create($item_content);
                    }
                }
            }
        }

        // Les plus écoutés (Top Articles)
        $max_items = 3;
        $top_videos = \Drupal::service('sorbonne_tv.videos_service')->getTopVideosNumberedListItems($max_items);
        $top_videos_mk = '';

        if($top_videos && !empty($top_videos)) {
            foreach($top_videos as $tv_k => $top_video) {
                $top_videos_mk .= \Drupal::service('renderer')->render($top_video);
            }
        }

        $content = [
            'programs_page_wrapper' => [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['programs-page-wrapper', 'container', 'd-lg-flex', 'align-items-lg-start']
                ],
                'content_main' => [
                    '#type' => 'container',
                    '#attributes' => [
                        'class' => ['programs-main', 'col-lg-9']
                    ],
                    'program_items' => [
                        '#theme' => 'item_list',
                        '#list_type' => 'ul',
                        '#attributes' => ['class' => ['program-items']],
                        '#items' => $program_items,
                    ],
                ],
                'content_sidebar' => [
                    '#type' => 'container',
                    '#attributes' => [
                        'class' => ['program-sidebar', 'col-lg-3']
                    ],
                    'top_videos_blk' => [
                        '#type' => 'container',
                        '#attributes' => [
                            'class' => ['paragraph--type--sorbonne-tv-prg-numbered-list']
                        ],
                        'top_videos_title_wrapper' => [
                            '#type' => 'container',
                            '#attributes' => ['class' => ['prg_stv_title']],
                            'top_videos_title' => [
                                '#type' => 'html_tag',
                                '#tag' => 'h2',
                                '#value' => '<span class="title-txt">'. t('Les plus écoutés') .'</span>',
                                '#attributes' => ['class' => []],
                            ],
                        ],
                        'top_videos_items_wrapper' => [
                            '#type' => 'container',
                            '#attributes' => ['class' => ['prg_stv_numbered_list', 'no_slider']],
                            '#markup' => Markup::create($top_videos_mk),
                        ],
                    ],
                ]
            ],
            '#cache' => [
                'contexts' => [
                    'url.query_args:date',
                ]
            ]
        ];

        return $content;
    }

}
