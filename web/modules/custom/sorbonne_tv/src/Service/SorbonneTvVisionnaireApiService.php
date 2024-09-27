<?php

namespace Drupal\sorbonne_tv\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;


class SorbonneTvVisionnaireApiService
{

    protected $httpClient;
    protected $urlAPI;
    public function __construct(ClientInterface $http_client)
    {
        $this->httpClient = $http_client;
        $config = \Drupal::config('sorbonne_tv.settings');
        $api_visionnaire = $config->get('sorbonne_tv.settings.api_visionnaire');
        $this->urlAPI = $api_visionnaire['url'];
    }

    public function getProgramme($endpoint = NULL, $params = NULL)
    {

        $result = NULL;

        try {
            $url = $this->urlAPI;
            $response = $this->httpClient->request('GET', $url.$endpoint, [
                'query' => [
                    'from' => $params['date'] ?: date('Y-m-d'),
                    'days' => $params['days'] ?: 1,
                    'format' => $params['format'] ?: 'json'
                ]
            ]);
            $json = $response->getBody()->getContents();
            $result = $json;


        } catch (RequestException $e) {
            $result = $e->getResponse()->getBody()->getContents();
        }

        return $result;
    }


}
