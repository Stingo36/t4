<?php

namespace Drupal\simple_twilio\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_twilio\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

define('TWILIO_USER_PENDING', 1);
define('TWILIO_USER_CONFIRMED', 2);

/**
 * Form for Validate Mobile number.
 */
class SimpleTwilioPageForm extends FormBase {
  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;
  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new object.
   */
  public function __construct(AccountInterface $account, Connection $database) {
    $this->account = $account;
    $this->database = $database;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
          $container->get('current_user'),
          $container->get('database')

      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_twilio_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $uid = $this->account->id();

    $form['#prefix'] = '<div id="simple-twilio-user-form">';
    $form['#suffix'] = '</div>';

    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $uid,
    ];

    $query = $this->database->select('simple_twilio_user', 't');
    $query->fields('t', ['status', 'number', 'country']);
    $query->condition('t.uid', $uid);
    $tiwilio = $query->execute()->fetchAssoc();

    if (empty($tiwilio['status'])) {
      $form['countrycode'] = [
        '#type' => 'select',
        '#title' => $this->t('Country code'),
        '#options' => Utility::simpleTwilioCountryCodes(),
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
        '#value' => $this->t('Confirm number'),
        '#validate' => ['::simpleTwilioUserSettingsAddFormValidate'],
        '#submit' => ['::simpleTwilioUserSettingsAddFormSubmit'],
        '#ajax' => [
          'callback' => '::simpleTwilioUserSettingsAjaxCallback',
          'wrapper' => 'simple-twilio-user-form',
          'method' => 'replace',
        ],
      ];
    }
    elseif ($tiwilio['status'] == 1) {
      $form['number'] = [
        '#type' => 'item',
        '#title' => $this->t('Mobile phone number'),
        '#markup' => $tiwilio['number'],
      ];
      $form['confirm_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Confirmation code'),
        '#description' => $this->t('Enter the confirmation code sent by SMS to your mobile phone.'),
        '#size' => 4,
        '#maxlength' => 4,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm number'),
        '#validate' => ['::simpleTwilioUserSettingsConfirmFormValidate'],
        '#submit' => ['::simpleTwilioUserSettingsConfirmFormSubmit'],
        '#ajax' => [
          'callback' => '::simpleTwilioUserSettingsAjaxCallback',
          'wrapper' => 'simple-twilio-user-form',
          'method' => 'replace',
        ],
      ];
      $form['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete & start over'),
        '#validate' => ['::simpleTwilioUserSettingsConfirmFormValidate'],
        '#submit' => ['::simpleTwilioUserSettingsConfirmFormSubmit'],
        '#ajax' => [
          'callback' => '::simpleTwilioUserSettingsAjaxCallback',
          'wrapper' => 'simple-twilio-user-form',
          'method' => 'replace',
        ],
      ];
    }
    elseif ($tiwilio['status'] == 2) {
      $form['twilio_user']['number'] = [
        '#type' => 'item',
        '#title' => $this->t('Your mobile phone number'),
        '#markup' => '+' . $tiwilio['country'] . ' ' . $tiwilio['number'],
        '#description' => $this->t('Your mobile phone number has been confirmed.'),
      ];
      $form['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete & start over'),
        '#validate' => ['::simpleTwilioUserSettingsResetFormValidate'],
        '#submit' => ['::simpleTwilioUserSettingsResetFormSubmit'],
        '#ajax' => [
          'callback' => '::simpleTwilioUserSettingsAjaxCallback',
          'wrapper' => 'simple-twilio-user-form',
          'method' => 'replace',
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements callback for Ajax event on color selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Color selection section of the form.
   */
  public function simpleTwilioUserSettingsAjaxCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Validation function for user settings form.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwilioUserSettingsAddFormValidate($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('number');
    if (!is_numeric($value)) {
      $form_state->setErrorByName('number', $this->t('You must enter a valid phone number'));
    }
    elseif (Utility::simpleTwilioVerifyDuplicateNumber($value, $form_state->getValue('countrycode'))) {
      $form_state->setErrorByName('number', $this->t('This number is already in use and cannot be assigned to more than one account'));
    }
  }

  /**
   * Submit handler for user settings form.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwilioUserSettingsAddFormSubmit($form, FormStateInterface $form_state) {
    $account = $this->account;
    Utility::simpleTwiliUserSendConfirmation($account, $form_state->getValue('number'), $form_state->getValue('countrycode'));
    $this->messenger()->addStatus($this->t("A confirmation code has been sent to your mobile device"));
    $form_state->setRebuild();
  }

  /**
   * Validation handler for user settings confirmation form.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwilioUserSettingsConfirmFormValidate($form, FormStateInterface $form_state) {
    $clicked_button = &$form_state->getTriggeringElement()['#parents'][0];
    if ($clicked_button == "submit") {
      $account = $this->account;

      $query = $this->database->select('simple_twilio_user', 't');
      $query->fields('t', ['code']);
      $query->condition('t.uid', $account->id());
      $code = $query->execute()->fetchField();

      if ($form_state->getValue('confirm_code') != $code) {
        $form_state->setErrorByName('confirm_code', $this->t('The confirmation code is invalid.'));
      }
    }
  }

  /**
   * Submit handler for user settings confirmation form.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwilioUserSettingsConfirmFormSubmit($form, FormStateInterface $form_state) {
    $account = $this->account;
    $clicked_button = &$form_state->getTriggeringElement()['#parents'][0];
    if ($clicked_button == "reset") {
      Utility::simpleTwilioUserDelete($account);
      $this->messenger()->addStatus($this->t('Your mobile information has been removed'));
    }
    else {
      $this->database->update('simple_twilio_user')
        ->condition('uid', $account->id())
        ->fields([
          'status' => TWILIO_USER_CONFIRMED,
        ])->execute();
      $this->messenger()->addStatus($this->t('Your mobile number has been confirmed'));
    }
    $form_state->setRebuild();
  }

  /**
   * Validation handler for user settings reset form.
   */
  public function simpleTwilioUserSettingsResetFormValidate($form, FormStateInterface $form_state) {
  }

  /**
   * Submit handler for user settings reset form.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwilioUserSettingsResetFormSubmit($form, FormStateInterface $form_state) {
    $account = $this->account;
    Utility::simpleTwilioUserDelete($account);
    $this->messenger()->addStatus($this->t('Your mobile information has been removed'));
    $form_state->setRebuild();
  }

}
