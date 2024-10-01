<?php

namespace Drupal\sorbonne_tv\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

class SorbonneTvConfigForm extends ConfigFormBase
{

    public function getFormId()
    {
        return 'sorbonne_tv_config_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);

        $config = $this->config('sorbonne_tv.settings');
        $api_mediatheque = $config->get('sorbonne_tv.settings.api_mediatheque');
        $api_visionnaire = $config->get('sorbonne_tv.settings.api_visionnaire');
        $api_flux_video = $config->get('sorbonne_tv.settings.api_flux_video');
        $programs = $config->get('sorbonne_tv.settings.programs');
        $favorites = $config->get('sorbonne_tv.settings.favorites');
        $contact = $config->get('sorbonne_tv.settings.contact');

        $form['api_mediatheque'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('API médiathèque'),
            '#tree' => TRUE,
        ];
        $form['api_mediatheque']['url'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Url'),
            '#default_value' => isset($api_mediatheque['url']) ? $api_mediatheque['url'] : '',
            '#required'      => TRUE,
        ];
        $form['api_mediatheque']['login'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Login'),
            '#default_value' => isset($api_mediatheque['login']) ? $api_mediatheque['login'] : '',
            '#required'      => TRUE,
        ];
        $form['api_mediatheque']['pwd'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Mot de passe'),
            '#default_value' => isset($api_mediatheque['pwd']) ? $api_mediatheque['pwd'] : '',
            '#required'      => TRUE,
        ];
        $form['api_mediatheque']['recipients_mail_after_sync'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Destinataire confirmation synchronisation'),
            '#default_value' => isset($api_mediatheque['recipients_mail_after_sync']) ? $api_mediatheque['recipients_mail_after_sync'] : '',
            '#required'      => TRUE,
            '#description'   => t('Pour renseigner plusieurs valeurs, vous pouvez séparer les adresses mail par une virgule.')
        ];

        $form['api_visionnaire'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('API visionnaire'),
            '#tree' => TRUE,
        ];
        $form['api_visionnaire']['url'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Url'),
            '#default_value' => isset($api_visionnaire['url']) ? $api_visionnaire['url'] : '',
            '#required'      => TRUE,
        ];

        $form['api_flux_video'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('flux vidéo'),
            '#tree' => TRUE,
        ];
        $form['api_flux_video']['url'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Url'),
            '#default_value' => isset($api_flux_video['url']) ? $api_flux_video['url'] : '',
            '#required'      => TRUE,
        ];

        $form['api_flux_video']['link1'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Link 1'),
            '#default_value' => isset($api_flux_video['link1']) ? $api_flux_video['link1'] : '',
            //'#required'      => TRUE,
        ];

        $form['api_flux_video']['link2'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Link 2'),
            '#default_value' => isset($api_flux_video['link2']) ? $api_flux_video['link2'] : '',
            //'#required'      => TRUE,
        ];

        $form['api_flux_video']['link3'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Link 2'),
            '#default_value' => isset($api_flux_video['link3']) ? $api_flux_video['link3'] : '',
            //'#required'      => TRUE,
        ];

        $form['programs'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Grille des programmes'),
            '#tree' => TRUE,
        ];
        $form['programs']['intro'] = [
            '#type'        => 'textarea',
            '#title'       => $this->t('Introduction'),
            '#default_value' => isset($programs['intro']) ? $programs['intro'] : '',
        ];
        $form['programs']['share_image'] = [
            '#type' => 'media_library',
            '#allowed_bundles' => ['image'],
            '#title' => t('Image de partage'),
            '#default_value' => isset($programs['share_image']) ? $programs['share_image'] : NULL,
            '#description' => t('Upload or select your image.'),
        ];
        /*$form['programs']['top_articles'] = [
            '#title' => $this->t('Les plus écoutés'),
            '#type' => 'entity_autocomplete',
            '#target_type' => 'node',
            '#selection_settings' => [
                'target_bundles' => ['page_sorbonne_tv'],
            ],
            '#tags' => TRUE,
            '#default_value' => $this->getDefaultEntitiesTopArticles($form_state, 'all'),
            '#attributes' => [
                'placeholder' => t('Select'),
                'data-disable-refocus' => "true"
            ],
            '#description' => $this->t('Sélectionnez jusqu\'à 5 valeurs séparées par des virgules.'),
            '#maxlength' => NULL,
            //'#element_validate' => [[get_class($this), 'validateEntityAutocompleteTopArticles']],
        ];*/
        $form['programs']['top_articles_wrapper'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Les plus écoutés'),
            '#tree' => TRUE,
        ];

        $nb_top = 5;
        for ($i = 1; $i <= $nb_top; $i++) {

            $form['programs']['top_articles_wrapper']['top_articles_'. $i] = [
                '#title' => $this->t('Top @item_num', ['@item_num' => $i]),
                '#type' => 'entity_autocomplete',
                '#target_type' => 'node',
                '#selection_settings' => [
                    'target_bundles' => ['page_sorbonne_tv'],
                ],
                '#default_value' => $this->getDefaultEntitiesTopArticles($form_state, $i),
                '#attributes' => [
                    'placeholder' => t('Select'),
                    'data-disable-refocus' => "true"
                ],
                '#maxlength' => NULL,
            ];

        }

        $form['favorites'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Favoris'),
            '#tree' => TRUE,
        ];
        $form['favorites']['intro'] = [
            '#type'        => 'textarea',
            '#title'       => $this->t('Introduction'),
            '#default_value' => isset($favorites['intro']) ? $favorites['intro'] : '',
        ];
        $form['favorites']['share_image'] = [
            '#type' => 'media_library',
            '#allowed_bundles' => ['image'],
            '#title' => t('Image de partage'),
            '#default_value' => isset($favorites['share_image']) ? $favorites['share_image'] : NULL,
            '#description' => t('Upload or select your image.'),
        ];

        $form['contact'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Page de contact'),
            '#tree' => TRUE,
        ];
        $form['contact']['intro'] = [
            '#type'        => 'textarea',
            '#title'       => $this->t('Introduction'),
            '#default_value' => isset($contact['intro']) ? $contact['intro'] : '',
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Helper function to get default values for the entity autocomplete.
     */
    protected function getDefaultEntitiesTopArticles(FormStateInterface $form_state, $item_num) {
        $def_top_articles = \Drupal::service('sorbonne_tv.videos_service')->getTopVideos($item_num);

        return $def_top_articles;
    }

    /**
     * Validate the entity autocomplete field to limit to 5 items.
     */
    /*
    public static function validateEntityAutocompleteTopArticles(array &$element, FormStateInterface $form_state, array &$complete_form) {
        $values = $form_state->getValue($element['#parents']);
        if (count($values) > 5) {
            $form_state->setError($element, t('Vous ne pouvez sélectionner que 5 articles maximum'));
        }
    }*/

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $config = $this->config('sorbonne_tv.settings');
        $config->set('sorbonne_tv.settings.api_mediatheque', $form_state->getValue('api_mediatheque'));
        $config->set('sorbonne_tv.settings.api_visionnaire', $form_state->getValue('api_visionnaire'));
        $config->set('sorbonne_tv.settings.api_flux_video', $form_state->getValue('api_flux_video'));
        $config->set('sorbonne_tv.settings.programs', $form_state->getValue('programs'));
        $config->set('sorbonne_tv.settings.favorites', $form_state->getValue('favorites'));
        $config->set('sorbonne_tv.settings.contact', $form_state->getValue('contact'));
        $config->save();

        return parent::submitForm($form, $form_state);
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'sorbonne_tv.settings',
            'sorbonne_tv.settings.api_mediatheque',
            'sorbonne_tv.settings.api_visionnaire',
            'sorbonne_tv.settings.api_flux_video',
            'sorbonne_tv.settings.programs',
            'sorbonne_tv.settings.favorites',
            'sorbonne_tv.settings.contact',
        ];
    }

}