<?php

namespace Drupal\cmis_extensions\Tree;

abstract class Kind {
  const MemberList = 0;    // Liste des membres
  const Deliberation = 1;  // Délibération
  const Record = 2;        // Relevé de décision
  const Statute = 3;       // Statut
  const Decree = 4;        // Arrêté
  const Regulations = 5;   // Réglement
  const Statement = 6;     // Relevé d'avis
}
