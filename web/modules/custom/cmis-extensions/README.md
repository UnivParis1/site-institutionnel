# CMIS Extensions

Module Drupal permettant d'ajouter de nouvelles fonctionnalités en
plus du module CMIS en se basant sur l'API Nuxeo :

* Recherche avancée
* Bloc permettant d'afficher les derniers éléments d'un dossier
* Extension Twig pour parser les tailles de fichiers

## Installation

Télécharger une archive du dépôt courant et l'extraire dans le dossier
`modules/custom` de l'installation Drupal concernée.

Ce module requiert :

* PHP 7.3 ou plus
* Drupal 9 ou plus

## Développement

Pour lancer les tests localement, il faut installer Composer puis
lancer :

~~~bash
$ composer install
~~~

Pour lancer les tests unitaires :

~~~bash
$ ./vendor/bin/phpunit tests
~~~
