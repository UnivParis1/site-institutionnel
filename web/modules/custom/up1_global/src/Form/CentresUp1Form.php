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

  public function renderCentre($code) {
    if (!empty($dataArray = $this->getCentresJson())) {
      $key = array_search($code, array_column($dataArray, 'Code'));
      $centre = $dataArray[$key];

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

    $htmlCentre .= '<div class="centre-up1">';
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
      }
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
