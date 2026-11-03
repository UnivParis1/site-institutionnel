<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Manager;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\up1_pages\Cache\FallbackCache;
use Drupal\up1_pages\Exception\WsUnavailableException;
use Drupal\up1_pages\Http\WsClient;

/**
 * Fiche wsgroups (« Comptex ») d'une personne.
 *
 * Aucune méthode publique ne lève d'exception ni n'attend wsgroups plus que
 * ws.timeout : si le service est indisponible, on renvoie la dernière valeur
 * connue, à défaut une valeur vide. isAvailable() permet aux appelants qui
 * doivent décider (créer un compte…) de distinguer « vide » et « panne ».
 */
final class ComptexManager {

  private const SETTINGS = 'up1_pages.settings';

  private const INFORMATION_ATTRS = 'supannCivilite,displayName,sn,givenName,mail,supannEntiteAffectation-all,supannActivite,supannRoleEntite-all,info,employeeType,buildingName,telephoneNumber,postalAddress,labeledURI,eduPersonPrimaryAffiliation,supannMailPerso,supannConsentement,supannListeRouge';

  /**
   * Attributs wsgroups parfois renvoyés sous forme de tableau.
   */
  private const SINGLE_VALUED = [
    'telephoneNumber',
    'supannCivilite',
    'labeledURI',
    'displayName',
    'sn',
    'givenName',
    'mail',
    'supannActivite',
    'buildingName',
    'postalAddress',
    'eduPersonPrimaryAffiliation',
  ];

  private const ENTITY_ORDER = ['doctoralSchool', 'research', 'pedagogy'];

  /**
   * URL des mini-sites par groupe LDAP, pour la requête en cours.
   */
  private array $siteUrls = [];

