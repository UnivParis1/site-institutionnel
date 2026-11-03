<?php

namespace Drupal\micro_cas_affiliation\Subscriber;


use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class MicroCasAffiliationSubscriber.
 */
class MicroCasAffiliationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Fiche wsgroups (module up1_pages), NULL s'il n'est pas installé.
   *
   * @var \Drupal\up1_pages\Manager\ComptexManager|null
   */
  protected $comptex;

  /**
   * Constructs a new MicroCasAffiliationSubscriber object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, $comptex = NULL) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->comptex = $comptex;
  }

  /**
   * La personne a-t-elle droit à une page personnelle dans wsgroups ?
   *
   * Réponse immédiate si wsgroups est indisponible (dernière réponse connue,
   * à défaut FALSE) : la connexion n'attend jamais le web service.
   */
  protected function hasPagePerso(AccountInterface $account): bool {
    return $this->comptex && $this->comptex->userHasPagePerso($account->getAccountName());
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_LOGIN][] = ['affectationRoleetMiniSite', -10];
    //$events[CasHelper::EVENT_PRE_LOGIN][] = ['checkLogin', -20];
    
    return $events;
  }

  /**
   * @param \Drupal\cas\Event\CasPreLoginEvent $casPreLoginEvent
   */
  public function checkLogin(CasPreLoginEvent $event){
    /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
    $negotiator = \Drupal::service('micro_site.negotiator');
    $configMicroUser = \Drupal::config('micro_user.settings');
    $account = $event->getAccount();
    if ($site = $negotiator->getActiveSite()) {
      if ($account instanceof AccountInterface) {
        $site_users = $site->getAllUsersId();
        if (!in_array($account->id(), $site_users)) {
          $event->cancelLogin($this->t('Unrecognized username on this site.'));
        }
      }
    }
    else {
      if ($account instanceof AccountInterface) {
        if (in_array('enseignant_doctorant', $account->getRoles()) && $this->hasPagePerso($account)) {
          return;
        }

        if ($account->hasPermission('administer site entities')) {
          return;
        }
        if ($configMicroUser->get('prevent_login_master_host') && !$account->hasPermission('login master host')) {
          $event->cancelLogin($this->t('Unrecognized username on this site.'));
        }
      }
    }
  }

  /**
   * @param \Drupal\cas\Event\CasPreLoginEvent $casPreLoginEvent
   */
  public function affectationRoleetMiniSite(CasPreLoginEvent $casPreLoginEvent){
    // TODO verifier que l'evenement post login est declenché en cas de creation du user
    $account = $casPreLoginEvent->getAccount();
    $memberOf = $account->get('field_group');
    if (!empty($memberOf) && !empty($memberOf[0]->value)) {
      $CNs = explode('cn=', $memberOf[0]->value);
      // les cn sont de la forme cn=applications.www.webmestre.general,ou=groups,dc=univ-paris1,dc=fr
      // ou  cn=applications.www.redacteur.miniSite.ufr.sx5,ou=groups,dc=univ-paris1,dc=fr
      foreach ($CNs as $CN) {

        if (!empty($CN) && substr_compare($CN, 'applications', 0, 12, TRUE) == 0) {
          $part = explode('.', $CN);

          $role = $part[2];
          $minisite = in_array('miniSite', $part);
          $general = in_array('general', $part);
          // si on a miniSite dans le cn c'est qu'on veut affecter spécifiquement l'utilisateur a un mini site
          if ($minisite) {
            $group = explode(',', $part[5])[0];
            $siteStorage = \Drupal::entityTypeManager()->getStorage('site');
            // on recherche les sites dont le champ groups contient le code recupéré dans le cn
            $siteIds = $siteStorage->getQuery()
              ->condition('status', TRUE)
              ->accessCheck(FALSE)
              // TODO s'assurer qu'on ne recupere pas de "mauvais" groupes
              ->condition('groups', $group, 'CONTAINS')
              ->execute();
            $sites = $siteStorage->loadMultiple($siteIds);
            // TODO supprimer les roles pour les remplacer ?
            // pour chaque minisite on affecte un role drupal ainsi qu'un role pour le minisite
            switch ($role) {
              case 'redacteur':
                if (!empty($sites)) {
                  $account->addRole('contributeur_sous_site');
                  foreach ($sites as $site) {
                    $contributeurs = $site->get('field_site_contributor')->getValue();
                    if (!(in_array(['target_id' => $account->id()],$contributeurs))) {
                      $contributeurs[] = ['target_id' => $account->id()];
                      $site->set('field_site_contributor', $contributeurs);
                      $site->save();
                    }
                  }
                }
                break;
              case 'webmestre':
                if (!empty($sites)) {
                  $account->addRole('webmestre_sous_site');
                  foreach ($sites as $site) {
                    $webmestres = $site->get('field_site_administrator')->getValue();
                    if (!in_array(['target_id' => $account->id()], $webmestres)) {
                      $webmestres[] = ['target_id' => $account->id()];
                      $site->set('field_site_administrator', $webmestres);
                      $site->save();
                    }
                  }
                }
                break;
            }
          }
          elseif($general) {
            // on affecte le rôle listé dans le cn à l'utilisateur
            \Drupal::logger('cas_affiliation')->info("Cas général! " . print_r($role, 1));
            switch ($role) {
              case 'redacteur':
                $account->addRole('contributeur');
                break;
              case 'webmestre':
                $account->addRole('webmestre_general');
                break;
            }
          }
          $account->save();
        }
      }
    }
    else {
      if ($this->hasPagePerso($account)) {
        $account->addRole('enseignant_doctorant');
        $account->save();
      }
    }
  }
}
