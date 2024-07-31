<?php

namespace Drupal\up1_webservices\Manager;

interface WsGroupsInterface {

  public function getUserInformation($username, $affiliation);
  public function hasPagePerso($username, $affiliation): bool;
  public function getUsersList($affiliation, $siteId = '', $settings_trombinoscope = NULL): array;
}
