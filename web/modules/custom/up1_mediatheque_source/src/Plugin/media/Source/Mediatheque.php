<?php
/**
 * Created by PhpStorm.
 * User: SLA11167
 * Date: 12/04/2019
 * Time: 11:00
 */

namespace Drupal\up1_mediatheque_source\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;


/**
 *
 * @MediaSource(
 *   id = "mediatheque",
 *   label = @Translation("Médiatheque"),
 *   description = @Translation("Vidéos provenant de la médiathèque Paris 1"),
 *   allowed_field_types = {
 *     "link", "string", "string_long"
 *   },
 *   default_thumbnail_filename = "video.png"
 * )
 */
class Mediatheque extends MediaSourceBase
{

    /**
     * {@inheritdoc}
     */
    public function getMetadataAttributes() {
        return NULL;
    }

    public function getMetadata(MediaInterface $media, $name) {

        $source_field = $this->getSourceFieldDefinition($media->bundle->entity)->getName();
        if ($media->hasField($source_field)) {
            // verifier qu'on a bien "mediatmediatheque.univ-paris1.fr/video/" dans l'URL
            return parent::getMetadata($media, $name);
        }
        return NULL;
    }


}