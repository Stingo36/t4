<?php

namespace Drupal\twilio\Services;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\twilio\TwilioConstantsInterface;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Service class for Twilio API commands.
 */
class Sms extends TwilioBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The twilio commands.
   *
   * @var \Drupal\twilio\Services\Command
   */
  protected $twilioCommand;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, Command $twilio_command, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->twilioCommand = $twilio_command;
    $this->messenger = $messenger;
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

    $capture = $this->configFactory->get('twilio.settings')->get('capture_messages');

    $fromNumber = $this->twilioCommand->getNumber();
    if (is_string($message)) {
      $message = [
        'body' => $message,
      ];
    }
    $message['from'] = !empty($message['from']) ? $message['from'] : $fromNumber;
    if (empty($message['body'])) {
      throw new TwilioException("Message requires a body.");
    }
    if (!empty($message['mediaUrl']) && !UrlHelper::isValid($message['mediaUrl'], TRUE)) {
      throw new TwilioException("Media URL must be a valid, absolute URL.");
    }
    $client = new Client($this->twilioCommand->getSid(), $this->twilioCommand->getToken());
    try {
      if ($capture) {
        $log = array_merge($message, [
          'to' => $number,
          'timestamp' => \Drupal::time()->getCurrentTime(),
        ]);

        $this->connection->insert('twilio_log')
          ->fields($log)
          ->execute();

        return 'capture';
      }

      $client->messages->create($number, $message);
      $message_status = 'send';
      return  $message_status;
    }
    catch (TwilioException $e) {
      $this->messenger->addError($e->getMessage());
      $message_status = 'not send';
      return  $message_status;
    }
  }

  /**
   * Send confirmation message.
   *
   * @param object $account
   *   The user object of the account to message.
   * @param string $number
   *   The phone number to send the message.
   * @param string $country
   *   The country code for the number.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function twilioUserSendConfirmation($account, $number, $country) {
    $code = rand(1000, 9999);
    $data = [
      'uid' => $account->id(),
      'number' => $number,
      'country' => $country,
      'status' => TwilioConstantsInterface::TWILIO_USER_PENDING,
      'code' => $code,
      'timestamp' => REQUEST_TIME,
    ];

    // $account = user_save($account, array('twilio' => $data), 'twilio');
    $confirmation_code_text = $this->configFactory->get('twilio.settings')->get('confirmation_code_text') ?: 'Confirmation code';
    $message = $confirmation_code_text . ': ' . $code;
    $number = '+' . $country . $number;
    $message_status = $this->messageSend($number, $message);

    if ($message_status == 'send') {
      $this->twilioInsert($data);
    } else {
      $link = '/user/' . $account->id() . '/edit/twilio';
      $message = t('Please visit the <a href=":account_link">account settings page</a> and enter a valid phone number.',
          [':account_link' => $link]);
      $this->messenger->addMessage($message);
    }

    return $message_status;
  }

  /**
   * Determines if a number is associated with a user account.
   *
   * @param int $number
   *   The phone number we are searching for.
   * @param bool $return_user
   *   Boolean flag to return a user object if TRUE.
   *
   * @results bool
   *   TRUE or FALSE based on query
   */
  public function twilioVerifyNumber($number, $return_user = FALSE) {

    $result = $this->connection->select('twilio_user', 't')
      ->fields('t')
      ->condition('t.number', $number)
      ->condition('t.status', TwilioConstantsInterface::TWILIO_USER_CONFIRMED)
      ->execute()
      ->fetchAssoc();
    if (!empty($result['uid'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a given phone number already exists in the database.
   *
   * @param string $number
   *   The sender's mobile number.
   *
   * @result boolean
   *   TRUE if it exists, FALSE otherwise
   */
  public function twilioVerifyDuplicateNumber($number) {

    $result = $this->connection->select('twilio_user', 't')
      ->fields('t')
      ->condition('t.number', $number)
      ->execute()
      ->fetchAssoc();

    return $result['number'] ?? FALSE;
  }

  /**
   * Insert the record in twilio table.
   *
   * @return bool
   *   Returns TRUE after successful execution.
   */
  public function twilioInsert($data) {
    $this->connection->insert('twilio_user')
      ->fields($data)
      ->execute();

    return TRUE;
  }

  /**
   * Update the record in twilio table.
   *
   * @return bool
   *   Returns TRUE after successful execution
   */
  public function twilioUpdate($data, $uid) {

    $this->connection->update('twilio_user')
      ->fields($data)
      ->condition('uid', $uid)
      ->execute();

    return TRUE;
  }

  /**
   * Delete the record in twilio table.
   *
   * @return bool
   *   Returns TRUE after successful execution
   */
  public function twilioUserDelete($uid) {
    $query = $this->connection->delete('twilio_user');
    $query->condition('uid', $uid);
    $query->execute();

    return TRUE;
  }

  /**
   * Insert the record in twilio table.
   *
   * @return 0|array
   *   An array of twilio details or 0 if user does not have twilio setup
   */
  public function twilioLoad($id) {

    $result = $this->connection->select('twilio_user', 't')
      ->fields('t')
      ->condition('t.uid', $id)
      ->execute()
      ->fetchAssoc();
    if (!empty($result['uid'])) {
      return $result;
    }

    return 0;
  }

}
