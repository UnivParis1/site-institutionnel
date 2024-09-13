<?php

namespace Drupal\sorbonne_tv\Plugin\QueueWorker;

/**
 *Queue worker on CRON run.
 *
 * @QueueWorker(
 *   id = "sorbonne_tv_sync_multimedia_queue",
 *   title = @Translation("Sorbonne TV - Synchro des vidéos (API Sorbonne)"),
 *   cron = {"time" = 60}
 * )
 */
class SorbonneTvSyncMultimediaQueueCron extends SorbonneTvSyncMultimediaQueueWorkerBase {}
