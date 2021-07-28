<?php

namespace Drupal\micro_annuaire_sorbonne;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Class AnnuaireService.
 *
*/
class AnnuaireService implements AnnuaireServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */

  protected $entityTypeManager;

  /**
   * Constructs a AnnuaireService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param $siteId (int) ID du site dont on veut recuperer l'annuaire
   *
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserList($siteId) {
    $users = [];
    $filter = '';
    $config = \Drupal::config('micro_annuaire_sorbonne.annuaireconfig');

    if (!empty($siteId)) {


      $siteStorage = $this->entityTypeManager->getStorage('site');
      $currentSite = $siteStorage->load($siteId);

      if (!empty($currentSite->get('filtre')->value)) {
        $filter = $currentSite->get('filtre')->value;
      }
    }
    else {
      $filter = $config->get('filtre_site_principal');
    }

    $ws = $config->get('url_ws');
    $searchUser = $ws . $filter;

    $params = [
      'attrs' => 'sn,givenName,labeledURI,supannEntiteAffectation,eduPersonPrimaryAffiliation,supannListeRouge'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUser . '&' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

    $users = json_decode(curl_exec($ch), TRUE);

    curl_close($ch);

    $reponse['users'] = $users;

    return $reponse;

  }

}