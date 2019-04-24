<?php
/**
 * Created by PhpStorm.
 * User: SLA11167
 * Date: 12/04/2019
 * Time: 16:49
 */

namespace Drupal\up1_mediatheque_source\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field formatter pour les médias de type Mediatheque.
 *
 * @FieldFormatter(
 *   id = "mediatheque_embed",
 *   label = @Translation("Mediatheque intégré"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class MediathequeEmbedFormatter extends FormatterBase
{

    /**
     * Builds a renderable array for a field value.
     *
     * @param \Drupal\Core\Field\FieldItemListInterface $items
     *   The field values to be rendered.
     * @param string $langcode
     *   The language that should be used to render the field.
     *
     * @return array
     *   A renderable array for $items, as an array of child elements keyed by
     *   consecutive numeric indexes starting from 0.
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $element = [];

        foreach ($items as $delta => $item) {

            $url = $this->nettoyageUrl($item->uri);

            $element[$delta] = [
                '#theme' => 'media_mediatheque_video',
                '#url' => $url,
                '#title' => $item->title,
                '#attributes' => [
                    'class' => [],
                    'data-conversation' => 'none',
                    'lang' => $langcode,
                ],
            ];
        }

        return $element;
    }

    private function nettoyageUrl($uri){
      if (stristr($uri,'mediatheque.univ-paris1.fr/video/') === FALSE ){
        $uri = '';
      }
      else {
        if (substr($uri, 0, 6) == 'https:'){
          $uri = substr($uri, 6);
        }
      }
      return $uri;
    }
}