<?php

namespace Drupal\up1_keywords\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeywordsConfigForm.
 */
class KeywordsConfigForm extends ConfigFormBase {

  protected $additionnal_rows = 0;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array
  {
    return [
      'up1_keywords.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keywords_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config('up1_keywords.settings');
    $existing_keywords = $config->get('keywords_links');
    $nb_keywords = count($existing_keywords);

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'keywords-form-container']
    ];
    if ($this->additionnal_rows < 5) {
      $form['container']['actions'] = [
        '#type' => 'actions'
      ];
      $form['container']['actions']['add_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add keyword link'),
        '#submit' => ['::add_keyword_item'],
        '#ajax' => [
          'callback' => '::add_keyword_item_ajax_callback',
          'wrapper' => 'keywords-form-container',
        ],
      ];
    }
    $form['container']['keywords_links'] = [
      '#type' => 'table',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];
    for ($i = 0; $i < $nb_keywords; $i++) {
      $form["container"]['keywords_links'][$i]['#attributes']['class'][] = 'draggable';
      $form["container"]["keywords_links"][$i]["url"] = [
        '#type' => "url",
        '#title' => $this->t("URL"),
        '#default_value' => $existing_keywords[$i]["uri"],
      ];
      $form["container"]["keywords_links"][$i]["text"] = [
        '#type' => "textfield",
        '#title' => $this->t("Link text"),
        '#default_value' => $existing_keywords[$i]["title"],
      ];
      $form["container"]['keywords_links'][$i]['weight'] = [
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
      $form["container"]['keywords_links'][$nb_keywords+$i]['#attributes']['class'][] = 'draggable';
      $form["container"]["keywords_links"][$nb_keywords+$i]["url"] = [
        '#type' => "url",
        '#title' => $this->t("URL"),
      ];
      $form["container"]["keywords_links"][$nb_keywords+$i]["text"] = [
        '#type' => "textfield",
        '#title' => $this->t("Link text"),
      ];
      $form["container"]['keywords_links'][$nb_keywords+$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t("Weight"),
        '#title_display' => 'invisible',
        '#default_value' => $nb_keywords+$i+1,
        '#attributes' => [
          'class' => ['table-sort-weight',]
        ],
      ];
    }

   $form_state->setCached(false);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['keywords_links'];
    \Drupal::logger('up1_keywords_values')->info(print_r($values,1));
    $list = [];
    foreach($values as $key => $value){
      $list[] = ["uri" => $value['url'], "title" => $value['text']];
    }
    $this->config('up1_keywords.settings')
      ->set('keywords_links', $list)
      ->save();
    \Drupal::messenger()->addMessage($this->t("Keywords element(s) saved"));
  }

  /**
   * Function add_keyword_item_ajax_callback().
   *
   * @returns array $form.
   */
  public function add_keyword_item_ajax_callback($form, $form_state) {
    return $form['container'];
  }

  /**
   * Function add_keyword_item
   */
  public function add_keyword_item(array &$form, FormStateInterface $form_state) {
    if ($this->additionnal_rows < 6) {
      $this->additionnal_rows++;
      $form_state->setRebuild();
    }
  }
}
