<?php

namespace Drupal\sorbonne_tv\Service;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Link;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;

class SorbonneTvService {

    // Get paragraph color
    public function get_prg_color($paragraph) {
        $prg_color = FALSE;
        
        if(isset($paragraph->field_sorbonne_tv_prg_color->value)) {
            $prg_color = $paragraph->field_sorbonne_tv_prg_color->value;
        }
        
        return $prg_color;
    }

    // Get paragraph playlist temlate
    public function get_prg_playlist_tpl($paragraph) {
        $prg_tpl = FALSE;
        
        if(isset($paragraph->field_sorbonne_tv_playlist_type->value)) {
            $prg_tpl = $paragraph->field_sorbonne_tv_playlist_type->value;
        }
        
        return $prg_tpl;
    }

    // Build search filter link on item
    public function set_views_filter_link($route_name, $search_query, $value_to_return = 'link_obj', $link_markup = '') {
        $url_object = Url::fromRoute($route_name, []);
        $url_object->setOptions(array('query' => $search_query));
        $link_object = Link::fromTextAndUrl($link_markup, $url_object);

        switch ($value_to_return) {
            case 'url_obj':
                return $url_object;
            break;

            case 'link_string':
                return $link_object->toString();
            break;

            case 'link_obj':
                return $link_object;
            break;

            default:
            break;
        }
    }

    /**
     * Convert HEX to Rgba.
     *
     * @param string $color
     *   Color hex code.
     * @param float $opacity
     *   Opacity value.
     *
     * @return string
     */
    public function hex2rgba($color, $opacity) {
        [$r, $g, $b] = sscanf($color, "#%02x%02x%02x");
        $output = "rgba($r, $g, $b, $opacity)";

        return $output;
    }

    // ---------- Autre exemple : https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php ---------- //
    /**
     * Increases or decreases the brightness of a color by a percentage of the current brightness.
     *
     * @param   string  $hexCode        Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
     * @param   float   $adjustPercent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
     *
     * @return  string
     *
     * @author  maliayas
     */
    public function adjustBrightness($hexCode, $adjustPercent) {
        $hexCode = ltrim($hexCode, '#');

        if (strlen($hexCode) == 3) {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }

        $hexCode = array_map('hexdec', str_split($hexCode, 2));

        foreach ($hexCode as & $color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);

            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }

