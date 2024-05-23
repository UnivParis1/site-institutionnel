<?php

namespace Drupal\micro_publications\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MicroPublicationsConfigForm extends FormBase {
  protected $additionnal_rows = 0;
  protected $site;

  const FORMID = "MicroPublicationsConfigForm";

  public function getFormId() {
    return self::FORMID;
  }


  public function buildForm(array $form, FormStateInterface $form_state, $site = NULL) {
    $this->site = $site;

    $labstructname = $site->get('field_labstructname_t')->getValue();
    $request_fields = $site->get('field_request_fields')->getValue();
    $types = $site->get('field_doctype')->getValue();
    $nbRowsWithValue = count($types);

    $form['general_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Paramètres généraux de la requête'),
      '#collapsible' => TRUE,
      '#open' => TRUE
    ];
    $form['general_settings']['field_labstructname_t'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom de la structure'),
      '#size' => 60,
      '#default_value' => !empty($labstructname) ? $labstructname[0]['value'] : '',
    ];
    $form['general_settings']['field_request_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Champs à récupérer'),
      '#size' => 60,
      '#default_value' => !empty($request_fields) ? $request_fields[0]['value'] : '',
    ];

    $form["container"] = [
      "#type" => "container",
      '#attributes' => ['id' => 'footer-form-container']
    ];

    $form["container"]['actions'] = [
      '#type' => 'actions'
    ];

    $form['container']['actions']['add_item'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add document Type'),
      '#submit' => ['::add_request_item'],
      '#ajax'   => [
        'callback' => '::addRow_callback',
        'wrapper'  => 'footer-form-container',
      ],
    ];

    $form["container"]["publications"] = [
      '#type' => 'table',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    //Génération du formulaire
    for($i=0; $i < $nbRowsWithValue; $i++){
      $form['container']['publications'][$i]['#attributes']['class'][] = 'draggable';

      $form['container']['publications'][$i]["doctype"] = [
        '#type' => "select",
        '#title' => $this->t("Type de document"),
        '#options' => $this->_getDocTypes(),
        '#default_value' => $types[$i]["value"],
      ];
    }

    for($i=0; $i < $this->additionnal_rows; $i++){
      $form['container']['publications'][$nbRowsWithValue+$i]['#attributes']['class'][] = 'draggable';

      $form['container']['publications'][$nbRowsWithValue+$i]["doctype"] = [
        '#type' => "select",
        '#title' => $this->t("Type de document"),
        '#options' => $this->_getDocTypes(),
      ];
    }

    $form['container']['actions']["submit"] = [
      "#type" => "submit",
      '#value' => $this->t("Save")
    ];

    $form_state->setCached(false);
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var @var FieldItemList $values $values */
    $values = $form_state->getValue('publications');
    $publications = [];
    foreach ($values as $key => $value) {
      $publications[] =['value' => $value['doctype']];
    }

    $this->site->field_doctype = $publications;
    $this->site->field_labstructname_t = $form_state->getValue('field_labstructname_t');
    $this->site->field_request_fields = $form_state->getValue('field_request_fields');
    $this->site->save();

    \Drupal::messenger()->addMessage($this->t("Requests saved"));
  }

  function addRow_callback($form, $form_state) {
    return $form['container'];
  }

  function add_request_item(array &$form, FormStateInterface $form_state) {
    $this->additionnal_rows++;
    $form_state->setRebuild();
  }

  protected function _getDocTypes() {
    return [
      'ART' => 'Article dans une revue',
      'COMM' => 'Communication dans un congrès',
      'POSTER' => 'Poster de conférence',
      'PROCEEDINGS' => 'Proceedings/Recueil des communications',
      'ISSUE' => 'N°spécial de revue/special issue',
      'OUV' => 'Ouvrages',
      'COUV' => 'Chapitre d\'ouvrage',
      'BLOG' => 'Article de blog scientifique',
      'NOTICE' => 'Notice d’encyclopédie ou de dictionnaire',
      'TRAD' => 'Traduction',
      'PATENT' => 'Brevet',
      'OTHER' => 'Autre publication scientifique',
      'UNDEFINED' => 'Pré-publication, Document de travail',
      'REPORT' => 'Rapport',
      'THESE' => 'Thèse',
      'HDR' => 'HDR',
      'LECTURE' => 'Cours',
      'MEM' => 'Mémoire d\'étudiant',
      'IMG' => 'Image',
      'VIDEO' => 'Vidéo',
      'SON' => 'Son',
      'MAP' => 'Carte',
      'SOFTWARE' => 'Logiciel',
      'PRESCONF' => 'Document associé à des manifestations scientifiques',
      'CREPORT' => 'Chapitre de rapport',
      'ETABTHESE' => 'Thèse d\'établissement',
      'MEMLIC' => 'typdoc_MEMLIC',
      'NOTE' => 'Note de lecture',
      'OTHERREPORT' => 'Autre rapport, séminaire, workshop',
      'REPACT' => 'Rapport d\'activité',
      'SYNTHESE' => 'Notes de synthèse',
    ];
  }
}
