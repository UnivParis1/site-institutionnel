<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Exception;

/**
 * La synchro des pages personnelles a refusé de s'exécuter (garde-fou).
 */
class SyncAbortedException extends \RuntimeException {
}
