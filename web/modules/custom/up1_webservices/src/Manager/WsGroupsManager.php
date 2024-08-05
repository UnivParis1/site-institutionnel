<?php

namespace Drupal\up1_webservices\Manager;

use Drupal\up1_webservices\Gateway\WsGroupsGatewayInterface;

class WsGroupsManager {

  public function __construct(
    private readonly WsGroupsGatewayInterface $wsGroupsGateway) {
  }

  public function getUserInformation($username, $affiliation) {
    $this->wsGroupsGateway->getUserInformation($username, $affiliation);
  }

  /**
   * Check if User has a page perso.
   *
   * @param $username
   * @return void
   */
  public function hasPagePersoInWsGroups($username): bool {
     return $this->wsGroupsGateway->userHasPagePersoWsGroups($username);
  }

  public function getUsersList($affiliation, $siteId = '', $settings_trombinoscope = NULL): array
  {
    // TODO: Implement getUsersList() method.
  }


}
