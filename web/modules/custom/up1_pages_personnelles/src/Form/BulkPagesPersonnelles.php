<?php
namespace Drupal\up1_pages_personnelles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BulkPagesPersonnelles.
 *
 * A form for bulk import Pages Persos.
 */
class BulkPagesPersonnelles extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'up1_pages_personnelles.bulk_import',
    ];
  }
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId()
  {
    return 'bulk_pages_persos_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('up1_pages_personnelles.bulk_import');

    $form['intro'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Use this form to create a "Enseignant-chercheur ou doctorant" user with all Typo3.'),
      '#suffix' => '</p>',
    ];
    $form['uid_ldap'] = [
      '#type' => 'textarea',
      '#title' => $this->t('uid ldap'),
      '#required' => TRUE,
      '#default_value' => implode(PHP_EOL, $config->get('uid_ldap')),
      '#description' => $this->t('Enter one uid per line.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create ldap users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $uids_ldap = trim($form_state->getValue('uid_ldap'));
    $uids_ldap = preg_split('/[\n\r|\r|\n]+/', $uids_ldap);

    $this->config('up1_pages_personnelles.bulk_import')
      ->set('uid_ldap', $uids_ldap)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
}
