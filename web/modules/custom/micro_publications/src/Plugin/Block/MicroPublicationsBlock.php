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
      $fl =  $site->get('field_request_fields')->getValue();
      $labStructName = $site->get('field_labstructname_t')->getValue();
      $params = [
        'q'     => 'labStructName_t' . $labStructName[0]['value'],
        'fl'    => $fl[0]['value'],
        'sort'  => 'producedDate_tdate desc',
        'rows'  => 30,
      ];
      foreach ($site->get('field_doctype')->getValue() as $key => $doctype) {
        $params['fq'] = 'docType_s:' . $doctype['value'];
        $data =$this->curl_request($request, $params);
        if (!empty($data)) {
          $list = [];
          $list[$doctype['value']] = $this->format_data($data);
        }
      }
    }


    $build['micro_publications'] = [
      '#theme' => 'micro_publications',
      '#publications' => $list,
      '#types' =>$this->getDocType(),
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

  private function format_data($curl_request)  {
    $data = [];

    foreach ($curl_request['response']['docs'] as $key => $doc) {
      $data[$key] = [
        'title' => $doc['title_s'][0],
        'other_titles' => (count($doc['title_s']) > 1) ? implode(', ', $doc['title_s']) : '',
        'authors' => implode(', ', $doc['authFullName_s']),
        'docType' => $this->getDocType($doc['docType_s']),
        'uri' => $doc['uri_s'],
      ];
    }

    return $data;
  }

  /**
   * @param $code
   * @return string| array[]
   */
  private function getDocType($code = NULL) {
     $types = [
        'ART' => 'Article dans une revue',
        'COMM' => 'Communication dans un congrès',
        'POSTER' => 'Poster de conférence',
        'PROCEEDINGS' => 'Proceedings/Recueil des communications',
        'ISSUE' => 'N°spécial de revue/special issue',
        'OUV' => 'Ouvrages',
        'COUV' => 'Chapitre d\'ouvrage',
        'BLOG' => 'Article de blog scientifique',
        'NOTICE' => 'Notice d’encyclopédie ou de dictionnaire',
        'TRAD' => 'Traduction',
        'PATENT' => 'Brevet',
        'OTHER' => 'Autre publication scientifique',
        'UNDEFINED' => 'Pré-publication, Document de travail',
        'REPORT' => 'Rapport',
        'THESE' => 'Thèse',
        'HDR' => 'HDR',
        'LECTURE' => 'Cours',
        'MEM' => 'Mémoire d\'étudiant',
        'IMG' => 'Image',
        'VIDEO' => 'Vidéo',
        'SON' => 'Son',
        'MAP' => 'Carte',
        'SOFTWARE' => 'Logiciel',
        'PRESCONF' => 'Document associé à des manifestations scientifiques',
        'CREPORT' => 'Chapitre de rapport',
        'ETABTHESE' => 'Thèse d\'établissement',
        'MEMLIC' => 'typdoc_MEMLIC',
        'NOTE' => 'Note de lecture',
        'OTHERREPORT' => 'Autre rapport, séminaire, workshop',
        'REPACT' => 'Rapport d\'activité',
        'SYNTHESE' => 'Notes de synthèse',
      ];
     !empty($code) ? $value = $types[$code] : $value = $types;

     return $value;
  }
}
