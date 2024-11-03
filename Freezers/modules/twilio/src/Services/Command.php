<?php

namespace Drupal\twilio\Services;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Service class for Twilio API commands.
 */
class Command {

  /**
   * Twilio Account SID.
   *
   * @var string
   */
  private $sid;

  /**
   * Twilio Auth Token.
   *
   * @var string
   */
  private $token;

  /**
   * Phone number to be used on twilio.
   *
   * @var string
   */
  private $number;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Initialize properties.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory->get('twilio.settings');
    $this->sid = $this->getSid();
    $this->token = $this->getToken();
    $this->number = $this->getNumber();
  }

  /**
   * Get config/key value.
   */
  private function getConfig(string $key):string {
    $value = $this->configFactory
      ->get($key);
    if ($value && \Drupal::moduleHandler()->moduleExists('key')) {
      // @phpstan-ignore-next-line
      $key = \Drupal::service('key.repository')->getKey($value);
      if ($key && $key->getKeyValue()) {
        $value = $key->getKeyValue();
      }
    }
    return $value ?? '';
  }

  /**
   * Get the Twilio Account SID.
   *
   * @return string
   *   The configured Twilio Account SID.
   */
  public function getSid() {
    if (empty($this->sid)) {
      $this->sid = $this->getConfig('account');
    }
    return $this->sid;
  }

  /**
   * Get the Twilio Auth Token.
   *
   * @return string
   *   The configured Twilio Auth Token.
   */
  public function getToken() {
    if (empty($this->token)) {
      $this->token = $this->getConfig('token');
    }
    return $this->token;
  }

  /**
   * Get the Twilio Number.
   *
   * @return string
   *   The configured Twilio Number.
   */
  public function getNumber() {
    if (empty($this->number)) {
      $this->number = $this->configFactory->get('number');
    }
    return $this->number;
  }

  /**
   * Send an SMS message.
   *
   * @param string $number
   *   The number to send the message to.
   * @param string|array $message
   *   Message text or an array:
   *   [
   *     from => number
   *     body => message string
   *     mediaUrl => absolute URL
   *   ].
   */
  public function messageSend(string $number, $message) {
    if (is_string($message)) {
      $message = [
        'from' => $this->number,
        'body' => $message,
      ];
    }
    $message['from'] = !empty($message['from']) ? $message['from'] : $this->number;
    if (empty($message['body'])) {
      throw new TwilioException("Message requires a body.");
    }
    if (!empty($message['mediaUrl']) && !UrlHelper::isValid($message['mediaUrl'], TRUE)) {
      throw new TwilioException("Media URL must be a valid, absolute URL.");
    }
    $client = new Client($this->sid, $this->token);
    $client->messages->create(
      $number,
      [
        'from' => $this->number,
        'body' => $message,
      ]
    );
  }

}