  public function __construct(
    private readonly WsClient $wsClient,
    private readonly FallbackCache $cache,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  public function isAvailable(): bool {
    return $this->wsClient->isAvailable(WsClient::WSGROUPS);
  }

  /**
   * Fiche complète, mise en forme pour la page personnelle.
   *
   * @param bool|null $stale
   *   Mis à TRUE si la fiche n'est pas à jour (wsgroups indisponible).
   *
   * @return array
   *   Vide si la personne est inconnue ou si wsgroups n'a jamais répondu.
   */
  public function getUserInformation(string $username, ?bool &$stale = NULL): array {
    $raw = $this->lookup('person:' . $username, $username, [
      'attrs' => self::INFORMATION_ATTRS,
      'allowNoAffiliationAccounts' => 1,
      'showExtendedInfo' => 2,
    ], $stale);

    return $this->format($raw);
  }

  /**
   * Attributs bruts d'un enseignant-chercheur, sans cache.
   *
   * @return array
   *   Vide si la personne est inconnue ou wsgroups indisponible.
   */
  public function getUserAttributes(string $username, array $attributes = []): array {
    try {
      return $this->fetch($username, [
        'attrs' => implode(',', $attributes),
        'allowNoAffiliationAccounts' => 1,
        'showExtendedInfo' => 2,
      ], '&profile_eduPersonAffiliation=faculty');
    }
    catch (WsUnavailableException) {
      return [];
    }
  }

  /**
   * La personne a-t-elle droit à une page personnelle (labeledURI) ?
   *
   * FALSE si wsgroups est indisponible et que la réponse n'est pas connue.
   */
  public function userHasPagePerso(string $username): bool {
    $raw = $this->lookup('haspp:' . $username, $username, ['attrs' => 'labeledURI'], $stale, '&profile_eduPersonAffiliation=faculty', 300);

    return !empty($raw['labeledURI']);
  }

  public function getUserEmail(string $username): ?string {
    $raw = $this->lookup('mail:' . $username, $username, [
      'attrs' => 'mail,supannMailPerso,eduPersonPrincipalName',
      'allowNoAffiliationAccounts' => 1,
      'showExtendedInfo' => 2,
    ]);
    $mail = $raw['mail'] ?? NULL;
    if (is_array($mail)) {
      $mail = reset($mail);
    }

    return is_string($mail) && $mail !== '' ? $mail : NULL;
  }

  /**
   * Un attribut d'un enseignant-chercheur ; FALSE si inconnu.
   */
  public function getUserAttribute(string $username, string $attr): mixed {
    $raw = $this->lookup('attr:' . $username . ':' . $attr, $username, ['attrs' => $attr], $stale, '&profile_eduPersonAffiliation=faculty');

    return $raw[$attr] ?? FALSE;
  }

  /**
   * Résumé d'une personne pour l'autocomplétion de la recherche.
   */
  public function getUserSummary(string $username): array {
    return $this->lookup('summary:' . $username, $username, [
      'attrs' => 'employeeType,supannEntiteAffectation,supannActivite,eduPersonPrimaryAffiliation,departmentNumber',
    ]);
  }

  /**
   * Met en forme une fiche brute pour le gabarit de la page personnelle.
   */
  public function format(array $information): array {
    if (!$information) {
      return [];
    }

    foreach (self::SINGLE_VALUED as $attr) {
      if (isset($information[$attr]) && is_array($information[$attr])) {
        $information[$attr] = (string) (reset($information[$attr]) ?: '');
      }
    }

    if (!empty($information['uid'])) {
      $information['userPhoto'] = $this->configFactory->get(self::SETTINGS)->get('url_userphoto') . $information['uid'];
    }

    foreach ($information['supannRoleEntite-all'] ?? [] as $key => $role_entity) {
      $information['supannRole'][$key] = array_filter([
        'role' => $role_entity['role'] ?? NULL,
        'name' => $role_entity['structure']['name'] ?? NULL,
        'structure' => $role_entity['structure']['description'] ?? NULL,
        'labeledURI' => $role_entity['structure']['labeledURI'] ?? NULL,
      ], static fn($value) => $value !== NULL);
    }

    $is_student = ($information['eduPersonPrimaryAffiliation'] ?? '') === 'student';
    if ($is_student) {
      $information['employeeType'] = ($information['supannCivilite'] ?? '') === 'Mme' ? 'Doctorante' : 'Doctorant';
    }
    elseif (isset($information['employeeType']) && is_array($information['employeeType'])) {
      $information['employeeType'] = (string) (reset($information['employeeType']) ?: '');
    }

    $information['entites'] = $this->formatEntities($information['supannEntiteAffectation-all'] ?? [], $is_student);

    // Consentement pour apparaître dans l'annuaire de l'Observatoire de l'IA.
    $information['obsia'] = in_array('{PROJ:OBSIA}CGU', (array) ($information['supannConsentement'] ?? []), TRUE);

    return $information;
  }

  /**
   * Affectations (école doctorale, labo, UFR) en HTML, liens compris.
   */
  private function formatEntities(array $affectations, bool $is_student): string {
    $entities = [];
    foreach ($affectations as $affectation) {
      if (!is_array($affectation)) {
        continue;
      }
      $category = $affectation['businessCategory'] ?? '';
      // Pas d'UFR sur la page d'un doctorant.
      if ($is_student && $category === 'pedagogy') {
        continue;
      }
      $entities[] = [
        'category' => $category,
        'description' => (string) ($affectation['description'] ?? ''),
        'uri' => $affectation['labeledURI'] ?? $this->getSiteUrl((string) ($affectation['key'] ?? '')),
      ];
    }

    $rank = array_flip(self::ENTITY_ORDER);
    usort($entities, static fn($a, $b) => ($rank[$a['category']] ?? 99) <=> ($rank[$b['category']] ?? 99));

    $html = '';
    foreach ($entities as $entity) {
      $description = Html::escape($entity['description']);
      $html .= $entity['uri']
        ? "<p><a href='" . Html::escape(UrlHelper::stripDangerousProtocols($entity['uri'])) . "' title='$description' target='_blank'>$description</a></p>"
        : "<p>$description</p>";
    }

    return $html;
  }

  /**
   * Adresse du mini-site d'une structure, s'il existe.
   */
  private function getSiteUrl(string $group): string {
    if ($group === '') {
      return '';
    }
    if (!isset($this->siteUrls[$group])) {
      $storage = $this->entityTypeManager->getStorage('site');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'mini_site')
        ->condition('groups', $group)
        ->execute();
      $url = '';
      if (count($ids) === 1) {
        $site_url = $storage->load(reset($ids))?->get('site_url')->value;
        $url = $site_url ? '//' . $site_url : '';
      }
      $this->siteUrls[$group] = $url;
    }

    return $this->siteUrls[$group];
  }

  /**
   * Fiche en cache, rechargée si besoin ; vide si wsgroups est injoignable.
   */
  private function lookup(string $key, string $username, array $params, ?bool &$stale = NULL, string $filter = '', ?int $ttl = NULL): array {
    $stale = FALSE;
    try {
      return $this->cache->remember($key, fn() => $this->fetch($username, $params, $filter), $stale, $ttl);
    }
    catch (WsUnavailableException) {
      $stale = TRUE;
      return [];
    }
  }

  /**
   * Première fiche renvoyée par wsgroups pour un identifiant.
   *
   * @throws \Drupal\up1_pages\Exception\WsUnavailableException
   */
  private function fetch(string $username, array $params, string $filter = ''): array {
    $username = trim($username);
    if ($username === '') {
      return [];
    }
    $config = $this->configFactory->get(self::SETTINGS);
    $url = $config->get('url_ws') . $config->get('search_user_page')
      . '&id=' . rawurlencode($username) . $filter . '&' . http_build_query($params);

    $result = $this->wsClient->getJson(WsClient::WSGROUPS, $url);
    if (!is_array($result)) {
      throw new WsUnavailableException('wsgroups : réponse inattendue.', WsClient::WSGROUPS);
    }
    $first = reset($result);

    return is_array($first) ? $first : [];
  }

}
