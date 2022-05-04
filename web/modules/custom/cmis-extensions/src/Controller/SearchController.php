<?php

namespace Drupal\cmis_extensions\Controller;

use Drupal\cmis_extensions\Service\NomenclaturesProvider;
use Drupal\cmis_extensions\Service\FoldersProvider;
use Drupal\cmis_extensions\Service\NuxeoService;
use Drupal\cmis_extensions\Nxql;
use Drupal\cmis_extensions\ResultBag;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchController extends ControllerBase {
  private $nomenclaturesProvider;
  private $foldersProvider;
  private $nuxeoService;

  public function __construct(NomenclaturesProvider $nomenclaturesProvider,
                              FoldersProvider $foldersProvider,
                              NuxeoService $nuxeoService) {
    $this->nomenclaturesProvider = $nomenclaturesProvider;
    $this->foldersProvider       = $foldersProvider;
    $this->nuxeoService          = $nuxeoService;
  }

  public function displayResults(Request $request) {
    $query = $request->query;

    $q    = $request->get('q');
    $year = $request->get('year');

    $kind    = $this->getInt($query, 'kind');
    $page    = $this->getInt($query, 'page');
    $entity  = $this->getInt($query, 'entity');
    $council = $this->getInt($query, 'council');

    $nxql = new Nxql\Query();

    if ($q) {
      $orChain = $nxql->or();

      $andChain = $orChain->and();

      foreach (explode(" ", $q) as $part) {
        $andChain->where("dc:title")
                 ->like($part);
      }

      $orChain->where("ecm:fulltext")
              ->eq(str_replace(")", "\)", str_replace("(", "\(", $q)));
    }

    if ($entity == 0 && !is_null($council)) {
      $folder = $this->foldersProvider
          ->councilFolder($council);

      $nxql->where("ecm:ancestorId")
           ->eq($folder);
    }

    if (!is_null($entity) && !is_null($kind)) {
      $possible_folders = $this->foldersProvider
          ->possibleFoldersFor($entity, $kind);

      $orChain = $nxql->or();

      foreach ($possible_folders as $folder) {
        $orChain->where("ecm:ancestorId")
                ->eq($folder);
      }

    } else if (!is_null($entity)) {
      $folder = $this->foldersProvider
          ->entityFolder($entity);

      $nxql->where("ecm:ancestorId")
           ->eq($folder);

    } else if (!is_null($kind)) {
      $possible_folders = $this->foldersProvider
          ->kindFolders($kind);

      $orChain = $nxql->or();

      foreach ($possible_folders as $folder) {
        $orChain->where("ecm:ancestorId")
                ->eq($folder);
      }

    } else {
      $nxql->where("ecm:ancestorId")
           ->eq($this->foldersProvider->getRootFolderId());
    }

    if ($year) {
      $nxql->where("file:content/name")
           ->strictLike($year . "_%");
    }

    $nxql->where("ecm:currentLifeCycleState")
         ->eq("project");

    $nxql->orderBy("dc:created")
         ->desc();

    if ($q) {
      $nxql->orderBy("ecm:fulltextScore")
           ->desc();
    }

    if ($nxql->isEmpty()) {
      $resultBag = ResultBag::empty();
    } else {
      $resultBag = $this->nuxeoService->get($nxql, 25, $page - 1);
    }

    $form = $this->buildForm($q, $kind, $entity, $council, $year);

    return [
      '#theme' => 'cmis_extensions_search_results',
      '#form' => $form,
      '#result_bag' => $resultBag,
      '#page' => $page
    ];
  }

  private function getInt($query, $key) {
    if ($query->has($key) && $query->get($key) != "") {
      return $query->getInt($key);
    } else {
      return null;
    }
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cmis_extensions.nomenclatures_provider'),
      $container->get('cmis_extensions.folders_provider'),
      $container->get('cmis_extensions.nuxeo_service'));
  }

  private function buildForm($q, $kind, $entity, $council, $year) {
    $form = [];

    $form['q'] = [
      '#type' => 'textfield',
      '#prefix' => '<div class="row"><div class="col-12">',
      '#suffix' => '</div></div>',
      '#value' => $q,
      '#attributes' => [
        'name' => 'q',
        'placeholder' => $this->t('Search ...')
      ]
    ];

    $form['kind'] = [
      '#type' => 'select',
      '#prefix' => '<div class="row"><div class="col-6">',
      '#suffix' => '</div></div>',
      '#options' => [
        "" => "--- " . $this->t("by kind"),
        ...$this->nomenclaturesProvider->document_kinds()
      ],
      '#attributes' => [
        'name' => 'kind'
      ],
      '#value' => $kind
    ];

    $form['entity'] = [
      '#type' => 'select',
      '#prefix' => '<div class="row"><div class="col-6">',
      '#suffix' => '</div></div>',
      '#options' => [
        "" => "--- " . $this->t("by entity"),
        ...$this->foldersProvider->entities()
      ],
      '#attributes' => [
        'id' => 'entity_select',
        'name' => 'entity'
      ],
      '#value' => $entity
    ];

    $form['council'] = [
      '#type' => 'select',
      '#prefix' => '<div class="row"><div class="col-6">',
      '#suffix' => '</div></div>',
      '#options' => [
        "" => "--- " . $this->t("by council"),
        ...$this->foldersProvider->councils()
      ],
      '#states' => [
        'visible' => [
          ':input[id="entity_select"]' => ['value' => 0]
        ]
      ],
      '#attributes' => [
        'name' => 'council',
      ],
      '#value' => $council
    ];

    $years = [
      "" => "--- " . $this->t("by year")
    ];

    foreach ($this->nomenclaturesProvider->filter_years() as $y) {
      $years[$y] = $y;
    }

    $form['year'] = [
      '#type' => 'select',
      '#prefix' => '<div class="row"><div class="col-6">',
      '#suffix' => '</div></div>',
      '#options' => $years,
      '#attributes' => [
        'name' => 'year'
      ],
      '#value' => $year
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#prefix' => '<div class="row"><div class="col-6 text-end">',
      '#suffix' => '</div></div>',
      '#value' => $this->t("Search"),
      '#attributes' => [ 'name' => '' ]
    ];

    return $form;
  }
}
