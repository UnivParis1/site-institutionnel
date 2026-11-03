<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Hook;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Champs ajoutés aux mini-sites : groupe LDAP et pages « Équipe ».
 *
 * Mêmes définitions que dans up1_pages_personnelles, dont ce module reprend
 * le stockage à l'installation (voir up1_pages_install()).
 */
final class SiteFields {

  /**
   * Groupes du formulaire de mini-site, par champ.
   */
  public const FORM_GROUPS = [
    'groups' => 'pages_personnelles',
    'supannConsentement' => 'pages_personnelles',
    'ec_enabled' => 'staff_ec',
    'trombi_ec_enable' => 'staff_ec',
    'supannEntite_pedagogy' => 'staff_ec',
    'supannEntite_research' => 'staff_ec',
    'discipline_enseignement' => 'staff_ec',
    'skills_lists' => 'staff_ec',
    'supannRole' => 'staff_ec',
    'about_me' => 'staff_ec',
    'doc_enabled' => 'staff_doc',
    'trombi_students_enable' => 'staff_doc',
    'display_viva_subject' => 'staff_doc',
    'display_viva_director' => 'staff_doc',
    'display_doctoral_school' => 'staff_doc',
  ];

  /**
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   */
  public static function definitions(): array {
    $fields = [
      'groups' => self::string(
        new TranslatableMarkup('LDAP Group(s)'),
        new TranslatableMarkup('List of LDAP Groups that can access to the site. Separated by ; (Useful for CAS connection)'),
      ),
      'supannConsentement' => self::string(
        new TranslatableMarkup('supannConsentement'),
        new TranslatableMarkup('Value of supannConsentement field in ldap for this grouper group.'),
      ),
    ];

    $booleans = [
      'ec_enabled' => ['Enable staff page Enseignants-Chercheurs. ', 'Enables the list of all "Enseignants-Chercheurs" working in this structure. '],
      'doc_enabled' => ['Enable staff page Doctorants. ', 'Enables the list of all "Doctorants" working in this structure. '],
      'trombi_ec_enable' => ['Enable trombinoscope for EC', 'List of all ECs displayed as trombinoscope.'],
      'supannEntite_pedagogy' => ['Show "supannEntite pedagogy" on trombinoscope', 'See label of the "UFR" on trombinoscope card.'],
      'supannEntite_research' => ['Show "supannEntite research" on trombinoscope', 'See label of the "labo de rattachement" on trombinoscope card.'],
      'discipline_enseignement' => ['Show "discipline d\'enseignement" on trombinoscope', 'See "discipline d\'enseignement" on trombinoscope card.'],
      'skills_lists' => ['Show "skills" on trombinoscope', 'See "skills" list on trombinoscope card.'],
      'supannRole' => ['Show "supannRole" on trombinoscope', 'See fonction on trombinoscope card.'],
      'about_me' => ['Show "about me" on trombinoscope', 'See about me section on trombinoscope card.'],
      'trombi_students_enable' => ['Enable trombinoscope for students', 'List of all students displayed as trombinoscope.'],
      'display_viva_subject' => ['Display thesis subject', 'Display the subject of student\'s thesis'],
      'display_viva_director' => ['Display thesis supervisor\'s name', 'Display thesis supervisor\'s name.'],
      'display_doctoral_school' => ['Display doctoral school label', 'Display doctoral school label.'],
    ];
    foreach ($booleans as $name => [$label, $description]) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $fields[$name] = BaseFieldDefinition::create('boolean')
        ->setLabel(new TranslatableMarkup($label))
        ->setDescription(new TranslatableMarkup($description))
        ->setDefaultValue(FALSE)
        ->setRevisionable(TRUE)
        ->setDisplayOptions('form', ['weight' => 100])
        ->setDisplayConfigurable('form', TRUE);
    }

    return $fields;
  }

  private static function string(TranslatableMarkup $label, TranslatableMarkup $description): BaseFieldDefinition {
    return BaseFieldDefinition::create('string')
      ->setLabel($label)
      ->setDescription($description)
      ->setRevisionable(FALSE)
      ->setSettings(['text_type' => 'text'])
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE);
  }

}
