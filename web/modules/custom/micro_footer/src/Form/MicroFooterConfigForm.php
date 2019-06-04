<?php
/**
 * Created by PhpStorm.
 * User: ede16590
 * Date: 29/05/2019
 * Time: 09:45
 */

namespace Drupal\micro_footer\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

class MicroFooterConfigForm extends FormBase
{

  protected $additionnal_rows = 0;

  protected $site;

  const FORMID = "MicroFooterConfigForm";

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId()
  {
    return self::FORMID;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site = NULL)
  {
    $this->site = $site;

    //Récupération des data
    $footerArray = $site->get("field_footer")->getValue();
    $nbRowsWithValue = count($footerArray);

    $form["container"] = [
      "#type" => "container",
      '#attributes' => ['id' => 'footer-form-container']
    ];

    $form["container"]['actions'] = [
      '#type' => 'actions'
    ];

    $form['container']['actions']['add_item'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add footer link'),
      '#submit' => ['::add_footer_item'],
      '#ajax'   => [
        // Could also use [ $this, 'colorCallback'].
        'callback' => '::add_footer_item_ajax_callback',
        'wrapper'  => 'footer-form-container', // CHECK THIS ID
      ],
    ];

    $form["container"]["footer_links"] = [
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
      $form["container"]['footer_links'][$i]['#attributes']['class'][] = 'draggable';

      $form["container"]["footer_links"][$i]["url"] = [
        '#type' => "url",
        '#title' => $this->t("URL"),
        '#default_value' => $footerArray[$i]["uri"],
      ];

      $form["container"]["footer_links"][$i]["text"] = [
        '#type' => "textfield",
        '#title' => $this->t("Link text"),
        '#default_value' => $footerArray[$i]["title"],
      ];

      $form["container"]['footer_links'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t("Weight"),
        '#title_display' => 'invisible',
        '#default_value' => $i,
        '#attributes' => [
          'class' => ['table-sort-weight',]
        ],
      ];
    }

    for($i=0; $i < $this->additionnal_rows; $i++){
      $form["container"]['footer_links'][$nbRowsWithValue+$i]['#attributes']['class'][] = 'draggable';

      $form["container"]["footer_links"][$nbRowsWithValue+$i]["url"] = [
        '#type' => "url",
        '#title' => $this->t("URL"),
      ];

      $form["container"]["footer_links"][$nbRowsWithValue+$i]["text"] = [
        '#type' => "textfield",
        '#title' => $this->t("Link text"),
      ];

      $form["container"]['footer_links'][$nbRowsWithValue+$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t("Weight"),
        '#title_display' => 'invisible',
        '#default_value' => $nbRowsWithValue+$i+1,
        '#attributes' => [
          'class' => ['table-sort-weight',]
        ],
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
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    /** @var FieldItemList $values $values */
    $values = $form_state->getValues()['footer_links'];
    $list = [];
    foreach($values as $key => $value){
      $list[] = ["uri" => $value['url'], "title" => $value['text']];
    }
    $this->site->field_footer = $list;
    $this->site->save();
    \Drupal::messenger()->addMessage($this->t("Footer element(s) saved"));
  }

  public function add_footer_item_ajax_callback($form, $form_state) {
    return $form['container'];
  }
  public function add_footer_item(array &$form, FormStateInterface $form_state) {
    $this->additionnal_rows++;
    $form_state->setRebuild();
  }

}