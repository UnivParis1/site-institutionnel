<?php

namespace Drupal\micro_publications\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

class MicroPublicationsConfig extends FormBase {
  protected $additionnal_rows = 0;
  protected $site;

  const FORMID = "MicroPublicationsConfig";

  public function getFormId() {
    return self::FORMID;
  }


  public function buildForm(array $form, FormStateInterface $form_state, $site = NULL) {
    $this->site = $site;

    $request = $site->get("field_publications_request")->getValue();
    $nbRowsWithValue = count($request);

    $form['labStructName_t'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom de la structure'),
      '#size' => 60,
      '#default_value' => $form_state->get('labStructName_t') ? $form_state->get('labStructName_t') : '',
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'publications-form-container']
    ];
    $form['container']['add'] = [
      '#type' => 'button',
      '#value' => $this->t('Ajouter une récupération de publications'),
      '#submit' => ['::add_publication_item'],
      '#ajax' => [
        'callback' => '::addRow_callback',
        'wrapper' => 'publications-form-container',
      ],
    ];
    $form['container']['actions'] = [
      '#type' => 'actions',
    ];

    $form['container']['publications'] = [
      '#type' => 'table',
      '#tabledrap' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ]
      ]
    ];

    for ($i = 0; $i < $this->additionnal_rows; $i++) {
      $form['container']['publications'][$nbRowsWithValue+$i]['#attributes']['class'][] = 'draggable';
      //$form['container']['publications'][$i]
      $form['container']['publications'][$nbRowsWithValue+$i]['docType_s'] = [
        '#type' => 'select',
        '#title' => $this->t('Type de document'),
        '#options' => _getDocTypes(),
        '#default_value' => $request[$nbRowsWithValue+$i]['docType_s'],
      ];
      $form['container']['publications'][$nbRowsWithValue+$i]['rows'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Nombre de publications à récupérer. 0 pour tout récupérer.'),
        '#size' => 60,
        '#default_value' => $request[$nbRowsWithValue+$i]['rows'],
      ];
    }
    $form["container"]['actions']["submit"] = [
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
    $values = $form_state->getValues()['publications'];
    $publications = [];
    foreach ($values as $key => $value) {
      $publications[] = [
        'docType_s' => $value['docType_s'],
        'rows' => $value['rows'],
      ];
    }
    $this->site->set('field_publications_request', $publications);
    $this->site->set('labStructName_t', $publications);
    $this->site->save();

    \Drupal::messenger()->addMessage($this->t("Publications element(s) saved"));
    $config = $this->config('up1_publications.settings');

    $config->save();

    parent::submitForm($form, $form_state);
  }

  function addRow_callback($form, $form_state) {
    return $form['container'];

  }
  function add_publication_item(array &$form, FormStateInterface $form_state) {
    $this->additionnal_rows++;
    $form_state->setRebuild();
  }

  protected function getEditableConfigNames() {
    return ['up1_publications.settings'];
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
