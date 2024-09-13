<?php

namespace Drupal\lmc_mail\Plugin\QueueWorker;

/**
 *Queue worker on CRON run.
 *
 * @QueueWorker(
 *   id = "lmc_mail_direct_queue",
 *   title = @Translation("Direct Mail Cron Queue"),
 *   cron = {"time" = 30}
 * )
 */
class LmcMailDirectQueueCron extends LmcMailDirectQueueWorkerBase {}
