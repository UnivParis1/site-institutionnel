<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class ComptexManager implements ComptexInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  //protected $entityTypeManager;

  /**
   * PagePersonnelleService constructor.
   */
  public function __construct() {}

  /**
   * @param $username (string) username du user dont on veut les informations.
   *
   * @return array|mixed
   */
  public function getUserInformation($username) {
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $ws = $config->get('url_ws') . $config->get('search_user_page');

    $searchUser = "$ws?token=$username";

    $params = [
      'attrs' => "supannCivilite,displayName,sn,givenName,mail,supannEntiteAffectation-all,supannActivite,supannRoleEntite-all,info,employeeType,buildingName,telephoneNumber,postalAddress,info,labeledURI,eduPersonPrimaryAffiliation,supannMailPerso",
      'showExtendedInfo'=> 2
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUser . '&' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

    $userInformation = json_decode(curl_exec($ch), TRUE);
    $userInformation = reset($userInformation);

    curl_close($ch);

    $information = $this->formatComptexData($userInformation);

    if ($information && !empty($information)) {
      if (isset($information['uid'])) {
        $information['userPhoto'] = $config->get('url_userphoto') . $information['uid'];
      }
    }
    return $information;
  }

  public function userHasPagePerso($username) {
    $has_page_perso = FALSE;
    $config = \Drupal::config('up1_pages_personnelles.settings');
    $ws = $config->get('url_ws') . $config->get('search_user_page');

    $searchUser = "$ws?token=$username";

    $params = [
      'attrs' => "labeledURI"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUser . '&' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

    $user = json_decode(curl_exec($ch), TRUE);
    $user = reset($user);

    curl_close($ch);
    if ($user && isset($user['labeledURI'])) {
      $has_page_perso = TRUE;
    }

    return $has_page_perso;
  }

  /**
   * @param $array
   *
   * @return mixed
   */
  private function formatComptexData(&$information) {
    $config = \Drupal::config('up1_pages_personnelles.settings');

    if ($information && !empty($information)) {
      if (isset($information['uid'])) {
        $information['userPhoto'] = $config->get('url_userphoto') . $information['uid'];
      }
      if (isset($information['supannCivilite']) && is_array($information['supannCivilite'])) {
        $information['supannCivilite'] = reset($information['supannCivilite']);
      }
      if (isset($information['labeledURI']) && is_array($information['labeledURI'])) {
        $information['labeledURI'] = reset($information['labeledURI']);
      }
      if (isset($information['displayName']) && is_array($information['displayName'])) {
        $information['displayName'] = reset($information['displayName']);
      }
      if (isset($information['sn']) && is_array($information['sn'])) {
        $information['sn'] = reset($information['sn']);
      }
      if (isset($information['givenName']) && is_array($information['givenName'])) {
        $information['givenName'] = reset($information['givenName']);
      }
      if (isset($information['mail']) && is_array($information['mail'])) {
        $information['mail'] = reset($information['mail']);
      }
      if (isset($information['supannMailPerso']) && is_array($information['supannMailPerso'])) {
        $information['supannMailPerso'] = reset($information['supannMailPerso']);
      }
      if (isset($information['supannActivite']) && is_array($information['supannActivite'])) {
        $information['supannActivite'] = reset($information['supannActivite']);
      }
      if (isset($information['supannRoleEntite-all']) && is_array($information['supannRoleEntite-all'])) {
        $information['supannRole']['name'] = $information['supannRoleEntite-all'][0]['role'];
        $information['supannRole']['structure'] = $information['supannRoleEntite-all'][0]['structure']['description'];
      }
      if (isset($information['employeeType']) && is_array($information['employeeType'])) {
        $information['employeeType'] = reset($information['employeeType']);
      }
      if (isset($information['buildingName']) && is_array($information['buildingName'])) {
        $information['buildingName'] = reset($information['buildingName']);
      }
      if (isset($information['postalAddress']) && is_array($information['postalAddress'])) {
        $information['postalAddress'] = reset($information['postalAddress']);
      }
      if (isset($information['telephoneNumber']) && is_array($information['telephoneNumber'])) {
        $information['telephoneNumber'] = reset($information['telephoneNumber']);
      }
      if (isset($information['eduPersonPrimaryAffiliation']) && is_array($information['eduPersonPrimaryAffiliation'])) {
        $information['eduPersonPrimaryAffiliation'] = reset($information['eduPersonPrimaryAffiliation']);
      }

      if (isset($information['supannEntiteAffectation-all']) && !empty($information['supannEntiteAffectation-all'])) {
        foreach ($information['supannEntiteAffectation-all'] as $key => $supannEntiteAffectation) {
          $information['entites'][$key]['name'] = $supannEntiteAffectation['name'];
          $information['entites'][$key]['description'] = $supannEntiteAffectation['description'];
          if ($key == 0) {
            $information['entites'][$key]['labeledURI'] = $supannEntiteAffectation['labeledURI'];
          }
        }
      }
    }
    else {
      $information = [];
    }
    return $information;
  }
}
