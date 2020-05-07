<?php


namespace Drupal\up1_global\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;


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
        'callback' => '::renderInfo',
      ]
    );
    $form['#suffix'] = '</div>
<div class="details-centre"></div>';

    return $form;

  }
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function renderInfo(array $form, FormStateInterface $form_state) {
    $content = $this->renderCentre($form_state->getValue('centre_name'));
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '.details-centre',
        '' . $content)
    );
    return $response;
  }

  public function getCentre($code) {

    if (!empty($dataArray = $this->getCentresJson())) {
      $key = array_search($code, array_column($dataArray, 'code'));
      $centre = $dataArray[$key];
    }

    return $centre;
  }

  public function getCentresJson() {
    $protocol = \Drupal::config('up1.settings')->get('webservice_centres.protocol');
    $hostname = \Drupal::config('up1.settings')->get('webservice_centres.hostname');
    $url = "$protocol://$hostname";

    $json = file_get_contents($url);
    $dataArray = json_decode($json, TRUE);

    return $dataArray;
  }

  public function getCentreImage($code) {
    $protocol = \Drupal::config('up1.settings')->get('webservice_centres.protocol');
    $path = \Drupal::config('up1.settings')->get('webservice_centres.images_path');
    $url = "$protocol://$path";

    $image_path = "";

    $file = "$url$code.jpg";
    $file_headers = @get_headers($file);
    \Drupal::logger('centres')->info("JPG : " . print_r($file_headers, 1));
    if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
      $image_path = $file;
    }
    else {
      $file = "$url$code.png";
      $file_headers = @get_headers($file);
      \Drupal::logger('centres')->info("PNG : " . print_r($file_headers, 1));
      if (!empty($file_headers) && $file_headers[0] == 'HTTP/1.1 200 OK') {
        $image_path = $file;
      }
      else {
        $random = rand(0, 1) ? 'blue' : 'white';
        $image_path = $url."default_$random.jpg";
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
    $options = [];

    if (!empty($dataArray = $this->getCentresJson())) {
      foreach ($dataArray as $key => $centre) {
        $code = $centre['code'];
        $options[$code] = $centre['intitule'];
      }
    }
    \Drupal::logger('centres')->info(print_r($options, 1));
    return $options;
  }
}
