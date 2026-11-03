<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Exception;

/**
 * Un web service (wsgroups, HAL) n'a pas donné de réponse exploitable.
 *
 * Levée aussi sans appel réseau quand le disjoncteur est ouvert ou que le
 * service est désactivé dans les réglages. À ne pas confondre avec une réponse
 * vide « normale » : elle permet aux appelants de ne rien modifier (dépublier,
 * retirer un rôle…) sur la foi d'une panne.
 */
class WsUnavailableException extends \RuntimeException {

  public function __construct(
    string $message,
    public readonly string $service = '',
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, 0, $previous);
  }

}
