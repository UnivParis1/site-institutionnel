<?php

namespace Drupal\up1_data_import\Service;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypoHelper {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $dataservice) {
    $this->database = $dataservice;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database.typo')
    );
  }
  public function selectFeUsers($username) {
    $query = $this->database->select('fe_users', 'fu');
    $fields = [
      'tx_oxcspagepersonnel_courriel',
      'tx_oxcspagepersonnel_responsabilites_scientifiques',
      'tx_oxcspagepersonnel_sujet_these',
      'tx_oxcspagepersonnel_projets_recherche',
      'tx_oxcspagepersonnel_directeur_these',
      'tx_oxcspagepersonnel_publications',
      'tx_oxcspagepersonnel_epi',
      'tx_oxcspagepersonnel_cv',
      'tx_oxcspagepersonnel_cv2',
      'tx_oxcspagepersonnel_directions_these',
      'tx_oxcspagepersonnel_page_externe_url',
    ];
    $query->fields('fu', $fields);
    $query->condition('username', $username, 'LIKE');
    $result = $query->execute();

    return $result;
  }
}
