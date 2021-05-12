<?php
namespace Drupal\up1_pages_personnelles\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\up1_pages_personnelles\Controller\WsGroupsController;

/**
 * Class BulkPagesPersonnelles.
 *
 * A form for bulk import Pages Persos.
 */
class BulkPagesPersonnelles extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'bulk_pages_persos_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['intro'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Use this form to create a "Enseignant-chercheur ou doctorant" user with all Typo3.'),
      '#suffix' => '</p>',
    ];
    $form['uid_ldap'] = [
      '#type' => 'textarea',
      '#title' => $this->t('uid ldap'),
      '#required' => TRUE,
      '#default_value' => '',
      '#description' => $this->t('Enter one uid per line.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create ldap users'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $uids_ldap = trim($form_state->getValue('uid_ldap'));
    $uids_ldap = preg_split('/[\n\r|\r|\n]+/', $uids_ldap);

    $operations = [];
    foreach ($uids_ldap as $uid_ldap) {
      $uid_ldap = trim($uid_ldap);
      if (!empty($uid_ldap)) {
        $operations[] = [
          '\Drupal\up1_pages_personnelles\Form\BulkPagesPersonnelles::createPagePerso',
          [$uid_ldap],
        ];
      }
    }

    $batch = [
      'title' => $this->t('Creating Pages Persos...'),
      'operations' => $operations,
      'finished' => '\Drupal\up1_pages_personnelles\Form\BulkPagesPersonnelles::createPagePersoFinished',
      'progress_message' => $this->t('Processed @current out of @total.'),
    ];

    batch_set($batch);
  }

  /**
   * Perform a single Page perso creation batch operation.
   *
   * Callback for batch_set().
   *
   * @param $uid_ldap
   * @param array $context
   *   The batch context array, passed by reference.
   */
  public static function createPagePerso($uid_ldap, array &$context) {
    $user = user_load_by_name($uid_ldap);
    if ($user) {
      $author = $user->id();

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'page_personnelle')
        ->condition('uid', $author);
      $nids = $query->execute();
      $ws = new WsGroupsController(\Drupal::service('up1_pages_personnelles.database'),\Drupal::service('up1_pages_personnelles.wsgroups'));
      $data_typo3 = $ws->createMissingPagePerso($uid_ldap);

      $pages_perso = Node::loadMultiple($nids);
      if (!empty($pages_perso)) {
        foreach ($pages_perso as $node) {
          try {
            $node->field_other_email_address = $data_typo3->tx_oxcspagepersonnel_courriel;
            $node->field_scientific_resp = $data_typo3->tx_oxcspagepersonnel_responsabilites_scientifiques;
            $node->field_thesis_subject = $data_typo3->tx_oxcspagepersonnel_sujet_these;
            $node->field_research_themes = [
              'value' => $data_typo3->tx_oxcspagepersonnel_themes_recherche . "<br />" .
                $data_typo3->tx_oxcspagepersonnel_projets_recherche,
              'format' => 'full_html'
            ];
            $node->field_phd_supervisor = $data_typo3->tx_oxcspagepersonnel_directeur_these;
            if (isset($data_typo3->tx_oxcspagepersonnel_publications) && !empty($data_typo3->tx_oxcspagepersonnel_publications)) {
              $publications = $data_typo3->tx_oxcspagepersonnel_publications;
              $publications = preg_replace("/<h3\s(.+?)>(.+?)<\/h3>/is", "<h5>$2</h5>", $publications);
              $publications = preg_replace("/<h2\s(.+?)>(.+?)<\/h2>/is", "<h4>$2</h4>", $publications);
              $publications = preg_replace("/<h1\s(.+?)>(.+?)<\/h1>/is", "<h3>$2</h3>", $publications);
              $node->field_publications = [
                'value' => "<div>" . $publications . "</div>",
                'format' => 'full_html'
              ];
            }
            if (isset($data_typo3->tx_oxcspagepersonnel_cv2) && !empty($data_typo3->tx_oxcspagepersonnel_cv2)) {
              $resume = $data_typo3->tx_oxcspagepersonnel_cv2;
              $resume = preg_replace("/<h3\s(.+?)>(.+?)<\/h3>/is", "<h5>$2</h5>", $resume);
              $resume = preg_replace("/<h2\s(.+?)>(.+?)<\/h2>/is", "<h4>$2</h4>", $resume);
              $resume = preg_replace("/<h1\s(.+?)>(.+?)<\/h1>/is", "<h3>$2</h3>", $resume);
              $node->field_resume_text = [
                'value' => "<div>" . $resume . "</div>",
                'format' => 'full_html'
              ];
            }
            $node->field_thesis_directions = $data_typo3->tx_oxcspagepersonnel_directions_these;
            $node->field_other_page_perso = $data_typo3->tx_oxcspagepersonnel_page_externe_url;
            if (isset($data_typo3->tx_oxcspagepersonnel_page_externe_url) && !empty($data_typo3->tx_oxcspagepersonnel_page_externe_url)) {
              if (!filter_var($data_typo3->tx_oxcspagepersonnel_page_externe_url, FILTER_VALIDATE_URL)) {
                $url = Url::fromUri($data_typo3->tx_oxcspagepersonnel_page_externe_url);
                $node->field_link_to_resume = $url->toString();
              }
            }
            if (isset($data_typo3->tx_oxcspagepersonnel_cv) && !empty($data_typo3->tx_oxcspagepersonnel_cv)) {
              $url = Url::fromUri("https://www.pantheonsorbonne.fr/uploads/pics/" . $data_typo3->tx_oxcspagepersonnel_cv);
              $node->field_link_to_resume = $url->toString();
            }

            $node->field_publications = [
              'value' => "<div>" . $publications . "</div>",
              'format' => 'full_html'
            ];
            $node->field_my_hal_publications = [
              'value' => 'nul',
            ];

            $node->site_id = NULL;
            $node->save();
          } catch (\Exception $e) {
            \Drupal::logger('bulk_pages_persos')->error("@code : @Message", [$e->getCode(), $e->getMessage()]);
            $context['results']['messages']['errors'][] = $uid_ldap;
          }
        }
      }
    }
  }

  /**
   * Complete CAS user creation batch process.
   *
   * Callback for batch_set().
   *
   * Consolidates message output.
   */
  public static function createPagePersoFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['messages']['errors'])) {
        $messenger->addError(t(
          'An error was encountered creating accounts for the following pages persos :(check logs for more details): %uid_ldap',
          ['%uid_ldap' => implode(', ', $results['messages']['errors'])]
        ));
      }
      if (!empty($results['messages']['already_exists'])) {
        $messenger->addError(t(
          'The following accounts were not registered because existing accounts are already using the usernames: %uid_ldap',
          ['%uid_ldap' => implode(', ', $results['messages']['already_exists'])]
        ));
      }
      if (!empty($results['messages']['created'])) {
        $userLinks = Markup::create(implode(', ', $results['messages']['created']));
        $messenger->addStatus(t(
          'Successfully created accounts for the following usernames: %uid_ldap',
          ['%uid_ldap' => $userLinks]
        ));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addError(t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]));
    }
  }
}
