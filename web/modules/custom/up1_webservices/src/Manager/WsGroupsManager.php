<?php

namespace Drupal\up1_webservices\Manager;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

class WsGroupsManager implements WsGroupsInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Settings Service.
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  public function __construct(Settings $settings, ClientInterface $http_client) {
    $this->settings = $settings;
    $this->httpClient = $http_client;
  }

  public function getUserInformation($username, $affiliation) {
    $ws_groups = $this->settings->get('wsgroups-api');

    if (!empty($wsgroups)) {
      if ($affiliation == 'student') {
        $filter = $ws_groups['filter_student'];
      } else {
        $filter = $ws_groups['filter_teacher'];
      }
      $params = [
        'attrs' => "supannCivilite,displayName,sn,givenName,mail,supannEntiteAffectation-all,supannActivite,supannRoleEntite-all,info,employeeType,buildingName,telephoneNumber,postalAddress,labeledURI,eduPersonPrimaryAffiliation,supannMailPerso,supannConsentement",
        'allowNoAffiliationAccounts' => true,
        'showExtendedInfo'=> 2
      ];

      $parsed_url = UrlHelper::parse( $ws_groups['url'] . $filter . http_build_query($params) );
      $url = Url::fromUri($parsed_url);
      try {
        $response = $this->httpClient->get($url->toString());

        return json_decode($response->getBody()->getContents(), TRUE);
      } catch (RequestException $e) {

        return [];
      }
    }

    return [];
  }

  /**
   * Check if User has a page perso.
   *
   * @param $username
   * @return void
   */
  public function hasPagePerso($username, $affiliation): bool {
    $has_page_perso = FALSE;

    $ws_groups = $this->settings->get('wsgroups-api');
    if (!empty($wsgroups)) {
      if ($affiliation == 'student') {
        $filter = $ws_groups['filter_student'];
      } else {
        $filter = $ws_groups['filter_teacher'];
      }
      $params = ['attrs' => 'labeledURI'];

      $parsed_url = UrlHelper::parse($ws_groups['url'] . $filter . http_build_query($params));

      $url = Url::fromUri($parsed_url);

      try {
        $response = $this->httpClient->get($url->toString());
        $user = json_decode($response->getBody()->getContents(), TRUE);
        if ($user && isset($user[0]['labeledURI'])) {
          $has_page_perso = TRUE;
        }
      } catch (RequestException $e) {

        return $has_page_perso;
      }
    }

    return $has_page_perso;
  }

  public function getUsersList($affiliation, $siteId = '', $settings_trombinoscope = NULL): array
  {
    // TODO: Implement getUsersList() method.
  }
}
