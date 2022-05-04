<?php

namespace Drupal\cmis_extensions\Service;

use Drupal\cmis_extensions\Nxql;
use Drupal\cmis_extensions\ResultBag;
use Drupal\cmis_extensions\Result;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class NuxeoService {
  private $client;

  private $user;
  private $password;

  private const BASE_URL = "https://ged.uphf.fr";

  public function __construct() {
    $this->client = new GuzzleClient();

    $config = \Drupal::config('cmis_extensions.settings');

    $this->user = $config->get('user');
    $this->password = $config->get('password');
  }

  public function get(Nxql\Query $nxql, $pageSize, $currentPageIndex = 0) {
    $query = $nxql->toNxql(true);
    $url   = self::BASE_URL . "/nuxeo/site/api/v1/query?query=" . $query
        . "&pageSize=" . $pageSize
        . "&currentPageIndex=" . $currentPageIndex;

    try {
      $response = $this->client->get($url, [
        'auth' => [$this->user, $this->password],
        'headers' => [
          'properties' => 'file'
        ]
      ]);

      if ($response->getStatusCode() != 200)
        return ResultBag::empty();

      $json = json_decode($response->getBody());
      $results = [];

      foreach ($json->entries as $entry) {
        $fileContent = $entry->properties->{"file:content"};

        array_push($results, new Result(
          $entry->uid,
          $entry->title,
          $fileContent ? $fileContent->length : 0,
          strtotime($entry->lastModified),
          $this->folderName($entry->parentRef)
        ));
      }

      return new ResultBag(
        $results,
        $json->totalSize,
        $json->numberOfPages,
        \Drupal::request()->query->all()
      );

    } catch (GuzzleRequestException $e) {
      echo $e;

      return ResultBag::empty();
    }
  }

  public function stream($id, $index) : Response {

    try {
      $nuxeoResponse = $this->client->get(
        self::BASE_URL . "/nuxeo/api/v1/repo/default/id/"
          . $id . "/@blob/files:files/"
          . $index . "/file",
        [
          'auth' => [$this->user, $this->password],
          'stream' => true
        ]
      );

      $body = $nuxeoResponse->getBody();

      $response = new StreamedResponse(function() use ($body) {
        while (!$body->eof()) {
          echo $body->read(1024);
        }
      });

      $response->headers->set('Content-Type', 'application/pdf');

      return $response;

    } catch (GuzzleRequestException $e) {
      echo $e;

      $response = new Response();
      $response->setStatusCode(500);

      return $response;
    }
  }

  public function folderName($id) {
    if ($cached = \Drupal::cache()->get("cmis_extensions:folders")) {
      return $cached->data[$id];
    } else {
      return "";
    }
  }

  public function cacheAllPaths() {
    $cache = [];
    $this->cachePath('4e693e96-7f7b-45ba-8529-685058ca1610', $cache);

    \Drupal::cache()->set("cmis_extensions:folders", $cache);
  }

  private function cachePath($id, &$cache, $parent = null) {
    try {
      $response = $this->client->get(
        self::BASE_URL . "/nuxeo/json/cmis/default/root?objectId=" . $id,
        ['auth' => [$this->user, $this->password]]
      );

      $json = json_decode($response->getBody());

      foreach ($json->objects as $entry) {
        $properties = $entry->object->properties;

        if ($properties->{"cmis:baseTypeId"}->value != "cmis:folder")
          continue;

        $id   = $properties->{"cmis:objectId"}->value;
        $name = $properties->{"cmis:name"}->value;
        $path = $parent == null ? $name : $parent . " / " . $name;

        $cache[$id] = $path;

        $this->cachePath($id, $cache, $path);
      }

    } catch (GuzzleRequestException $e) {
      echo $e;
    }
  }
}
