<?php

namespace Drupal\cmis_extensions\Service;

use \Drupal\cmis_extensions\Tree\Kind;

class NomenclaturesProvider {
  public function document_kinds() {
    return [
      Kind::MemberList => 'Listes des membres',
      Kind::Deliberation => 'Délibérations',
      Kind::Record => 'Relevés de décisions',
      Kind::Statute => 'Statuts',
      Kind::Decree => 'Arrêtés',
      Kind::Regulations => 'Règlements',
      Kind::Statement => 'Relevés d\'avis'
    ];
  }

  public function filter_years() {
    return range(date("Y"), 2012, -1);
  }
}
