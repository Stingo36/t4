<?php

namespace Drupal\smssystem\Plugin\QueueWorker;

/**
 * A SMS Processor that Sends an SMS to a recipient on CRON run.
 *
 * @QueueWorker(
 *   id = "sms_send_processing",
 *   title = @Translation("Cron SMS Processor"),
 *   cron = {"time" = 30}
 * )
 */
class CronSmsProcessor extends SmsProcessing {}