        return '#' . implode($hexCode);
    }

    // ---------- SEO : Get "My favorites" Page Meta ---------- //
    public function getMyFavoritesMetatags() {
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $page_title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());

      $config = \Drupal::config('sorbonne_tv.settings');
      $favoritesConf = $config->get('sorbonne_tv.settings.favorites');
      $page_intro = (isset($favoritesConf['intro']) ? $favoritesConf['intro'] : '');

      $favorites_ogimg_med = FALSE;
      if(isset($favoritesConf['share_image'])) {
          $favorites_ogimg_med = Media::load($favoritesConf['share_image']);
      }

      $favoritesMetas = [];

      // Twitter Card
      $twitter_card = [
        '#tag' => 'meta',
          '#attributes' => [
            'name' => 'twitter:card',
            'content' => 'summary_large_image',
          ],
      ];
      $twitter_card_widget = [
        '#tag' => 'meta',
          '#attributes' => [
            'name' => 'twitter:widgets:new-embed-design',
            'content' => 'on',
          ],
      ];
      $favoritesMetas[] = [$twitter_card, 'twitter:card'];

      // OG url
      // Get the current page URL.
      $current_path = \Drupal::service('path.current')->getPath();
  
      $og_url = [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:url',
          'content' => $host . $current_path,
        ],
      ];
      $favoritesMetas[] = [$og_url, 'og_url'];

      // OG img
      if($favorites_ogimg_med) {
        if(isset($favorites_ogimg_med->field_media_image->entity)) {
          $favorites_ogimg = $favorites_ogimg_med->field_media_image->entity;
  
          $favorites_ogimg_uri = $favorites_ogimg->getFileUri();
          $favorites_ogimg_url = ImageStyle::load('sorbonne_tv_og_image')->buildUrl($favorites_ogimg_uri);
          $favorites_twitterimg_url = ImageStyle::load('sorbonne_tv_og_twitter_card')->buildUrl($favorites_ogimg_uri);
  
          $og_image = [
            '#tag' => 'meta',
            '#attributes' => [
              'property' => 'og:image',
              'content' => $host . $favorites_ogimg_url,
            ],
          ];
          $twitter_image = [
            '#tag' => 'meta',
            '#attributes' => [
              'property' => 'twitter:image',
              'content' => $favorites_twitterimg_url,
            ],
          ];
  
          $favoritesMetas[] = [$og_image, 'og_image'];
          $favoritesMetas[] = [$twitter_image, 'twitter:image'];
        }
      }

      // OG title
      $og_title = [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:title',
          'content' => $page_title
        ],
      ];
      $og_twitter_title = [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'twitter:title',
          'content' => $page_title
        ],
      ];
      $favoritesMetas[] = [$og_title, 'og:title'];
      $favoritesMetas[] = [$og_twitter_title, 'twitter:title'];
  
      // OG Descr
      $meta_descr = [
          '#tag' => 'meta',
          '#attributes' => [
              'name' => 'description',
              'content' => $page_intro,
          ],
      ];
      $og_description = [
          '#tag' => 'meta',
          '#attributes' => [
              'property' => 'og:description',
              'content' => $page_intro,
          ],
      ];
      $og_twitter_description = [
          '#tag' => 'meta',
          '#attributes' => [
              'property' => 'twitter:description',
              'content' => $page_intro,
          ],
      ];
      $favoritesMetas[] = [$meta_descr, 'description'];
      $favoritesMetas[] = [$og_description, 'og:description'];
      $favoritesMetas[] = [$og_twitter_description, 'twitter:description'];
      
      return $favoritesMetas;
    }
    
    // ---------- SEO : Get Programs Page Meta ---------- //
    public function getProgramsMetatags() {
        $host = \Drupal::request()->getSchemeAndHttpHost();
        $request = \Drupal::request();
        $route_match = \Drupal::routeMatch();
        $page_title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
        
        $config = \Drupal::config('sorbonne_tv.settings');
        $programsConf = $config->get('sorbonne_tv.settings.programs');
        $page_intro = (isset($programsConf['intro']) ? $programsConf['intro'] : '');

        $program_ogimg_med = FALSE;
        if(isset($programsConf['share_image'])) {
            $program_ogimg_med = Media::load($programsConf['share_image']);
        }

        $programMetas = [];
    
        // Twitter Card
        $twitter_card = [
        '#tag' => 'meta',
          '#attributes' => [
            'name' => 'twitter:card',
            'content' => 'summary_large_image',
          ],
        ];
        $twitter_card_widget = [
        '#tag' => 'meta',
          '#attributes' => [
            'name' => 'twitter:widgets:new-embed-design',
            'content' => 'on',
          ],
        ];
        $programMetas[] = [$twitter_card, 'twitter:card'];
    
        // OG url
        // Get the current page URL.
        $current_path = \Drupal::service('path.current')->getPath();
    
        $og_url = [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:url',
            'content' => $host . $current_path,
          ],
        ];
        $programMetas[] = [$og_url, 'og_url'];

        // OG img
        if($program_ogimg_med) {
          if(isset($program_ogimg_med->field_media_image->entity)) {
            $program_ogimg = $program_ogimg_med->field_media_image->entity;
    
            $program_ogimg_uri = $program_ogimg->getFileUri();
            //$program_ogimg_url = $program_ogimg->createFileUrl();
            $program_ogimg_url = ImageStyle::load('sorbonne_tv_og_image')->buildUrl($program_ogimg_uri);
            $program_twitterimg_url = ImageStyle::load('sorbonne_tv_og_twitter_card')->buildUrl($program_ogimg_uri);
    
            $og_image = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image',
                'content' => $host . $program_ogimg_url,
              ],
            ];
            $twitter_image = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'twitter:image',
                'content' => $program_twitterimg_url,
              ],
            ];
    
            $programMetas[] = [$og_image, 'og_image'];
            $programMetas[] = [$twitter_image, 'twitter:image'];
          }
        }
    
        // OG title
        $og_title = [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:title',
            'content' => $page_title
          ],
        ];
        $og_twitter_title = [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'twitter:title',
            'content' => $page_title
          ],
        ];
        $programMetas[] = [$og_title, 'og:title'];
        $programMetas[] = [$og_twitter_title, 'twitter:title'];
    
        // OG Descr
        $meta_descr = [
            '#tag' => 'meta',
            '#attributes' => [
                'name' => 'description',
                'content' => $page_intro,
            ],
        ];
        $og_description = [
            '#tag' => 'meta',
            '#attributes' => [
                'property' => 'og:description',
                'content' => $page_intro,
            ],
        ];
        $og_twitter_description = [
            '#tag' => 'meta',
            '#attributes' => [
                'property' => 'twitter:description',
                'content' => $page_intro,
            ],
        ];
        $programMetas[] = [$meta_descr, 'description'];
        $programMetas[] = [$og_description, 'og:description'];
        $programMetas[] = [$og_twitter_description, 'twitter:description'];
        
        return $programMetas;
      }

}
