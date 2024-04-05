<?php
namespace Drupal\micro_multilingue;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\micro_multilingue\LanguageValidatorInterface;

class LanguageValidator implements LanguageValidatorInterface
{

  protected $languageManager;
  protected $negotiator;
  protected $configFactory;


  public function __construct(LanguageManagerInterface $languageManager, SiteNegotiatorInterface $negotiator,
                             ConfigFactoryInterface $configFactory) {
    $this->languageManager = $languageManager;
    $this->negotiator = $negotiator;
    $this->configFactory = $configFactory;
  }

  public function isAvailableLanguage (){
    $active_language = $this->languageManager->getCurrentLanguage();
    $availableLanguage = $this->getAvailableLanguageIds();
    if (empty($availableLanguage)) {
      $availableLanguage[] = $active_language->getID();
    }
    return in_array($active_language->getID(), $availableLanguage, true);
  }

  public function getAvailableLanguageIds() {
    $availableLanguage = [];
    $site = $this->negotiator->getActiveSite();
    if ($site instanceof SiteInterface) {
      foreach ($site->get('active_language') as $language) {
        $availableLanguage[] = $language->entity->getID();
      }
    }
    elseif($this->negotiator->isHostUrl()){
      $availableLanguage = array_values(\Drupal::config('micro_multilingue.settings')->get('host_active_language'));
    }
    return $availableLanguage;
  }

}
