<?php

namespace Drupal\sorbonne_tv\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;


class SorbonneTvSyncMultimediaQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create (ContainerInterface $container , array $configuration , $plugin_id , $plugin_definition)
    {
        return new static( $configuration , $plugin_id , $plugin_definition );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem ($item)
    {

        /*
         * Delete node si pas dans l'API
         */

        if(isset($item->delete) && $item->delete == 'TRUE' ){

            $ids = \Drupal::entityQuery('node')
                ->condition('type', 'page_sorbonne_tv')
                ->condition('field_sorb_tv_type', 'video')
                ->condition('field_api_sync', 0)
                ->accessCheck(FALSE)
                ->execute();

            $nodes = Node::loadMultiple($ids);

            if ($nodes) {
                foreach ($nodes as $node) {
                    $node->delete();
                }
            }

        }else {


            /*
            * Mapping API vers DRUPAL
            */

            $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page_sorbonne_tv');

            $sync = 1;
            $type = 'video';
            $id = $item->video['id'];
            $title = $item->video['title'];
            $description = $item->video['description'] ?? null;
            $video = $item->video['video'] ?? null;
            $slug = $item->video['slug'] ?? null;

            // Langue
            $main_lang = $item->video['main_lang'] ?? null;
            $lang_allowed_options = options_allowed_values($field_definitions['field_sorb_tv_langue']->getFieldStorageDefinition());
            $list_lang = isset($lang_allowed_options[$main_lang]) ? $main_lang : NULL;

            // Durée
            $duration = (int)$item->video['duration'] ?? null;
            $ranges = [];
            if (isset($field_definitions['field_sorbonne_tv_time_lapse'])) {
                $allowed_options = options_allowed_values($field_definitions['field_sorbonne_tv_time_lapse']->getFieldStorageDefinition());
                $minute_duration = $duration / 60; // comparaison en minutes
                foreach($allowed_options as $key => $option) {
                    if ($minute_duration < $key) {
                        $ranges[] = $key;
                    }
                }
                if ($minute_duration > 60) {
                    $ranges[] = 60;
                }
            }

            $date_added = $item->video['date_added'] ?? null; // format : 2024-05-13T13:01:20+02:00
            $date_time = strtotime($date_added);
            if($date_added) {
                //$date_annee = substr($date_added, 0, 10);
                $date_annee = date('Y', $date_time);
            }else{
                $date_annee = null;
            }

            if($item->video['thumbnail']) {
                $var_thumbnail = str_replace('https://', '', $item->video['thumbnail']);
                $var_thumbnail = explode("/", $var_thumbnail);
                $imageApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('images/' . $var_thumbnail[3]);
                $image = $imageApi['file'] ?? null;
            }else{
                $image = null;
            }

            /* if($item->video['owner']) {
                $var_owner = str_replace('https://', '', $item->video['owner']);
                $var_owner = explode("/", $var_owner);
                $ownerApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('users/' . $var_owner[3]);
                $first_name = $ownerApi['first_name'] ?? null;
                $username = $ownerApi['username'] ?? null;
                $owner = $first_name . ' ' . $username;
                $owner = ucwords($owner);
            }else{
                $owner = null;
            }


            if(!empty($item->video['additional_owners'])) {
                foreach ($item->video['additional_owners'] as $owner_other) {
                    $var_owner_other = str_replace('https://', '', $owner_other);
                    $var_owner_other = explode("/", $var_owner_other);
                    $owner_otherApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('users/' . $var_owner_other[3]);
                    $first_name = $owner_otherApi['first_name'] ?? null;
                    $username = $owner_otherApi['username'] ?? null;
                    $owner_array[] = ucwords($first_name . ' ' . $username);
                }
            }else{
                $owner_array = [];
            } */
            $director = '';
            $contributors = [];
            $apiContributors = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getVideoContributors($id);
            if(!empty($apiContributors)) {
              foreach($apiContributors as $contributorApi) {
                $name = '';
                $split = explode(', ', $contributorApi['name']);
                if (isset($split[1])) { // prénom
                  $name = $split[1] . ' ' . $split[0];
                }
                else {
                  $name = $split[0];
                }

                if (isset($contributorApi['role']) && $contributorApi['role'] == 'director') {
                  $director = $name;
                }
                else {
                  $contributors[] = $name;
                }
              }
            }

            $tags = $item->video['tags'] ?? null;

            if($item->video['type']) {
                $var_type = str_replace('https://', '', $item->video['type']);
                $var_type = explode("/", $var_type);
                $typeApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('types/' . $var_type[3]);

                $taxo_type_id = $typeApi['id'] ?? null;
                $taxo_type_title = $typeApi['title'] ?? null;
                $taxo_type_description = $typeApi['description'] ?? null;
                $taxo_type_icon = $typeApi['icon'] ?? null;

            }else{

                $taxo_type_id = null;
                $taxo_type_title = null;
                $taxo_type_description = null;
                $taxo_type_icon = null;

            }


            if(!empty($item->video['discipline'])) {
                $i = 0;
                $discipline_array = [];
                foreach ($item->video['discipline'] as $discipline) {
                    $var_discipline = str_replace('https://', '', $discipline);
                    $var_discipline = explode("/", $var_discipline);
                    $disciplineApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('discipline/' . $var_discipline[3]);
                    $discipline_array[$i]['id'] = $disciplineApi['id'] ?? null;
                    $discipline_array[$i]['title'] = $disciplineApi['title'] ?? null;
                    $discipline_array[$i]['description'] = $disciplineApi['description'] ?? null;
                    $discipline_array[$i]['icon'] = $disciplineApi['icon'] ?? null;
                    $i++;
                }
                $disciplines = $discipline_array;
            }else{
                $disciplines = [];
            }




            /*
             * ADD TAXO TAGS
             */

            $taxo_tags_ids = [];

            $publish = TRUE;
            if($tags) {

                $tags = str_replace('\/', '', $tags);
                $tags = str_replace('" "', '~', $tags);
                $tags = str_replace('"', '~', $tags);
                $tag_array = explode("~", $tags);

                $excludedTags = [
                    'ne pas diffuser',
                ];
                if(!empty($tag_array)) {
                    foreach ($tag_array as $tag) {

                        if ($tag != ''){

                            $taxo_tags_id = \Drupal::entityQuery('taxonomy_term')
                                ->condition('vid', 'tag_sorbonne_tv')
                                ->condition('name', $tag)
                                ->accessCheck(FALSE)
                                ->execute();

                            if (empty($taxo_tags_id)) {

                                $taxo_tags = Term::create([
                                    'vid' => 'tag_sorbonne_tv',
                                    'name' => $tag,
                                    'site_id' => 126,
                                ]);


                            } else {

                                $taxo_tags_id = reset($taxo_tags_id);
                                $taxo_tags = Term::load($taxo_tags_id);
                                $taxo_tags->set('site_id', 126);

                            }
                            $taxo_tags->save();
                            $taxo_tags_ids[] = $taxo_tags->id();

                            if (in_array($tag, $excludedTags)) {
                                $publish = FALSE;
                            };

                        }

                    }
                }
            }


            /*
             * ADD TAXO TYPE
             */
            $taxo_type_ids = [];
            if ($taxo_type_id) {

                $query_taxo_type = \Drupal::entityQuery('taxonomy_term')
                    ->condition('vid', 'type_sorbonne_tv')
                    ->condition('field_id_type', $taxo_type_id)
                    ->accessCheck(FALSE)
                    ->execute();

                if (empty($query_taxo_type)) {

                    $taxo_type = Term::create([
                        'vid' => 'type_sorbonne_tv',
                        'name' => $taxo_type_title,
                        'description' => $taxo_type_description,
                        'field_id_type' => $taxo_type_id,
                        'field_icon_type' => $taxo_type_icon,
                        'site_id' => 126,
                    ]);
                    $taxo_type->save();

                } else {

                    $query_taxo_type = reset($query_taxo_type);
                    $taxo_type = Term::load($query_taxo_type);

                    if ($taxo_type) {
                        $taxo_type->name->setValue($taxo_type_title);
                        $taxo_type->description->setValue($taxo_type_description);
                        $taxo_type->field_icon_type->setValue($taxo_type_icon);
                        $taxo_type->set('site_id', 126);
                        $taxo_type->save();
                    }

                }

                $taxo_type_id = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['field_id_type' => $taxo_type_id, 'vid' => 'type_sorbonne_tv']);
                $taxo_type_id = reset($taxo_type_id);
                $taxo_type_id = $taxo_type_id->id();
                $taxo_type_ids[] = $taxo_type_id;

            }


            /*
             * ADD TAXO DISCIPLINE
             */

            $taxo_discipline_ids = [];
            if (!empty($disciplines)) {


                foreach ($disciplines as $discipline) {

                    $query_taxo_discipline = \Drupal::entityQuery('taxonomy_term')
                        ->condition('vid', 'discipline_sorbonne_tv')
                        ->condition('field_id_discipline', $discipline['id'])
                        ->accessCheck(FALSE)
                        ->execute();

                    if (empty($query_taxo_discipline)) {

                        $taxo_discipline = Term::create([
                            'vid' => 'discipline_sorbonne_tv',
                            'name' => $discipline['title'],
                            'description' => $discipline['description'],
                            'field_id_discipline' => $discipline['id'],
                            'field_icon_discipline' => $discipline['icon'],
                            'site_id' => 126,
                        ]);
                        $taxo_discipline->save();

                    } else {

                        $query_taxo_discipline = reset($query_taxo_discipline);
                        $taxo_discipline = Term::load($query_taxo_discipline);

                        if ($taxo_discipline) {

                            $taxo_discipline->name->setValue($discipline['title']);
                            $taxo_discipline->description->setValue($discipline['description']);
                            $taxo_discipline->field_icon_discipline->setValue($discipline['icon']);
                            $taxo_discipline->set('site_id', 126);
                            $taxo_discipline->save();

                        }
                    }

                    $taxo_discipline_id = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['field_id_discipline' => $discipline['id'], 'vid' => 'discipline_sorbonne_tv']);
                    $taxo_discipline_id = reset($taxo_discipline_id);
                    $taxo_discipline_id = $taxo_discipline_id->id();
                    $taxo_discipline_ids[] = $taxo_discipline_id;

                }

            }


            /*
             * DOC DE LA VIDEO
             */

            $tracksApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getTracks($id);
            $docApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getDocs($id);

            $file_vtt = [];
            $file_transcription = [];
            $file_audio = [];

            if(!empty($tracksApi)){

                foreach ($tracksApi as $track) {

                    if($track['src']){

                        $var_track = str_replace('https://', '', $track['src']);
                        $var_track = explode("/", $var_track);
                        $fileApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('files/' . $var_track[3]);

                        if(!empty($fileApi)){

                            if($fileApi['file']){

                                $file_parts = pathinfo($fileApi['file']);
                                $file_ext = $file_parts['extension'];


                                if(strtolower($file_ext) == 'vtt'){

                                    $file_vtt[] = $track['kind'] . ' ~ ' . $track['lang'] . ' ~ ' . $fileApi['file'];
                                    //\Drupal::logger('sorbonne_tv_VIDEO:ID')->notice($id);
                                    //\Drupal::logger('sorbonne_tv_DOC:FILE')->notice('<pre>' . print_r($file_vtt, TRUE) . '</pre>');

                                }

                            }

                        }

                    }

                }

            }

            if(!empty($docApi)){

                foreach ($docApi as $doc) {

                    if($doc['document']){

                        $var_doc = str_replace('https://', '', $doc['document']);
                        $var_doc = explode("/", $var_doc);
                        $fileApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('files/' . $var_doc[3]);

                        if(!empty($fileApi)){

                            if($fileApi['file']){

                                $file_parts = pathinfo($fileApi['file']);
                                $file_ext = $file_parts['extension'];


                                if(strtolower($file_ext) == 'mp3'){
                                    $file_audio[] = $fileApi['file'];
                                    //\Drupal::logger('sorbonne_tv_VIDEO:ID')->notice($id);
                                    //\Drupal::logger('sorbonne_tv_DOC:FILE')->notice('<pre>' . print_r($file_audio, TRUE) . '</pre>');


                                }

                                if(str_contains(strtolower($fileApi['name']), 'transcription') && strtolower($file_ext) == 'doc' ){

                                    $file_transcription[] = $fileApi['file'];
                                    //\Drupal::logger('sorbonne_tv_VIDEO:ID')->notice($id);
                                    //\Drupal::logger('sorbonne_tv_DOC:FILE')->notice('<pre>' . print_r($file_transcription, TRUE) . '</pre>');



                                }

                            }

                        }

                    }

                }

            }

            /*
             * Node collection pour les vidéos
             */

            // ajouter toutes les discipline des video pour la collection

            $collection_video = [];
            $thumbnail_collection_medias = [];
            $collection_array = [];

            if(!empty($item->video['theme'])) {
                $i = 0;
                foreach ($item->video['theme'] as $theme) {
                    $var_theme = str_replace('https://', '', $theme);
                    $var_theme = explode("/", $var_theme);
                    $themeApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('themes/' . $var_theme[3]);

                    $collection_array[$i]['id'] = $themeApi['id'];
                    $collection_array[$i]['title'] = $themeApi['title'];
                    $collection_array[$i]['description'] = $themeApi['description'] ?? null;
                    $collection_array[$i]['headband'] = $themeApi['headband'] ?? null;

                    if(isset($themeApi['channel']) && $themeApi['channel'] != '' ){
                        $var_theme_channel = str_replace('https://', '', $themeApi['channel']);
                        $var_theme_channel = explode("/", $var_theme_channel);
                        $collection_array[$i]['channel'] = $var_theme_channel[3];

                    }else{
                        $collection_array[$i]['channel'] = null;
                    }

                    $i++;
                }
            }



            if(!empty($collection_array)) {

                foreach ($collection_array as $collection) {

                    if($collection['channel'] == 81) {

                        //\Drupal::logger('channel')->notice("<pre>". print_r($collection, true)."</pre>");
                        $node_collection = NULL;

                        $nid_collection = \Drupal::entityQuery('node')
                            ->condition('type', 'page_sorbonne_tv')
                            ->condition('field_sorb_tv_type', 'collection')
                            ->condition('field_id_video', $collection['id'])
                            ->accessCheck(FALSE)
                            ->execute();

                        if (empty($nid_collection)) {

                            $node_collection = Node::create([
                                'type' => 'page_sorbonne_tv',
                                'title' => $collection['title'],
                                'field_sorb_tv_type' => 'collection',
                                'field_id_video' => $collection['id'],
                                'site_id' => 126,
                            ]);
                            $op = 'CREATE';

                        } else {
                            $op = 'UPDATE';
                            $nid_collection = reset($nid_collection);
                            $node_collection = Node::load($nid_collection);
                        }



                        if ($collection['headband']) {


                            $var_collection_img = str_replace('https://', '', $collection['headband']);
                            $var_collection_img = explode("/", $var_collection_img);
                            $collectionImgApi = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('images/' . $var_collection_img[3]);
                            $image_collection = $collectionImgApi['file'];


                            $directory_collection = 'public://medias/sorbonne-tv/thumbnail/collection/';
                            $file_system_collection = \Drupal::service('file_system');
                            $file_system_collection->prepareDirectory($directory_collection, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
                            $file_repository_collection = \Drupal::service('file.repository');

                            $var_image_collection = str_replace('https://', '', $image_collection);
                            $var_image_collection = explode("/", $var_image_collection);
                            $filename_collection = end($var_image_collection);
                            $newFilePath_collection = $directory_collection . $filename_collection;

                            $image_data_collection = file_get_contents($image_collection);
                            $thumbnail_collection = $file_repository_collection->writeData($image_data_collection, $newFilePath_collection, FileSystemInterface::EXISTS_REPLACE);

                            $medias_collection = $node_collection->field_media->referencedEntities();

                            if (empty($medias_collection)) {

                                $thumbnail_media_collection = Media::create([
                                    'name' => $filename_collection,
                                    'bundle' => 'image',
                                    'uid' => 1,
                                    'langcode' => 'fr',
                                    'status' => 1,
                                    'field_media_image' => [
                                        'target_id' => $thumbnail_collection->id(),
                                    ],
                                    'site_id' => 126,
                                ]);

                            } else {

                                $thumbnail_media_collection = reset($medias_collection);
                                $thumbnail_media_collection->set('field_media_image', ['target_id' => $thumbnail_collection->id()]);
                                $thumbnail_media_collection->set('site_id', 126);

                            }

                            $thumbnail_media_collection->save();
                            $thumbnail_collection_medias [] = $thumbnail_media_collection->id();

                        }


                        if ($node_collection) {

                            $field_disciplines = $node_collection->get('field_discipline')->getValue();
                            foreach($field_disciplines as $value) {
                              $taxo_discipline_ids[] = $value['target_id'];
                            }
                            $taxo_discipline_ids = array_unique($taxo_discipline_ids);

                            $node_collection->setTitle($collection['title']);
                            $node_collection->set('body', array(
                                'value' => $collection['description'],
                                'format' => 'full_html',
                            ));
                            $node_collection->set('field_media', $thumbnail_collection_medias);

                            $share_medias = $node_collection->field_sorb_tv_share_image->referencedEntities();
                            if (empty($share_medias)) {
                              $node_collection->set('field_sorb_tv_share_image', $thumbnail_collection_medias);
                            }
                            $node_collection->set('field_discipline', $taxo_discipline_ids);
                            $node_collection->set('site_id', 126);
                            $node_collection->changed = time();
                            $node_collection->save();



                            \Drupal::logger('sorbonne_tv_syncMultimedia::processItem')->notice('@op collection @id', ['@op' => $op, '@id' => $node_collection->id()]);

                            $collection_video[] = $node_collection->id();

                        }
                    }
                }
            }


            $nid = \Drupal::entityQuery('node')
                ->condition('type', 'page_sorbonne_tv')
                ->condition('field_sorb_tv_type', 'video')
                ->condition('field_id_video', $id)
                ->accessCheck(FALSE)
                ->execute();

            if (empty($nid)) {

                $node = Node::create([
                    'type' => 'page_sorbonne_tv',
                    'title' => $title,
                    'field_sorb_tv_type' => $type,
                    'field_id_video' => $id,
                    'site_id', 126,
                ]);

            } else {

                $nid = reset($nid);
                $node = Node::load($nid);
                $node->set('site_id', 126);

            }

            if($image) {

                $directory = 'public://medias/sorbonne-tv/thumbnail/video/';
                $file_system = \Drupal::service('file_system');
                $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
                $file_repository = \Drupal::service('file.repository');

                $var_image = str_replace('https://', '', $image);
                $var_image = explode("/", $var_image);
                $filename = end($var_image);
                $newFilePath = $directory . $filename;

                $image_data = file_get_contents($image);
                $thumbnail = $file_repository->writeData($image_data, $newFilePath, FileSystemInterface::EXISTS_REPLACE);

                $medias = $node->field_media->referencedEntities();

                if(empty($medias)) {

                    $thumbnail_media = Media::create([
                        'name' => $filename,
                        'bundle' => 'image',
                        'uid' => 1,
                        'langcode' => 'fr',
                        'status' => 1,
                        'field_media_image' => [
                            'target_id' => $thumbnail->id(),
                        ],
                        'site_id', 126,
                    ]);

                }else{

                    $thumbnail_media = reset($medias);
                    $thumbnail_media->set('field_media_image', ['target_id' => $thumbnail->id()]);
                    $thumbnail_media->set('site_id', 126);

                }

                $thumbnail_media->save();
                $thumbnail_medias [] = $thumbnail_media->id();

            }else{

                $thumbnail_medias = [];

            }


            $taxo_discipline_ids = array_unique($taxo_discipline_ids);

            if ($node) {

              $share_medias = $node->field_sorb_tv_share_image->referencedEntities();
              if (empty($share_medias)) {
                $share_medias = $thumbnail_medias;
              }
              $fields = [
                'field_api_sync' => $sync,
                'field_url_video' => $video,
                'field_sorb_tv_video_slug' => $slug,
                'field_media' => $thumbnail_medias,
                'field_sorb_tv_share_image' => $share_medias,
                'field_langue' => $main_lang,
                'field_sorb_tv_langue' => $list_lang,
                'field_duree' => $duration,
                'field_sorbonne_tv_time_lapse' => $ranges,
                'field_date_maj' => $date_added,
                'field_annee_depot' => $date_annee,
                'field_sorb_tv_date_depot' => date('Y-m-d\TH:i:s', $date_time),
                'field_sorb_tv_video_directedby' => $director,
                'field_sorb_tv_video_with' => $contributors,
                'field_sorb_tv_video_vtt' => $file_vtt,
                'field_sorb_tv_video_trans' => $file_transcription,
                'field_sorb_tv_video_audio' => $file_audio,
                'field_video_type' => $taxo_type_ids,
                'field_discipline' => $taxo_discipline_ids,
                'field_tag_video' => $taxo_tags_ids,
                'field_collections' => $collection_video,
              ];


                $node->setTitle($title);
                $node->set('body', array(
                    'value' => $description,
                    'format' => 'full_html',
                ));

                foreach ($fields as $fieldname => $fieldvalue) {
                    $node->set($fieldname, $fieldvalue);
                }

                $node->set('status', $publish);

                $node->save();
                \Drupal::logger('sorbonnetv::sync')->notice("Sync video @video_id > node @nid", ['@video_id' => $id, '@nid' => $node->id()]);
            }

        }
    }
}
