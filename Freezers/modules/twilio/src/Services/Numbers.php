<?php

namespace Drupal\twilio\Services;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service class for Twilio phone number handling.
 */
class Numbers extends TwilioBase {

  /**
   * Numbers being handled in this instance.
   *
   * @var array
   */
  private $numbers;

  /**
   * Cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $bin;

  /**
   * Initialize properties.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, CacheFactoryInterface $cacheFactory) {
    parent::__construct($config_factory, $moduleHandler, $cacheFactory);
    $this->bin = $this->cacheFactory->get('twilio');
  }

  /**
   * Get all purchased numbers.
   */
  public function getAllNumbers(): Numbers {
    if ($cache = $this->bin->get('allnumbers')) {
      $this->numbers = $cache->data;
      return $this;
    }
    $this->numbers = $this->client()->incomingPhoneNumbers->read();
    $this->bin->set('allnumbers', $this->numbers);
    return $this;
  }

  /**
   * Get purchased numbers by capability.
   *
   * @param string $capability
   *   The capability we want, can be voice, sms, mms or fax.
   */
  public function getByCapability(string $capability): Numbers {
    if ($cache = $this->bin->get($capability . 'numbers')) {
      $this->numbers = $cache->data;
      return $this;
    }
    $numbers = $this->client()->incomingPhoneNumbers->stream();
    $this->numbers = [];
    foreach ($numbers as $number) {
      $capable = $number->capabilities[$capability] ?? FALSE;
      if ($capable) {
        $this->numbers[] = $number;
      }
    }
    $this->bin->set($capability . 'numbers', $this->numbers);
    return $this;
  }

  /**
   * Get voice enabled numbers.
   */
  public function getVoiceNumbers(): Numbers {
    $this->getByCapability('voice');
    return $this;
  }

  /**
   * Get SMS enabled numbers.
   */
  public function getSmsNumbers(): Numbers {
    $this->getByCapability('sms');
    return $this;
  }

  /**
   * Get MMS enabled numbers.
   */
  public function getMmsNumbers(): Numbers {
    $this->getByCapability('mms');
    return $this;
  }

  /**
   * Get Fax enabled numbers.
   */
  public function getFaxNumbers(): Numbers {
    $this->getByCapability('fax');
    return $this;
  }

  /**
   * Filter numbers by country.
   *
   * This is necessary because the IncomingPhoneNumbers endpoint
   * does not return locality information for purchased numbers.
   *
   * @param string $country
   *   ISO country code.
   */
  public function filterByCountry(string $country) {
    $closure = function ($n) use ($country) {
      $lookup = $this->lookup($n->phoneNumber);
      return $lookup->countryCode == $country;
    };
    $this->numbers = array_values(array_filter($this->numbers, $closure));
    return $this;
  }

  /**
   * Perform a lookup request for a single number.
   *
   * @param string $number
   *   The phone number in e.164 format.
   */
  public function lookup($number) {
    if ($cache = $this->bin->get($number)) {
      return $cache->data;
    }
    $response = $this->client()->lookups->v1->phoneNumbers($number)->fetch();
    $this->bin->set($number, $response);
    return $response;
  }

  /**
   * Magic method to get a string representation of the found numbers.
   */
  public function __toString() {
    $closure = function ($r, $n) {
      $r .= $n->phoneNumber . "\n";
      return $r;
    };
    return array_reduce($this->numbers, $closure, count($this->numbers) . " numbers: \n");
  }

  /**
   * Magic method to get numbers in a palatable form.
   *
   * @param string $name
   *   The name of the parameter.
   */
  public function __get(string $name) {
    if ($name == 'numbers') {
      $closure = function ($n) {
        return [
          'friendly_name' => $n->friendlyName,
          'phone_number' => $n->phoneNumber,
          'capabilities' => $n->capabilities,
        ];
      };
      return array_map($closure, $this->numbers);
    }
  }

  /**
   * Manual cache reset function.
   */
  public function resetCache() {
    $this->bin->deleteAll();
    return $this;
  }

}
