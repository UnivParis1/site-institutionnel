<?php

namespace Drupal\micro_publications\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MicroPublicationsBlock' block.
 *
 * @Block(
 *   id = "micro_publications_block",
 *   admin_label = @Translation("Micro publications block"),
 *   )
 */
class MicroPublicationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build()  {
    $negotiator = \Drupal::service('micro_site.negotiator');
    if (!empty($negotiator->getActiveSite())) {
      $site_id = $negotiator->getActiveId();
      $site_storage = \Drupal::entityTypeManager()->getStorage('site');
      $site = $site_storage->load($site_id);

      $config = \Drupal::config('micro_publications.settings');
      $request = $config->get('hostname') . '?wt='. $config->get('wt');
      $params = [
        'q'     => 'labStructName_t' . $site->get('field_labstructname_t')->getValue(),
        'fl'    => $site->get('field_request_fields')->getValue(),
        'sort'  => 'producedDate_tdate%20desc',
        'rows'  => 30,
      ];
      foreach ($site->get('field_doctype') as $key => $value) {
        $list[] = $this->curl_request($request, $params + ['fq' => 'docType_s:' . $value->value]);
      }
      \Drupal::logger('micro_publications')->info(print_r($list, TRUE));
    }


    $build['micro_publications'] = [
      '#theme' => 'micro_publications',
      '#publications' => $list,
    ];

    return $build;
  }

  private function curl_request($url, $params)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '&' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $data = json_decode(curl_exec($ch), TRUE);

    curl_close($ch);

    return $data;
  }
}
