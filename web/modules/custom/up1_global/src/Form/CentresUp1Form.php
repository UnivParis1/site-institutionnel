<?php


namespace Drupal\up1_global\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

class CentresUp1Form extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'centres_up1_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="map-centre" class="map-centre">';
    $form['centre_name'] = [
      '#type' => 'select',
      '#title' => t('Search a centre'),
      '#options' => $this->getCentresList(),
      '#required' => TRUE,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::renderMap',
      ]
    );
    $form['map'] = [
      '#type' => 'markup',
      '#markup' => '<div class="details-centre"></div>'
    ];
    $form['#suffix'] = '</div>';

    $form['#attached'] = [
      'library' => [
        'leaflet/leaflet',
      ]
    ];
    return $form;

  }
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function renderMap(array $form, FormStateInterface $form_state) {
    $content = $this->renderCentre($form_state->getValue('centre_name'));
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '.details-centre',
        '' . $content)
    );
    return $response;
  }

  public function getCentresJson() {
    $url = "http://ws-theses.univ-paris1.fr/centres_up1";
    $json = file_get_contents($url);
    $dataArray = json_decode($json, TRUE);

    return $dataArray;
  }

  public function renderCentre($code, $with_phone = TRUE, $with_fax = TRUE) {
    if (!empty($dataArray = $this->getCentresJson())) {
      $key = array_search($code, array_column($dataArray, 'Code'));
      $centre = $dataArray[$key];

      $has_tel_fax = 0;
      $has_transports = 0;
      !empty($centre['LibWeb'])? $libWeb = $centre['LibWeb'] : $libWeb = "";
      !empty($centre['Adresse'])? $adresse = $centre['Adresse'] : $adresse = "";
      !empty($centre['Tel'] && $with_phone)? $tel = $centre['Tel'] : $tel = "";
      !empty($centre['Tel'] && $with_phone)? $has_tel_fax++ : $has_tel_fax;
      !empty($centre['Fax'] && $with_fax)? $fax = $centre['Fax'] : $fax = "";
      !empty($centre['Fax'] && $with_fax)? $has_tel_fax++ : $has_tel_fax;
      !empty($centre['Metro'])? $metro = $centre['Metro'] : $metro = "";
      if (!empty($metro)) {
        $metro = "<div>
        <span><b>" . t('Métro : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $metro) . "</span>
        </div>";
        $has_transports++;
      }
      !empty($centre['Rer'])? $rer = $centre['Rer'] : $rer = "";
      if (!empty($rer)) {
        $rer = "<div>
        <span><b>" . t('RER : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $rer) . "</span>
        </div>";
        $has_transports++;
      }
      !empty($centre['Bus'])? $bus = $centre['Bus'] : $bus = "";
      if (!empty($bus)) {
        $bus = "<div>
        <span><b>" . t('Bus : ') . "</b></span>
        <span>" . preg_replace('/ ; /', ', ', $bus) . "</span>
        </div>";
        $has_transports++;
      }
      if ($has_tel_fax > 0) {
        $tel_fax = "<div class='centre-phone-fax'>";
        if (!empty($tel)) {
          $tel_fax .= "<div class='centre-phone'>
            <span>" . t('Phone : ') . "</span>
            <a href='tel:$tel'>$tel</a>  
            </div>";
        }
        if (!empty($fax)) {
          $tel_fax .= "<div class='centre-fax'>
            <span>" . t('Fax : ') . "</span>
            <span>$fax</span>  
            </div>";
        }
        $tel_fax .= '</div>';
      }
      else { $tel_fax = ""; }
      if ( $has_transports > 0 ) {
        $transports = "<div class='centre-itineraire'>
           <span>" . t('Accès transports : ') . "</span>
           $metro $rer $bus
           </div>";
      }
      else { $transports = ''; }

    $htmlCentre = '<script>
      var osmUrl = "https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png";
      var osmAttr = "&copy; Openstreetmap France";
      osmLayer = new L.tileLayer(osmUrl, {
          maxZoom: 20,
          attribution: osmAttr,
          });
      var leafletMap = new L.map("map-centre", {
        center: [' . $centre['Lat'] . ', ' . $centre['Long'] .'],
        zoom: 17,
        zoomControl: true,
        scrollWheelZoom: false,
      });
      leafletMap.addLayer(osmLayer);
      new L.marker([' . $centre['Lat'] . ', ' . $centre['Long'] .']).addTo(leafletMap);
        </script>';


     $htmlCentre .=
       "<div class='centre-up1'>
            <div class='center-address'>
               <i class='fa fa-map-marker'></i> 
               <div>
                <h4>$libWeb</h4>
                <address>$adresse</address>
                $tel_fax
               </div>
            </div>
            $transports
        </div>";
    /*$htmlCentre .= '<div class="centre-up1">';
      $htmlCentre .= '<h4>' . $centre['LibWeb'] . '</h4>';
      $htmlCentre .= '<address><i class="fa fa-map-marker"></i>' . $centre['Adresse'] . '</address>';
      if (!empty($centre['Tel']) || $centre['Fax']) {
        $htmlCentre .= '<div class="centre-phone-fax" >';
        if (!empty($centre['Tel'])) {
          $htmlCentre .= '<div class="centre-phone">
          <span>' . t('Phone : ') . '</span>
          <a href = "tel:' . $centre['Tel'] . '">' . $centre['Tel'] . '</a >
          </div >';
        }
        if (!empty($centre['Fax'])) {
          $htmlCentre .= '<div class="centre-fax">
          <span>' . t('Fax : ') . '</span>' . $centre['Fax'] . '</div >';
        }
        $htmlCentre .= '</div >';
      }
      if (!empty($centre['Metro']) || $centre['Rer'] || $centre['Bus']) {
        $htmlCentre .= '<div class="centre-itineraire">';
        if (!empty($centre['Metro'])) {
          $htmlCentre .= '<div class="centre-metro">
          <i class="fa fa-subway"></i>' . $centre['Metro'] . '</div>';
        }
        if (!empty($centre['Rer'])) {
          $htmlCentre .= '<div class="centre-rer" >
          <i class="fa fa-train" ></i >' . $centre['Rer'] . '</div >';
        }
        if (!empty($centre['Bus'])) {
          $htmlCentre .= '<div class="centre-bus" >
          <i class="fa fa-bus" ></i >' . $centre['Bus'] . '</div >';
        }
        $htmlCentre .= '</div>';
      }*/
    }

    $htmlCentre .= '</div>';

    return $htmlCentre;
  }

  public function getCentresList() {
    $options = [];

    if (!empty($dataArray = $this->getCentresJson())) {
      foreach ($dataArray as $key => $centre) {
        if ($centre['Etat'] == 'Ouvert') {
          $code = $centre['Code'];
          $options[$code] = $centre['LibWeb'];
        }
      }
    }
    return $options;
  }
}
