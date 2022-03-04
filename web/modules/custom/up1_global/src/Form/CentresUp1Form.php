<?php

namespace Drupal\up1_global\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\up1_global\Controller\CentresController;

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
      '#prefix' => '<div class="overlay-infos">',
      '#type' => 'select',
      '#title' => t('Search a centre'),
      '#options' => $this->getCentresList(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'renderInfo'],
        'event' => 'change',
        'method' => 'html',
        'wrapper' => 'center-wrapper-ajax'
      ],
      '#suffix' => '</div>',
    ];

    $form['my_ajax_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'center-wrapper-ajax'
      ]
    ];

    if (!empty($form_state->getValues()) && !empty($form_state->getValue('centre_name'))) {
      $displayCentre = new CentresController();
      $content = $displayCentre->blockCentre($form_state->getValue('centre_name'));

      $form['my_ajax_container']['my_response'] = [
        '#markup' => \Drupal::service('renderer')->render($content),
      ];
    }

    $form['#suffix'] = '</div>';

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}


  public function renderInfo(array $form, FormStateInterface $form_state) {


    /*$response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand('.details-centre', $content)
    );*/
    return $form['my_ajax_container'];
  }

  public function getCentre($code) {
    if (!empty($dataArray = $this->getCentresJson())) {
      $key = array_search($code, array_column($dataArray, 'code'));
      $centre = $dataArray[$key];
    }

    return $centre;
  }

  public function getCentresJson() {
    $cache = \Drupal::cache();

    $up1_centres = $cache->get('up1_liste_centres');
    if ($up1_centres) {
      $dataArray = $up1_centres->data;
    }
    else {
      $url = "https://ws-centres.univ-paris1.fr/new_liste_centres_up1.json";
      $json = file_get_contents($url);
      $dataArray = json_decode($json, TRUE);
      $cache->set('up1_liste_centres', $dataArray, time() + 24 * 60 * 60);
    }
    return $dataArray;
  }

  public function getCentreImage($code) {
    $url = "https://ws-centres.univ-paris1.fr/images";

    $image_path = "";

    $file = "$url/$code.jpg";
    $file_headers = @get_headers($file);

    if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
      $image_path = $file;
    }
    else {
      $file = "$url/$code.png";
      $file_headers = @get_headers($file);

      if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
        $image_path = $file;
      }
      else {
        $random = rand(0, 1) ? 'blue' : 'white';
        $image_path = "$url/default_$random.jpg";
      }
    }

    return $image_path;
  }

  public function renderCentre($code) {
    $image_url = $this->getCentreImage($code);
    $htmlCentre = "";

    $block_phone = "<div class='centre-phone'>";
    $block_email = "<div class='centre-email'>";
    $block_info = "<div class='centre-info'>";

    $centre = $this->getCentre($code);
    $title = $centre['intitule'];
    $address = $centre['adresse'];
    $block_address = "
      <i class='fa fa-map-signs'></i>
      <a href='//maps.google.fr/maps?t=m&z=16&q=$address' target='_blank' title='$title'>
        $address
      </a>";
    //telephone
    !empty($centre['telephone']) ? $phone = $centre['telephone'] : $phone = "";

    if (!empty($phone)) {
      $block_phone .= "<i class='fas fa-phone'></i>";
      $block_phone .= "<div>";
      foreach ($phone as $item) {
        $block_phone .= "<p><a href='tel:$item'>$item</a></p>";
      }
      $block_phone .="</div>";
    }
    $block_phone .= "</div>";

    //email
    !empty($centre['email']) ? $email = $centre['email'] : $email = "";

    if (!empty($email)) {
      $block_email .= "<div class='centre-phone'>
            <i class='fas fa-envelope'></i>
            <a href='mailto:$email'>$email</a>
            </div>";
    }
    $block_email .= "</div>";

    //infos
    !empty($centre['informations']) ? $info = $centre['informations'] : $info = "";

    if (!empty($info)) {
      $block_info .= "<div class='centre-more'>
        <i class='fas fa-info-circle'></i>
        <a href='$info'>$info</a>
        </div>";
    }
    $block_info .= "</div>";
    $img = file_create_url($image_url);
    $htmlCentre .= "<div class='image-centre mask no-hover relative-wrapper'>
        <img src='$img' />
    </div>";

    $htmlCentre .=
      "<div class='centre-up1'>
           <div class='centre-up1-info'>
            <h4>$title</h4>
            <address>$block_address</address>
            $block_phone
            $block_email
            $block_info
          </div>
        </div>";

    return $htmlCentre;
  }

  public function getCentresList() {
    $object = [];
    $options = [];

    if (!empty($dataArray = $this->getCentresJson())) {
      foreach ($dataArray as $key => $centre) {
        $code = $centre['code'];
        $object[$code] = $centre['intitule'];
      }
      $obj = new \ArrayObject($object);
      $obj->asort();
      foreach ($obj as $key => $item) {
        $options[$key] = $item;
      }
    }

    return $options;
  }
}
