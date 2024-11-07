<?php

namespace Drupal\up1_webservices\Gateway;

interface WsGroupsGatewayInterface {

  public function getStudents(): array;
  public function getTeachers(): array;
  public function getUsersList($affiliation, $siteId = '', $settings_trombinoscope = NULL);
  public function getUserInformation($username, $affiliation);
  public function userHasPagePersoWsGroups($username);
  public function getUserAttrs($username, $attrs, $affiliation = NULL);
}
