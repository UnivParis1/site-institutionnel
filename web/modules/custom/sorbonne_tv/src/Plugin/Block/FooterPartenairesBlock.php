<?php

namespace Drupal\sorbonne_tv\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Provides a 'FooterPartenairesBlock' block.
 *
 * @Block(
 *  id = "footer_partenaires_block",
 *  admin_label = @Translation("Footer partenaires block"),
 * )
 */
class FooterPartenairesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $logos = [];
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $currentSiteId = $negotiator->getActiveId();

      if (!empty($currentSiteId)) {
        $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
        $currentSite = $siteStorage->load($currentSiteId);

        $paragraph = $currentSite->get('field_footer_partenaires');
        $paragraph_id = $paragraph->getValue();

          foreach ($paragraph_id as $paragraph_item) {

              $paragraph_child = $paragraph_item["target_id"];
              $paragraph_entity = Paragraph::load($paragraph_child);

              $lien = $paragraph_entity->get('field_footer_partenaires_lien');
              $lien_id = $lien->getValue();
              $lien_entity = $lien_id[0]['uri'];
              $logos["$paragraph_child"]['lien'] = $lien_entity;


              $media = $paragraph_entity->get('field_media');
              $media_id = $media->getValue();
              $media_entity = Media::load($media_id[0]['target_id']);
              if($media_entity) {
                $file_id = $media_entity->getSource()->getSourceFieldValue($media_entity);
                $file = File::load($file_id);
                $logos["$paragraph_child"]['image'] = $file->createFileUrl();
              }

          }
      }
    }
    $build['footer_partenaires'] = [
      '#theme' => 'sorbonne_tv_footer_partenaires',
      '#logos' => $logos,
    ];

    return $build;
  }

}
