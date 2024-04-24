<?php
namespace Drupal\micro_multilingue;


interface LanguageValidatorInterface
{
  public function isAvailableLanguage ();

  public function getAvailableLanguageIds();
}
