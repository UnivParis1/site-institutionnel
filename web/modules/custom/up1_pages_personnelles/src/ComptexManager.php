<?php

namespace Drupal\up1_pages_personnelles;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\Entity\Site;

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

        /**
         * @TODO: Delete employeeType when gender is validated
         */
        $params = [
            'attrs' => "supannCivilite,displayName,sn,givenName,mail,supannEntiteAffectation-all,supannActivite,supannRoleEntite-all,info,employeeType-all,buildingName,telephoneNumber,postalAddress,info,labeledURI,eduPersonPrimaryAffiliation,supannMailPerso,supannConsentement",
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
            if (isset($information['supannActivite']) && is_array($information['supannActivite'])) {
                $information['supannActivite'] = reset($information['supannActivite']);
            }

            if (isset($information['supannRoleEntite-all']) && is_array($information['supannRoleEntite-all'])) {
                $information['supannRole']['role'] = $information['supannRoleEntite-all'][0]['role'];
                $information['supannRole']['name'] = $information['supannRoleEntite-all'][0]['structure']['name'];
                $information['supannRole']['structure'] = $information['supannRoleEntite-all'][0]['structure']['description'];
            }

            /**
             * @TODO: Delete those lines when gender is validated
             */
            if ($information['eduPersonPrimaryAffiliation'] == 'student') {
                $information['employeeType'] = $information['supannCivilite'] == "Mme" ? "Doctorante" : "Doctorant";
            }
            else {
              if (isset($information['employeeType-all']) && is_array($information['employeeType-all'])) {
                $employee_types = array_column($information['employeeType-all'], 'name', 'weight');
                $key = min(array_keys($employee_types));
                $information['employeeType'] = $employee_types[$key];
              }
            }
            /**
             * @TODO: Uncomment those lines when gender is validated
             */
            /*
            if ($information['eduPersonPrimaryAffiliation'] == 'student') {
                $information['employeeType'] = $information['supannCivilite'] == "Mme" ? "Doctorante" : "Doctorant";
            }
            else {
              if (isset($information['employeeType-all']) && is_array($information['employeeType-all'])) {
                  if ($information['supannCivilite'] == "Mme")) {
                      foreach ($information['employeeType-all'] as $key => &$value) {
                          if (isset($value['name-gender'])) {
                              $value['name'] = $value['name-gender'];
                          }
                      }
                  }
                  $employee_types = array_column($information['employeeType-all'], 'name', 'weight');
                  $key = min(array_keys($employee_types));
                  $information['employeeType'] = $employee_types[$key];
              }
            }
            */
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
                    $business_cat = $supannEntiteAffectation['businessCategory'];
                    $uri = "";
                    if (isset($supannEntiteAffectation['labeledURI'])) {
                        $uri = $supannEntiteAffectation['labeledURI'];
                    } else {
                        $site_group = $supannEntiteAffectation['key'];
                        $ids = \Drupal::entityQuery('site')
                            ->condition('type', 'mini_site')
                            ->condition('groups', $site_group)
                            ->execute();
                        $site = Site::loadMultiple($ids);
                        if (count($site) == 1) {
                            $site = reset($site);
                            $site_url = $site->get('site_url')->getValue();
                            $uri = "//" . $site_url[0]['value'];
                        }
                    }
                    if ((in_array($information['employeeType'], ['Doctorant', 'Doctorante']) && $business_cat != 'pedagogy') ||
                        (!in_array($information['employeeType'], ['Doctorant', 'Doctorante']))) {
                        $entites[] = [
                            'businessCategory' => $business_cat,
                            'name' => $supannEntiteAffectation['name'],
                            'description' => $supannEntiteAffectation['description'],
                            'labeledURI' => $uri
                        ];
                    }
                }
                $order = ['doctoralSchool',  'research',  'pedagogy'];

                usort($entites, function($a, $b) use ($order) {
                    $pos_a = array_search($a['businessCategory'], $order);
                    $pos_b = array_search($b['businessCategory'], $order);
                    return $pos_a - $pos_b;
                });

                $information['entites'] = [];
                foreach ($entites as $entite) {
                    if (!empty($entite['labeledURI'])) {
                        $affectation[] = "<p><a href='" . $entite['labeledURI'] . "' title='" . $entite['description'] . "' target='_blank'>"
                            . $entite['description'] . "</a></p>";
                    }
                    else {
                        $affectation[] = "<p>" . $entite['description'] . "</p>";
                    }
                    $information['entites'] = implode('', $affectation);
                }
            }

            //Consentement pour apparaître dans l'annuaire de l'Observatoire de l'IA
            $information['obsia'] = FALSE;
            \Drupal::logger('comptex')->info(print_r($information['supannConsentement'], 1));
            \Drupal::logger('comptex')->info(print_r(array_search($information['supannConsentement'], '{PROJ:OBSIA}CGU'), 1));
            if (!empty($information['supannConsentement'])) {
              if (array_search($information['supannConsentement'], '{PROJ:OBSIA}CGU')) {
                $information['obsia'] = TRUE;
              }
            }
        }
        else {
            $information = [];
        }
        return $information;
    }

    public function getUserEmail($uid) {
        $config = \Drupal::config('up1_pages_personnelles.settings');
        $ws = $config->get('url_ws') . $config->get('search_user_page') . "?token=$uid";

        $params = [
            'attrs' => 'mail,supannMailPerso,eduPersonPrincipalName',
            'showExtendedInfo' => 2
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ws . '&' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

        $information = json_decode(curl_exec($ch), TRUE);
        curl_close($ch);
        $information = reset($information);
        $this->formatEmails($information);

        return $information['mail'];
    }

    private function formatEmails(&$information) {
        if (isset($information['mail']) && !empty($information['mail'])) {
            unset($information['eduPersonPrincipalName']);
        }
        if ((!isset($information['mail']) || empty($information['mail'])) &&
            (isset($information['eduPersonPrincipalName']) && !empty($information['eduPersonPrincipalName']))) {
            $information['mail'] = $information['eduPersonPrincipalName'];
        }
    }
}
