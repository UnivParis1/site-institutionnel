<?php

namespace Drupal\sorbonne_tv\Service;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class SorbonneTvVideosService {

    // ----- Donne le noeud Srbonne TV par idVideo et sous type ----- //
    public function getStvNodeByVideoId($video_id, $ss_type = NULL) {
        $stv_node = FALSE;

        $query = \Drupal::entityQuery('node')
        ->condition('type', 'page_sorbonne_tv')
        ->condition('field_id_video', $video_id)
        ->accessCheck(FALSE);

        if($ss_type) {
            $query->condition('field_sorb_tv_type', $ss_type);
        }

        $query->execute();

        $ids = $query->execute();
        if($ids) {
            $result_nodes = Node::loadMultiple($ids);

            foreach ($result_nodes as $result_node) {
                $stv_node = $result_node;

                break;
            }
        }

        return $stv_node;
    }


    // ----- Donne les disciplines du noeud video ----- //
    public function getVideoDisciplines($node) {
        $video_disciplines = [];

        if(isset($node->field_discipline->target_id)) {
            $disciplinesIds = array_column($node->field_discipline->getValue(), 'target_id');
  
            $disciplines = Term::loadMultiple($disciplinesIds);
            foreach($disciplines as $dis_k => $discipline) {
              $video_disciplines[$discipline->id()] = $discipline->getName();
            }
        }

        return $video_disciplines;
    }

    // ----- Donne les disciplines du noeud video Pour les filtres contextuels de vue ----- //
    public function getVideoDisciplinesContextualFiltersFormat($node) {
        $video_disciplines = '';

        if( $node_disciplines = $this->getVideoDisciplines($node) ) {
            foreach($node_disciplines as $nd_k => $discipline) {
                $video_disciplines .= ($video_disciplines ? '+' : '') . $nd_k;
            }
        }

        return $video_disciplines;
    }

    // ----- Donne la/les collections du noeud video ----- //
    public function getVideoCollects($node) {
        $video_collections = [];

        if(isset($node->field_collections->target_id)) {
          $collectionsIds = array_column($node->field_collections->getValue(), 'target_id');

          $collections = Node::loadMultiple($collectionsIds);
          foreach($collections as $coll_k => $collection) {
            $video_collections[$collection->id()] = $collection->getTitle();
          }
        }

        return $video_collections;
    }

    // ----- Donne les collections du noeud video Pour les filtres contextuels de vue ----- //
    public function getVideoCollectsContextualFiltersFormat($node) {
        $video_collections = '';

        if( $node_collects = $this->getVideoCollects($node) ) {
            foreach($node_collects as $coll_k => $collection) {
                $video_collections .= ($video_collections ? '+' : '') . $coll_k;
            }
        }

        return $video_collections;
    }

    // ----- Donne les articles les plus écoutés (top articles) ----- //
    public function getTopVideos($item_num) {
        $def_top_articles = [];
        $def_single_top_article = FALSE;
        $config = \Drupal::config('sorbonne_tv.settings');
        $programs = $config->get('sorbonne_tv.settings.programs');

        if($item_num == 'all') {
            /*
            if(isset($programs['top_articles'])) {
                foreach($programs['top_articles'] as $k => $val) {
                    if(isset($val['target_id'])) {
                        $def_top_articles[] = Node::load($val['target_id']);
                    }
                }
            }
            */
            if(isset($programs['top_articles_wrapper'])) {
                foreach($programs['top_articles_wrapper'] as $k => $val) {
                    if($val) {
                        if($top_article_node = Node::load($val)) { // Dans le cas où le noeud taggé est supprimé par la synchro
                            $def_top_articles[] = $top_article_node;
                        }
                    }
                }
            }

            return $def_top_articles;
        }
        else {
            if(isset($programs['top_articles_wrapper']['top_articles_'.$item_num])) {
                $def_single_top_article = Node::load($programs['top_articles_wrapper']['top_articles_'.$item_num]);
            }

            return $def_single_top_article;
        }
    }

    // ----- Donne les articles les plus écoutés (top articles) sous le format de liste numérotée ----- //
    public function getTopVideosNumberedListItems($nbitems) {
        $items = [];
        $top_articles = $this->getTopVideos('all');

        if($top_articles && !empty($top_articles)) {
            $entityType = 'node';
            $viewMode = 'sorbonne_tv_numbered_list';
            $storage = \Drupal::entityTypeManager()->getStorage($entityType);
            $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entityType);

            $ta_i = 1;
            foreach($top_articles as $ta_k => $top_article) {
                if($ta_i > $nbitems) {
                    break;
                }
                else {
                    $items[] = [
                        '#type' => 'container',
                        '#attributes' => [
                            'class' => ['field--item']
                        ],
                        // render node
                        'item_article_ct' => $viewBuilder->view($top_article, $viewMode),
                    ];

                    $ta_i++;
                }
            }
        }

        return $items;
    }

    // ----- (Pour les pages "mosaïque") Donne l'indication d'affichage du champ descriptif des vignettes de liste ----- //
    public function getItemsHiddenDescrVal($node) {
        $mosaic_hide_descr = FALSE;

        if($node->getType() == 'page_sorbonne_tv') {
            $ss_type = (isset($node->field_sorb_tv_type->value) ? $node->field_sorb_tv_type->value : FALSE);

            if($ss_type == 'mosaic') {
                $mosaic_hide_descr = (isset($node->field_sorb_tv_mosaic_hidedescr->value) ? $node->field_sorb_tv_mosaic_hidedescr->value : FALSE);
            }
        }

        return $mosaic_hide_descr;
    }

}
