<?php

namespace Drupal\twilio\Services;

use Twilio\Exceptions\TwilioException;

/**
 * Service class for Twilio voice calling.
 */
class Voice extends TwilioBase {

  /**
   * Status callback url.
   *
   * @var string
   */
  private $statusCallback;

  /**
   * Status callback method.
   *
   * @var string
   */
  private $statusCallbackMethod = 'POST';

  /**
   * Status callback events.
   *
   * @var array
   */
  private $statusCallbackEvent = [
    'initiated',
    'ringing',
    'answered',
    'completed',
  ];

  /**
   * Answering machine detection enabled.
   *
   * @var bool
   */
  private $machineDetection = FALSE;

  /**
   * Async Answering machine detection.
   *
   * @var bool
   */
  private $amd = TRUE;

  /**
   * TwiML script to use on the call.
   *
   * @var \SimpleXMLElement
   */
  private $twiml;

  /**
   * URL to fetch twiml from to use on the call.
   *
   * @var string
   */
  private $twimlUrl;

  /**
   * Set the status callback url.
   *
   * @param string|null $url
   *   The url twilio will call for status updates.
   * @param string $method
   *   The HTTP method to use.
   * @param array $events
   *   The events to track.
   */
  public function setCallback(string $url = NULL, string $method = 'POST', array $events = []): Voice {
    $url = $url ?? $GLOBALS['base_url'] . '/twilio/status';
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      $this->statusCallback = $url;
      $this->statusCallbackMethod = $method;
      if (!empty($events)) {
        $this->statusCallbackEvent = $events;
      }
    }
    else {
      throw new TwilioException('Invalid status callback url.');
    }
    return $this;
  }

  /**
   * Turn on machine detection.
   */
  public function enableMachineDetection($async = TRUE) {
    $this->machineDetection = TRUE;
    $this->amd = $async;
    return $this;
  }

  /**
   * Make a call.
   *
   * @param string $twilioNumber
   *   The twilio number to use to process the call.
   * @param string $destNumber
   *   The number of the person we are calling.
   *
   * @return string
   *   The ID of the initiated call.
   */
  public function dial(string $twilioNumber, string $destNumber) {
    if ($this->twimlUrl) {
      $params['url'] = $this->twimlUrl;
    }
    elseif (!empty($this->twiml)) {
      $params['twiml'] = $this->twiml->asXML();
    }
    else {
      throw new TwilioException('No twiml specified');
    }
    if (!$this->statusCallback) {
      $this->statusCallback = $GLOBALS['base_url'] . '/twilio/status';
    }
    if ($this->machineDetection) {
      $params['MachineDetection'] = 'Enable';
      $params['AsyncAMD'] = $this->amd;
    }
    $params += [
      'statusCallback' => $this->statusCallback,
      'statusCallbackEvent' => $this->statusCallbackEvent,
      'statusCallbackMethod' => $this->statusCallbackMethod,
    ];
    $call = $this->client()->calls->create(
      $destNumber,
      $twilioNumber,
      $params
    );
    return $call->sid;
  }

  /**
   * Set the twiml xml to use on the call.
   *
   * @param string $script
   *   Either a url or a twiml script.
   */
  public function setScript($script): Voice {
    if (filter_var($script, FILTER_VALIDATE_URL)) {
      $this->twimlUrl = $script;
    }
    else {
      libxml_use_internal_errors(TRUE);
      $xml = simplexml_load_string($script);
      if ($xml !== FALSE) {
        $this->twiml = $xml;
      }
      else {
        throw new TwilioException('Invalid twiml XML.');
      }
    }
    return $this;
  }

  /**
   * Start script.
   */
  public function startScript() {
    if (empty($this->twiml)) {
      $this->twiml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Response></Response>');
    }
  }

  /**
   * Add a Say command to the script.
   *
   * @param string $message
   *   The thing to say.
   * @param string $voice
   *   The robot voice to use.
   * @param string $language
   *   The language the robot should speak in.
   */
  public function addSay(string $message, string $voice = 'woman', string $language = 'en'): Voice {
    $this->startScript();
    $say = $this->twiml->addChild('Say', $message);
    $say->addAttribute('voice', $voice);
    $say->addAttribute('language', $language);
    return $this;
  }

  /**
   * Add a Play command to the script.
   *
   * @param string $url
   *   The url to the recorded message.
   */
  public function addPlay(string $url): Voice {
    $this->startScript();
    $this->twiml->addChild('Play', $url);
    return $this;
  }

  /**
   * Add a beep.
   *
   * @param string $soundFileUrl
   *   The URL to access the soundfile (beep). Has to be public - reachable by
   *   Twilio. You may copy the sound/beep.mp3 in this module to a server you
   *   host and use the full URL when calling addBeep().
   */
  public function addBeep(string $soundFileUrl): Voice {
    $this->startScript();
    $this->twiml->addChild('Play', $soundFileUrl);
    return $this;
  }

  /**
   * Add a Dial command to the script.
   *
   * @param string $number
   *   The phone number to dial.
   */
  public function addDial(string $number): Voice {
    $this->startScript();
    $dial = $this->twiml->addChild('Dial');
    $number = $dial->addChild('Number', $number);
    if ($this->statusCallback) {
      $number->addAttribute('statusCallback', $this->statusCallback);
      $number->addAttribute('statusCallbackEvent', implode(' ', $this->statusCallbackEvent));
      $number->addAttribute('statusCallbackMethod', $this->statusCallbackMethod);
    }
    return $this;
  }

}
