<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\up1_pages\Http\CircuitBreaker;
use Drupal\up1_pages\Http\WsClient;
use Drupal\up1_pages\Manager\HalPublications;
use Drupal\up1_pages\Manager\PagePersoSyncManager;
use Drupal\up1_pages\StatusReport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Réglages des pages personnelles et des web services.
 */
final class SettingsForm extends ConfigFormBase {

  private const SETTINGS = 'up1_pages.settings';

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    private readonly CircuitBreaker $breaker,
    private readonly DateFormatterInterface $dateFormatter,
    private readonly PagePersoSyncManager $syncManager,
    private readonly HalPublications $halPublications,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get(CircuitBreaker::class),
      $container->get('date.formatter'),
      $container->get(PagePersoSyncManager::class),
      $container->get(HalPublications::class),
    );
  }

  public function getFormId(): string {
    return 'up1_pages_settings';
  }

  protected function getEditableConfigNames(): array {
    return [self::SETTINGS];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('État des web services'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $rows = [];
    foreach ([WsClient::WSGROUPS, WsClient::HAL] as $service) {
      $rows[] = [$service, $this->describe($this->breaker->status($service))];
    }
    $form['status']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Service'), $this->t('État')],
      '#rows' => $rows,
    ];
    $form['status']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Réactiver les appels maintenant'),
      '#submit' => ['::resetCircuits'],
      '#limit_validation_errors' => [],
    ];

    // Comptes enregistrés à la dernière synchro : aucun appel aux web services.
    $form['counts'] = [
      '#type' => 'details',
      '#title' => $this->t('Comptes'),
      '#open' => TRUE,
      '#weight' => -9,
    ];
    $sections = StatusReport::sections(
      $this->syncManager->getStatistics(),
      $this->halPublications->statistics(),
      fn(int $timestamp) => $this->dateFormatter->format($timestamp, 'custom', 'd/m/Y H:i'),
    );
    foreach (array_values($sections) as $index => $rows) {
      $form['counts']["section_$index"] = [
        '#type' => 'table',
        '#caption' => array_keys($sections)[$index],
        '#rows' => $rows,
      ];
    }

    $form['maintenance'] = [
      '#type' => 'details',
      '#title' => $this->t('Maintenance'),
      '#open' => TRUE,
    ];
    $form['maintenance']['activate_maintenance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer le mode maintenance'),
      '#description' => $this->t('Empêcher la modification des pages personnelles en activant le mode maintenance.'),
      '#config_target' => self::SETTINGS . ':activate_maintenance',
    ];

    $form['availability'] = [
      '#type' => 'details',
      '#title' => $this->t('Disponibilité des web services'),
      '#description' => $this->t("Si un web service ne répond plus, ses appels sont suspendus et les pages affichent les dernières données connues. Décocher un service le neutralise complètement."),
      '#open' => TRUE,
    ];
    $form['availability']['wsgroups_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Appeler wsgroups'),
      '#config_target' => self::SETTINGS . ':ws.wsgroups_enabled',
    ];
    $form['availability']['hal_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Appeler HAL'),
      '#config_target' => self::SETTINGS . ':ws.hal_enabled',
    ];
    $numbers = [
      'failure_threshold' => [$this->t("Nombre d'échecs avant suspension des appels"), 1],
      'cooldown' => [$this->t('Durée de suspension des appels (secondes)'), 30],
      'connect_timeout' => [$this->t('Délai de connexion (secondes)'), 1],
      'timeout' => [$this->t("Délai de réponse, fiche d'une personne (secondes)"), 1],
      'list_timeout' => [$this->t('Délai de réponse, trombinoscopes (secondes)'), 1],
      'sync_timeout' => [$this->t('Délai de réponse, synchronisation (secondes)'), 1],
      'cache_ttl' => [$this->t('Durée de fraîcheur des données (secondes)'), 60],
      'stale_ttl' => [$this->t('Conservation des dernières données connues en cas de panne (secondes)'), 3600],
    ];
    foreach ($numbers as $key => [$title, $min]) {
      $form['availability'][$key] = [
        '#type' => 'number',
        '#title' => $title,
        '#min' => $min,
        '#required' => TRUE,
        '#config_target' => self::SETTINGS . ":ws.$key",
      ];
    }

    $form['wsgroups'] = [
      '#type' => 'details',
      '#title' => $this->t('wsgroups'),
      '#open' => TRUE,
    ];
    $form['wsgroups']['url_ws'] = [
      '#type' => 'url',
      '#title' => $this->t('URL du web service WSGROUPS'),
      '#description' => $this->t('Doit être du type "https://wsgroups.univ-paris1.fr/"'),
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':url_ws',
    ];
    $form['wsgroups']['search_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recherche initiale (listes)'),
      '#description' => $this->t('Doit être du type "searchUser, searchUserTrusted etc., avec filtre si nécessaire."'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':search_user',
    ];
    $form['wsgroups']['filtre_faculty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filtre(s) à appliquer pour récupérer les enseignants-chercheurs'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':filtre_faculty',
    ];
    $form['wsgroups']['filtre_student'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filtre(s) à appliquer pour récupérer les doctorants'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':filtre_student',
    ];
    $form['wsgroups']['other_filters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Filtre(s) supplémentaire(s) à appliquer'),
      '#description' => $this->t('cf. https://github.com/UnivParis1/wsgroups#usergroupsandroles'),
      '#config_target' => self::SETTINGS . ':other_filters',
    ];
    $form['wsgroups']['search_user_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Recherche initiale (fiche d'une personne)"),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':search_user_page',
    ];
    $form['wsgroups']['url_userphoto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du web service userphoto'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':url_userphoto',
    ];

    $form['hal'] = [
      '#type' => 'details',
      '#title' => $this->t('HAL'),
      '#open' => TRUE,
    ];
    $form['hal']['url_hal_api'] = [
      '#type' => 'url',
      '#title' => $this->t('URL of the web service HAL'),
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':url_hal_api',
    ];
    $form['hal']['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalle entre deux rafraîchissements des publications (secondes)'),
      '#description' => $this->t("Les pages affichent l'encart enregistré, sans appeler HAL. Le cron le remplace seulement si HAL répond une liste valide et différente."),
      '#min' => 3600,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':hal.refresh_interval',
    ];

    $form['sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Synchronisation avec wsgroups'),
      '#open' => FALSE,
    ];
    $form['sync']['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalle entre deux synchronisations par le cron (secondes)'),
      '#min' => 3600,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':sync.interval',
    ];
    $form['sync']['min_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalle minimal entre deux synchronisations demandées par URL (secondes)'),
      '#min' => 0,
      '#required' => TRUE,
      '#config_target' => self::SETTINGS . ':sync.min_interval',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function resetCircuits(array &$form, FormStateInterface $form_state): void {
    foreach ([WsClient::WSGROUPS, WsClient::HAL] as $service) {
      $this->breaker->reset($service);
    }
    $this->messenger()->addStatus($this->t('Les appels aux web services sont réactivés.'));
  }

  private function describe(array $status): string {
    if (!$status['enabled']) {
      return (string) $this->t('Désactivé dans les réglages : aucun appel.');
    }
    if ($status['open']) {
      return (string) $this->t('Suspendu jusqu’à @until après @nb échecs (@reason).', [
        '@until' => $this->dateFormatter->format($status['open_until'], 'custom', 'H:i:s'),
        '@nb' => $status['failures'],
        '@reason' => $status['reason'],
      ]);
    }
    if ($status['failures']) {
      return (string) $this->t('Disponible, @nb échec(s) récent(s) (@reason).', [
        '@nb' => $status['failures'],
        '@reason' => $status['reason'],
      ]);
    }

    return (string) $this->t('Disponible.');
  }

}
