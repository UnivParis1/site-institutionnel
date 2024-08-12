<?php

namespace Drupal\up1_webservices\Gateway;

use Drupal\up1_webservices\Gateway\WsGroupsGatewayInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class WsGroupsGateway implements WsGroupsGatewayInterface {

  /**
   * The HTTP client.
   * @var \GuzzleHttp\Client
   */

  protected $httpClient;
  /**
   * Settings Service.
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  public function __construct(
    ClientInterface $http_client,
    Settings $settings) {
    $this->httpClient = $http_client;
    $this->settings = $settings;
  }

  public function getStudents(): array {
    $ws_groups = $this->settings->get('wsgroups-api');
    $filter = $ws_groups['filter_student'];
    $params = [
      'attrs' => "mail, supannCivilite,displayName,givenName,sn,supannEntiteAffectation-all,labeledURI,supannMailPerso,supannListeRouge",
      'allowNoAffiliationAccounts' => true,
      'showExtendedInfo'=> 2,
    ];
    $parsed_url = UrlHelper::parse( $ws_groups['url'] . $filter . http_build_query($params) );
    $url = Url::fromUri($parsed_url['path'], [$parsed_url['query']]);
    try {
      $response = $this->httpClient->get($url->toString());

      return json_decode($response->getBody()->getContents(), TRUE);
    } catch (RequestException $e) {
      return [];
    }
  }

  public function getTeachers(): array {
    return [];
  }

  public function getUsersList($affiliation, $siteId = '', $settings_trombinoscope = NULL) {  }

  public function getUserInformation($username, $affiliation)
  {    // TODO: Implement getUserInformation() method.

  }

  public function getUserAttrs($username, $attrs, $affiliation = NULL): array {
    $ws_groups = $this->settings->get('wsgroups-api');
    if ( !empty($ws_groups )) {
      $filter = "&id=" . trim($username);
      $params = [
        'attrs' => implode(',', $attrs),
      ];
    }

    $parsed_url = UrlHelper::parse($ws_groups['url'] . $filter . '&' . http_build_query($params));
    $url = Url::fromUri($parsed_url['path'], ['query' => $parsed_url['query']]);

    try {
      $response = $this->httpClient->get($url->toString());
      $user = json_decode($response->getBody()->getContents(), TRUE);

      if (!empty($user[0])) {
        return $user[0];
      }
    } catch (RequestException $e) {
      return [];
    }

    return [];
  }

  public function userHasPagePersoWsGroups($username): bool {
    $ws_groups = $this->settings->get('wsgroups-api');
    if (!empty($ws_groups)) {
      $filter = '&id=' . trim($username);
      $params = [
        'attrs' => 'labeledURI,supannListeRouge',
      ];

      $parsed_url = UrlHelper::parse($ws_groups['url'] . $filter . '&' . http_build_query($params));
      $url = Url::fromUri($parsed_url['path'], ['query' => $parsed_url['query']]);

      try {
        $response = $this->httpClient->get($url->toString());
        $user = json_decode($response->getBody()->getContents(), TRUE);

        if (!empty($user[0]['labeledURI'])) {
          return TRUE;
        }
      } catch (RequestException $e) {
        return FALSE;
      }
    }

    return FALSE;
  }

  public function hasPagePerso($username) {
    // TODO: Implement hasPagePerso() method.
  }

}
