<?php

namespace Drupal\twilio\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\twilio\Controller\TwilioController;
use Drupal\twilio\Services\Sms;
use Drupal\twilio\TwilioConstantsInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to send test SMS messages.
 */
class UserSettingsForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Injected Twilio service Sms class.
   *
   * @var \Drupal\twilio\Services\Sms
   */
  private $sms;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
 * Class Constructor.
 *
 * @param \Drupal\twilio\Services\Sms $sms
 *   The sms.
 * @param \Drupal\user\UserStorageInterface $user_storage
 *   The user storage.
 * @param \Drupal\Core\Messenger\Messenger $messenger
 *   The messenger.
 */
  final public function __construct(Sms $sms, UserStorageInterface $user_storage, Messenger $messenger) {
    $this->sms = $sms;
    $this->userStorage = $user_storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('twilio.sms'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('messenger')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twilio_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {

    $twilio_user = $this->sms->twilioLoad($user);
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $user,
    ];

    if (empty($twilio_user['status'])) {
      $form['countrycode'] = [
        '#type' => 'select',
        '#title' => $this->t('Country code'),
        '#options' => TwilioController::countryDialCodes(FALSE),
      ];
      $form['number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('A confirmation code will be sent to via SMS to the number provided'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send SMS'),
      ];
    }
    elseif ($twilio_user['status'] == 1) {
      $form['number'] = [
        '#type' => 'item',
        '#title' => $this->t('Mobile phone number'),
        '#markup' => $twilio_user['number'],
      ];
      $form['confirm_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Confirmation code'),
        '#description' => $this->t('Enter the confirmation code sent by SMS to your mobile phone.'),
        '#size' => 4,
        '#maxlength' => 4,
        '#prefix' => '<div id="confirm-code"></div>',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm number'),
      ];

    }
    elseif ($twilio_user['status'] == 2) {
      $form['twilio_user']['number'] = [
        '#type' => 'item',
        '#title' => $this->t('Your mobile phone number'),
        '#markup' => '+' . $twilio_user['country'] . ' ' . $twilio_user['number'],
        '#description' => $this->t('Your mobile phone number has been confirmed.'),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete & start over'),
      ];

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $twilio_user = $this->sms->twilioLoad($form_state->getValue('uid'));

    if ($form_state->getValue('submit') == 'Confirm number') {
      $value = $form_state->getValue('confirm_code');

      if ($value != $twilio_user['code']) {
        $form_state->setErrorByName('confirm_code', $this->t('The confirmation code is invalid.'));
        // $ajax_response->addCommand(new HtmlCommand('#confirm-code', $text));
      }
    }
    if ($form_state->getValue('submit') == 'Send SMS') {

      $num_verify = $this->sms->twilioVerifyDuplicateNumber($form_state->getValue('number'));

      if ($num_verify) {
        $form_state->setErrorByName('number', $this->t('This number is already in use and cannot be assigned to more than one account'));
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $uid = $form_state->getValue('uid');
    $twilio_user = $this->sms->twilioLoad($uid);
    $account_data = $this->userStorage->load($uid);

    if ($form_state->getValue('submit') == 'Delete & start over') {
      $this->sms->twilioUserDelete($uid);
      $this->messenger->addMessage('Your mobile information has been removed..');

    }
    elseif ($form_state->getValue('submit') == 'Send SMS') {
      $message_status = $this->sms->twilioUserSendConfirmation($account_data, $form_state->getValue('number'), $form_state->getValue('countrycode'));
      if ($message_status == 'send') {
        $this->messenger->addStatus('Message has been send on your mobile number...');
      }
    }
    else {
      $data = [
        'number' => $twilio_user['number'],
        'status' => TwilioConstantsInterface::TWILIO_USER_CONFIRMED,
      ];
      $this->sms->twilioUpdate($data, $uid);
      $this->messenger->addStatus('Your mobile number has been confirmed...');
    }

  }

}
