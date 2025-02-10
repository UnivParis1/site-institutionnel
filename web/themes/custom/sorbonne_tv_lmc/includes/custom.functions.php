<?php

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Views;

function detectFileType($url) {
  $pathInfo = pathinfo($url);
  $result = FALSE;

  // Vérifier l'extension du fichier
  if (isset($pathInfo['extension'])) {
    $extension = strtolower($pathInfo['extension']); // Convertir en minuscule pour uniformité
    
    if ($extension === 'mp3') {
      //return "Il s'agit d'un fichier MP3.";
      $result = 'audio';
    } elseif ($extension === 'mp4') {
      //return "Il s'agit d'un fichier MP4.";
      $result = 'video';
    }
  }

  return $result;
}