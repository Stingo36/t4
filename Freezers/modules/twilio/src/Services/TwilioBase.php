<?php

namespace Drupal\twilio\Services;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Twilio\Rest\Client;

/**
 * Service class for Twilio API commands.
 */
class TwilioBase {

  /**
   * Twilio account ID.
   *
   * @var string
   */
  protected $sid;

  /**
   * Twilio auth token.
   *
   * @var string
   */
  protected $token;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache factory service.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * Twilio client.
   *
   * @var \Twilio\Rest\Client
   */
  protected $twilio;

  /**
   * Phone number.
   *
   * @var string
   */
  protected $number;

  /**
   * Initialize properties.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, CacheFactoryInterface $cacheFactory) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $moduleHandler;
    $this->cacheFactory = $cacheFactory;
    $this->sid = $this->getSid();
    $this->token = $this->getToken();
    $this->number = $this->getNumber();
  }

  /**
   * Get the Twilio client.
   */
  protected function client() {
    if (empty($this->twilio)) {
      $this->twilio = new Client($this->sid, $this->token);
    }
    return $this->twilio;
  }

  /**
   * Get config/key value.
   */
  private function getConfig(string $key):string {
    $value = $this->configFactory
      ->get('twilio.settings')
      ->get($key);
    if ($value && $this->moduleHandler->moduleExists('key')) {
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
  public function getSid():string {
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
  public function getToken():string {
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
      $this->number = $this->configFactory
        ->get('twilio.settings')
        ->get('number');
    }
    return $this->number;
  }

}
