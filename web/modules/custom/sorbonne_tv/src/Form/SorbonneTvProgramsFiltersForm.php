<?php

namespace Drupal\sorbonne_tv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

class SorbonneTvProgramsFiltersForm extends FormBase
{

    public function getFormId()
    {
        return 'sorbonne_tv_programs_filters_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#attached']['library'][] = 'sorbonne_tv/programs';

        // Get programs files
        $private_files_folder_path = \Drupal::service('file_system')->realpath("private://");
        $json_files_folder = $private_files_folder_path .'/sorbonne-tv/programmes';
        $jsonFiles = glob($json_files_folder . '/*.json');
        $days = [];

        foreach($jsonFiles as $k => $val) {
            $opt_val = str_replace([$json_files_folder.'/', '.json'], ['', ''], $val);

            $opt_tstp = strtotime($opt_val);
            $opt_ddt = DrupalDateTime::createFromTimestamp($opt_tstp);
            $opt = $opt_ddt->format('l j F Y');

            $days[$opt_val] = $opt;
        }

        $SelectedDay = \Drupal::request()->query->get('date');
        $SelectedPeriod = \Drupal::request()->query->get('period');
        //$SelectedPeriodArr = explode('+', $SelectedPeriod);

        $form['day'] = [
            '#type' => 'select',
            '#options' => $days,
            '#default_value' => (isset($SelectedDay) && (!empty($SelectedDay)) ? $SelectedDay : []),
            '#title' => t('Choose'),
            '#title_display' => 'invisible',
        ];

        $period_opts = [
            'morning' => t('Morning'),
            'noon' => t('Noon'),
            'evening' => t('Evening'),
            'night' => t('Night'),
        ];
        /*
        $form['period'] = [
            '#type' => 'checkboxes',
            '#options' => $period_opts,
            '#title' => $this->t('Période'),
            '#title_display' => 'invisible',
            '#default_value' => (isset($SelectedPeriodArr) && (!empty($SelectedPeriodArr)) ? $SelectedPeriodArr : []),
        ];
        */
        $form['period'] = [
            '#type' => 'radios',
            '#options' => $period_opts,
            '#title' => $this->t('Période'),
            '#title_display' => 'invisible',
            '#default_value' => (isset($SelectedPeriod) && (!empty($SelectedPeriod)) ? $SelectedPeriod : []),
            '#attributes' => [
                'class' => ['form-radios'],
            ],
        ];

        $form['actions'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['form-actions', 'hidden'],
            ],
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Search'),
            '#attributes' => [
                'class' => ['form-submit'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $redirectToUrl = FALSE;
        $selected_date = $form_state->getValue('day');
        $selected_period = $form_state->getValue('period');

        /* // Cas des checkboxes
        $selected_period_arr = array_values($selected_period);
        $period_str_filter = '';
        foreach($selected_period_arr as $period_k => $period_val) {
            if($period_val != 0) {
                $period_str_filter .= (!empty($period_str_filter) ? '+' : '') . $period_val;
            }
        }
        */

        $url_options = [
            'query' => [
                'date' => $selected_date,
            ],
        ];

        if($selected_period && !empty($selected_period)) {
            $url_options['query']['period'] = $selected_period;
        }

        $redirectToUrl = Url::fromRoute('sorbonne_tv.grille_programmes', [], $url_options);
        $form_state->setRedirectUrl($redirectToUrl);
    }

}