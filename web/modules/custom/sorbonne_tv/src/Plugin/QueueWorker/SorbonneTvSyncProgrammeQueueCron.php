<?php

namespace Drupal\sorbonne_tv\Plugin\QueueWorker;

/**
 *Queue worker on CRON run.
 *
 * @QueueWorker(
 *   id = "sorbonne_tv_sync_programme_queue",
 *   title = @Translation("Sorbonne TV - Synchro des programmes (API Visionnaire)"),
 *   cron = {"time" = 60}
 * )
 */
class SorbonneTvSyncProgrammeQueueCron extends SorbonneTvSyncProgrammeQueueWorkerBase {}
