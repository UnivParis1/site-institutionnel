<?php

namespace Drupal\up1_pages_personnelles\Plugin\QueueWorker;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
use Drupal\up1_pages_personnelles\WsGroupsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity;

/**
 * Executes users (enseignants & doctorants) import from web service.
 *
 * @QueueWorker(
 *   id = "up1_typo3_data_queue",
 *   title = @Translation("Page Perso populate"),
 *   cron = {"time" = 30}
 *  )
 */
class Typo3DataImportQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;
  /**
   * The theses service.
   *
   * @var WsGroupsService;
   */
  protected $wsGroups;

  /**
   * thesesQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param LoggerChannelFactoryInterface $logger
   * @param EntityTypeManagerInterface $entity_type
   * @param WsGroupsService $ws_groups
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type,
                              WsGroupsService $ws_groups,
                              LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type;
    $this->loggerChannelFactory = $logger;
    $this->wsGroups = $ws_groups;
  }

  /**
   * {@
   */
  public static function create(ContainerInterface $container,
                                array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('up1_pages_personnelles.wsgroups'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($item) {
    $user = user_load_by_name($item->username);

    if ($user) {
      $author = $user->id();
      $ids = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $author)
        ->execute();
      $pages = Node::loadMultiple($ids);
      if (!empty($pages)) {
        foreach ($pages as $node) {
          $node->field_other_email_address = $item->tx_oxcspagepersonnel_courriel;
          $node->field_scientific_resp = $item->tx_oxcspagepersonnel_responsabilites_scientifiques;
          $node->field_thesis_subject = $item->tx_oxcspagepersonnel_sujet_these;
          $research = "";
          if (isset($item->tx_oxcspagepersonnel_themes_recherche) && !empty($item->tx_oxcspagepersonnel_themes_recherche)) {
            $research .= "<br />" . $item->tx_oxcspagepersonnel_themes_recherche;
          }
          if (isset($item->tx_oxcspagepersonnel_projets_recherche) && !empty($item->tx_oxcspagepersonnel_projets_recherche)) {
            $research .= "<br />" . $item->tx_oxcspagepersonnel_projets_recherche;
          }
          $node->field_research_themes = $research;
          $node->field_phd_supervisor = $item->tx_oxcspagepersonnel_directeur_these;
          //$node->field_publications = strip_tags($item->tx_oxcspagepersonnel_publications, ['<p><a>']);
          //$node->field_resume_text = $item->tx_oxcspagepersonnel_cv2;
          $node->field_thesis_directions = $item->tx_oxcspagepersonnel_directions_these;
          $node->field_other_page_perso = $item->tx_oxcspagepersonnel_page_externe_url;
          if (isset($item->tx_oxcspagepersonnel_cv) && !empty($item->tx_oxcspagepersonnel_cv)) {
            $node->field_link_to_resume = "//www.pantheonsorbonne.fr/uploads/pics/" . $item->tx_oxcspagepersonnel_cv;
          }

          $node->save();
        }
      }
    }
  }
}
