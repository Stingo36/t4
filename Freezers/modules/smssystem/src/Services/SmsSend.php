<?php

namespace Drupal\smssystem\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;

/**
 * Send Sms notification.
 */
class SmsSend {

  use StringTranslationTrait;

  /**
   * The service API url.
   *
   * @var string
   */
  protected string $ip;

  /**
   * The service base path.
   *
   * @var string
   */
  protected string $basePath;

  /**
   * The service protocol.
   *
   * @var string
   */
  protected string $protocol = 'https';

  /**
   * The username (login).
   *
   * @var string
   */
  protected string $username;

  /**
   * The password.
   *
   * @var string
   */
  protected string $password;

  /**
   * Returns the default http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected LoggerChannelFactory $loggerFactory;

  /**
   * Retrieves the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Gets the current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Gets the queue factory.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * Constructs a new SmsSend object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current account.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   */
  public function __construct(
    Client $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactory $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    Token $token,
    AccountProxyInterface $current_user,
    QueueFactory $queue
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
    $this->currentUser = $current_user;
    $this->queue = $queue->get('sms_send_processing');
    // Get admin API configs.
    $config = $this->configFactory->get('smssystem.api.config');

    if (
      empty($config->get('ip')) ||
      empty($config->get('base_path')) ||
      empty($config->get('username')) ||
      empty($config->get('password'))
    ) {
      $this
        ->loggerFactory
        ->get('type')
        ->error($this->t(
          "SMS system: please, go here: @here and add all necessary configs or you won't be able to use the system.",
          ['@here' => Url::fromRoute('smssystem.api')->toString()]
        ));
    }
    $this->ip = $config->get('ip');
    $this->basePath = $config->get('base_path');
    $this->username = $config->get('username');
    $this->password = $config->get('password');
  }

  /**
   * Callback used for adding an SMS entity item for reporting - after sending.
   *
   * @param string $to
   *   The recipient, or, the actual phone number to where we send the SMS.
   * @param string $message
   *   The message body.
   * @param string $smsid
   *   The unique SMS id string.
   * @param bool $test
   *   The flag that indicates if is TEST or LIVE mode.
   */
  protected function addSmsReport(string $to, string $message, string $smsid, bool $test = FALSE) {
    $data = [
      'to' => $to,
      'smsid' => $smsid,
      'test' => $test,
      'message' => $message,
      'uid' => $this->currentUser->id(),
    ];
    $sms = $this->entityTypeManager->getStorage('sms')->create($data);
    $sms->save();
  }

  /**
   * Send a SMS to a recipient.
   *
   * @param string $to
   *   The recipient, or, the actual phone number to where we send the SMS.
   * @param string $message
   *   The message body.
   * @param bool $should_queue
   *   If true, the SMS will be sent to the queue and wait for cron run.
   */
  public function sendSms(string $to, string $message, bool $should_queue = FALSE) {
    $system_config = $this->configFactory->get('smssystem.config');
    // Generate an SMS id.
    $smsid = sha1(time());
    // Do not perform any processing once the test mode is on.
    if ($system_config->get('mode') == 'test') {
      $this->addSmsReport($to, $message, $smsid, TRUE);
      // Log a message in Drupal logs with sender, recipient, and message.
      $this
        ->loggerFactory
        ->get('smssystem')->notice(
          'SMS system is in test mode. Message not sent. Sender: @sender, Recipient: @recipient, Message: @message',
          [
            // Include the sender (modify as needed).
            '@sender' => $this->t('Website admin'),
            '@recipient' => $to,
            '@message' => $message,
          ]
        );
      return;
    }

    // Prepare the request params.
    $params = [
      // Generate unisue SMS id.
      'smsid' => $smsid,
      // We can't change this value, so, an empty string will be enough.
      'from' => '',
      'to' => $to,
      // This is SMS text message (sanitized for SMS service).
      'text' => urlencode(str_replace("\r\n", "%0a", $message)),
      // Auth data.
      'username' => $this->username,
      'password' => $this->password,
    ];
    // Add SMS to the queue processing.
    if (!empty($should_queue)) {
      $this->queue->createItem((object) $params);
      return;
    }

    $url = $this->protocol . '://' . $this->ip . '' . $this->basePath;
    // This is SMS text message (filtered by tokens if any).
    $message = $this->token->replace($message);

    // Do not require ssl verification (at least for now).
    $options = ['verify' => FALSE];
    $request_params = [];
    foreach ($params as $param_name => $param_value) {
      $request_params[] = $param_name . '=' . $param_value;
    }
    // Build the request full url.
    $url .= '?' . implode('&', $request_params);

    try {
      $response = $this->httpClient->get($url, $options);
      $code = $response->getStatusCode();
      // 0: Accepted for delivery (which means, message was sent).
      if ($code == 202) {
        $this->addSmsReport($params['to'], $message, $params['smsid']);
      }
    }
    catch (\Exception $e) {
      $this
        ->loggerFactory
        ->get('type')
        ->error($this->t(
          "SMS system: Could not send the message: @reason.",
          ['@reason' => $e->getMessage()]
        ));
      // @todo Handle an error. Also, write in log table.
    }
  }

  /**
   * Send a SMS to a recipient by a given template.
   *
   * @param string $template_name
   *   The machine name of the template.
   * @param string $to
   *   The recipient, or, the actual phone number to where we send the SMS.
   * @param bool $should_queue
   *   If true, the SMS will be sent to the queue and wait for cron run.
   */
  public function sendSmsByTemplate(string $template_name, string $to, bool $should_queue = FALSE) {
    $template = $this
      ->entityTypeManager
      ->getStorage('sms_message_template')
      ->loadByProperties(['template_name' => $template_name]);

    if (empty($template)) {
      $this
        ->loggerFactory
        ->get('type')
        ->error($this->t(
          "SMS system: Could not find the SMS template with the name: @template_name.",
          ['@template_name' => $template_name]
        ));

      return FALSE;
    }

    // Load message body from SMS template entity.
    if (!empty($template)) {
      $template = reset($template);
      // This is SMS text message (filtered by tokens if any).
      $message = $this->token->replace($template->get('template_text')->value);

      return $this->sendSms($to, $message, $should_queue);
    }
  }

}
