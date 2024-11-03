<?php

namespace Drupal\smssystem\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smssystem\Services\SmsSend;

/**
 * The SMS message processing logic.
 */
abstract class SmsProcessing extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The SMS Send service.
   *
   * @var \Drupal\smssystem\Services\SmsSend
   */
  protected $smsSend;

  /**
   * Creates a new SmsProcessing instance.
   *
   * @param \Drupal\smssystem\Services\SmsSend $sms_send
   *   The node storage.
   */
  final public function __construct(SmsSend $sms_send) {
    $this->smsSend = $sms_send;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('smssystem.send_sms')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Send the SMS to the recipient.
    return $this->smsSend->sendSms($data->to, $data->text);
  }

}
