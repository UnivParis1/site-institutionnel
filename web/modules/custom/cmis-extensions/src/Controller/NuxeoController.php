<?php

namespace Drupal\cmis_extensions\Controller;

use Drupal\cmis_extensions\Nxql;
use Drupal\cmis_extensions\Service\FoldersProvider;
use Drupal\cmis_extensions\Service\NuxeoService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NuxeoController extends ControllerBase {
  private $foldersProvider;
  private $nuxeoService;

  public function __construct(FoldersProvider $foldersProvider,
                              NuxeoService $nuxeoService) {
    $this->foldersProvider = $foldersProvider;
    $this->nuxeoService = $nuxeoService;
  }

  public function download(Request $request) {
    $id = $request->attributes->get('id');
    $index = $request->attributes->get('index');

    $nxql = new Nxql\Query();

    $nxql->where("ecm:uuid")
         ->eq($id);

    $nxql->where("ecm:ancestorId")
         ->eq($this->foldersProvider->getRootFolderId());

    $result = $this->nuxeoService->get($nxql, 1);

    if ($result->isEmpty()) {
      $response = new Response();
      $response->setStatusCode(403);
      $response->setContent("Forbidden");

      return $response;

    } else {
      return $this->nuxeoService->stream($id, $index);
    }
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cmis_extensions.folders_provider'),
      $container->get('cmis_extensions.nuxeo_service'));
  }
}
