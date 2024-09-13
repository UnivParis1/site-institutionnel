<?php

namespace Drupal\sorbonne_tv\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * Class SorbonneTvTestController.
 */
class SorbonneTvTestController extends ControllerBase
{

    public function testPage()
    {

        $content = [];
        $programme = [];
        $videos = [];
        $video = [];
        $thumbnail = [];
        $type = [];
        $discipline = [];
        $api = NULL;

        $params = \Drupal::request()->query->all();

        if (isset($params['api'])) {
            $api = $params['api'];
        }

        if ($api == 'cron-mediatheque') {

            $videos = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getVideos();

            if (isset($params['video_id'])) {
                $video_id = $params['video_id'];

                $videoData = NULL;
                foreach($videos as $data) {
                    if ($data['id'] == $video_id) {
                        $videoData = $data;
                        break;
                    }
                }
                if ($videoData) {
                    $content[] = '<pre>' . print_r($videoData, TRUE) . '</pre>';

                    $queue = \Drupal::service('queue')->get('sorbonne_tv_sync_multimedia_queue');
                    $queueItem = new \stdClass();

                    $queueItem->video = $videoData;
                    $queue->createItem($queueItem);
                }
                else {
                    $content[] = 'Video ' . $video_id . ' not found';
                }
            }
            else {
                $content[] = '<pre>' . print_r($videos, TRUE) . '</pre>';
            }
        }

        if ($api == 'contributors') {
          if (isset($params['video_id'])) {
            $apiContributors = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getVideoContributors($params['video_id']);
            $content[] = '<pre>' . print_r($apiContributors, TRUE) . '</pre>';
          }
        }

        if ($api == 'mediatheque') {
            $videos['all'] = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('videos');
            $video['detail_'.$videos['all']['results'][0]['id']] = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('videos/' . $videos['all']['results'][0]['id'] );

            $var_thumbnail = $video['detail_'.$videos['all']['results'][0]['id']]['thumbnail'];
            $var_thumbnail = str_replace('https://', '', $var_thumbnail);
            $var_thumbnail = explode("/", $var_thumbnail);
            $thumbnail['thumbnail_'.$var_thumbnail[3]] = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('images/' . $var_thumbnail[3] );

            $var_type = $video['detail_'.$videos['all']['results'][0]['id']]['type'];
            $var_type = str_replace('https://', '', $var_type);
            $var_type = explode("/", $var_type);
            $type['types_'.$var_type[3]] = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('types/' . $var_type[3] );

            $var_discipline = $video['detail_'.$videos['all']['results'][0]['id']]['discipline'][0];
            $var_discipline = str_replace('https://', '', $var_discipline);
            $var_discipline = explode("/", $var_discipline);
            $discipline['discipline_'.$var_discipline[3]] = \Drupal::service('sorbonne_tv.api_mediatheque_service')->getValue('discipline/' . $var_discipline[3] );


            $content[] = '<pre>' . print_r($videos, TRUE) . '</pre>';
            $content[] = '<pre>' . print_r($video, TRUE) . '</pre>';
            $content[] = '<pre>' . print_r($thumbnail, TRUE) . '</pre>';
            $content[] = '<pre>' . print_r($type, TRUE) . '</pre>';
            $content[] = '<pre>' . print_r($discipline, TRUE) . '</pre>';
        }

        if ($api == 'visionnaire') {

            if (!isset($params['date'])) {
                $params['date'] = date('Y-m-d');
            }

            $programme[] = \Drupal::service('sorbonne_tv.api_visionnaire_service')->getProgramme('getCustomEPG.php', $params);
            $content[] = '<pre>' . print_r($programme, TRUE) . '</pre>';
        }


        $page = array(
            '#type' => 'markup',
            '#markup' => \Drupal\Core\Render\Markup::create(
                implode('<br />', $content)
            ),
        );

        return $page;

    }
}
