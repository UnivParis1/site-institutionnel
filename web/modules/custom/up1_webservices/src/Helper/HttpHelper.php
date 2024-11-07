<?php

namespace Drupal\up1_webservices\src\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class HttpHelper
 * @package Drupal\up1_webservices\Helper
 */
class HttpHelper
{
  /**
   * Make an HTTP request to the webservice using Guzzle.
   * @param string $url
   *    Base URL to request.
   * @param array $params
   *    Parameters to send with the request.
   * @return array
   *    Response data.
   */
  public static function guzzleRequest($url, array $params = []) {
    $client = new Client();

    try {
      $response = $client->get($url,[
        'query' => http_build_query($params)
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if ( !empty($data) ) {
        $data = reset($data);
      }
    } catch (RequestException $e) {
      watchdog_exception('up1_webservices', $e);
      $data = [];
    }

    return $data;
  }
}
