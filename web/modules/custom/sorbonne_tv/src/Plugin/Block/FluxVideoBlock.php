<?php

namespace Drupal\sorbonne_tv\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FluxVideoBlock' block.
 *
 * @Block(
 *  id = "flux_video_block",
 *  admin_label = @Translation("Flux Video Block"),
 * )
 */
class FluxVideoBlock extends BlockBase
{

    /**
     * {@inheritdoc}
     */
    public function build()
    {

        $config = \Drupal::config('sorbonne_tv.settings');
        $api_flux_video = $config->get('sorbonne_tv.settings.api_flux_video');

        $build['flux_video'] = [
            '#theme' => 'sorbonne_tv_flux_video',
            '#url' => $api_flux_video,
        ];

        return $build;

    }

}