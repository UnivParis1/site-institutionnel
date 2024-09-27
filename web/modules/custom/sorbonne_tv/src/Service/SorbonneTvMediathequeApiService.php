<?php

namespace Drupal\sorbonne_tv\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;


class SorbonneTvMediathequeApiService
{

    protected $httpClient;
    protected $urlAPI;
    protected $login;
    protected $pwd;

    public function __construct(ClientInterface $http_client)
    {
        $this->httpClient = $http_client;
        $config = \Drupal::config('sorbonne_tv.settings');
        $api_mediatheque = $config->get('sorbonne_tv.settings.api_mediatheque');
        $this->urlAPI = $api_mediatheque['url'];
        $this->login = $api_mediatheque['login'];
        $this->pwd = $api_mediatheque['pwd'];
    }


    public function getValue($endpoint = NULL, $params = NULL)
    {

        $result = NULL;

        try {
            $url = $this->urlAPI;
            $login = $this->login;
            $pwd = $this->pwd;

            if($endpoint == 'videos'){

                $response = $this->httpClient->request('GET', $url.$endpoint, [
                    'auth' => [$login, $pwd],
                    'query' => [
                        'channel' => 81,
                        'page' => $params['page'] ?? 1,
                    ]
                ]);

            }else{

                if($params){

                    $query = [];
                    foreach ($params as $param => $value){
                        $query[$param] = $value;
                    }

                    $response = $this->httpClient->request('GET', $url.$endpoint, [
                        'auth' => [$login, $pwd],
                        'query' => $query
                    ]);

                }else{
                    $response = $this->httpClient->request('GET', $url.$endpoint, [
                        'auth' => [$login, $pwd]
                    ]);
                }


            }

            $result = Json::decode($response->getBody()->getContents(), TRUE);


        } catch (RequestException $e) {
            $result = $e->getResponse()->getBody()->getContents();
        }

        return $result;
    }

    public function getVideos() {
        $endpoint = 'videos';
        $params['page'] = 1;

        $finalResults = [];
        $results = $this->getValue($endpoint, $params);

        $finalResults = array_merge($finalResults , $results['results']);

        while (isset($results['next'])) {
            $parts = parse_url($results['next']);
            parse_str($parts['query'], $query);

            $params['page'] = $query['page'];

            $results = $this->getValue($endpoint, $params);

            $finalResults = array_merge($finalResults, $results['results']);
        }

        return $finalResults;
    }
    public function getVideoContributors($id) {
      $endpoint = 'contributors';
      $params['page'] = 1;
      $params['video'] = $id;

      $finalResults = [];
      $results = $this->getValue($endpoint, $params);

      $finalResults = array_merge($finalResults , $results['results']);

      while (isset($results['next'])) {
          $parts = parse_url($results['next']);
          parse_str($parts['query'], $query);

          $params['page'] = $query['page'];

          $results = $this->getValue($endpoint, $params);

          $finalResults = array_merge($finalResults, $results['results']);
      }

      return $finalResults;
    }

    public function getDocs($id) {
        $endpoint = 'documents';
        $params['page'] = 1;
        $params['video'] = $id;

        $finalResults = [];
        $results = $this->getValue($endpoint, $params);

        $finalResults = array_merge($finalResults , $results['results']);

        while (isset($results['next'])) {
            $parts = parse_url($results['next']);
            parse_str($parts['query'], $query);

            $params['page'] = $query['page'];

            $results = $this->getValue($endpoint, $params);

            $finalResults = array_merge($finalResults, $results['results']);
        }

        return $finalResults;
    }


    public function getTracks($id) {
        $endpoint = 'tracks';
        $params['page'] = 1;
        $params['video'] = $id;

        $finalResults = [];
        $results = $this->getValue($endpoint, $params);

        $finalResults = array_merge($finalResults , $results['results']);

        while (isset($results['next'])) {
            $parts = parse_url($results['next']);
            parse_str($parts['query'], $query);

            $params['page'] = $query['page'];

            $results = $this->getValue($endpoint, $params);

            $finalResults = array_merge($finalResults, $results['results']);
        }

        return $finalResults;
    }


}
