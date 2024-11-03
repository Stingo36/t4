<?php

namespace Drupal\ncbs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;

class EmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ncbs_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Fetch users with the 'dean' role.
    $roles = ['dean'];
    $query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $roles, 'IN')
      ->accessCheck(FALSE);
    $uids = $query->execute();
    $users = User::loadMultiple($uids);

    // Create user reference field (multivalue).
    $options = [];
    foreach ($users as $user) {
      $options[$user->id()] = $user->getDisplayName();
    }

    $form['user_references'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Dean users'),
      '#options' => $options,
      '#multiple' => TRUE,
    ];

    // Email subject.
    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email Subject'),
      '#required' => TRUE,
    ];

    // Email body.
    $form['email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email Body'),
      '#required' => TRUE,
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Email'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Add any custom validation if necessary.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get selected users.
    $selected_uids = array_filter($form_state->getValue('user_references'));
    if (!empty($selected_uids)) {
      // Fetch email subject and body.
      $subject = $form_state->getValue('email_subject');
      $body = $form_state->getValue('email_body');

      // Load each user and send email.
      foreach ($selected_uids as $uid) {
        $user = User::load($uid);
        if ($user) {
          $to = $user->getEmail();
          // Trigger the email sending function.
            //\Drupal::moduleHandler()->invoke('ncbs', 'mail', [$user->getEmail(), $subject, $body]);
            // Send email using the mail manager.
$mailManager = \Drupal::service('plugin.manager.mail');
$module = 'ncbs';
$key = 'mail'; // The key to identify the mail type.
$langcode = \Drupal::currentUser()->getPreferredLangcode();
$send = TRUE;

// Inside the submitForm method.
foreach ($selected_uids as $uid) {
    $user = User::load($uid);
    if ($user) {
      $to = $user->getEmail();
      $params = [
        'to' => $to, // Add the recipient email to the params array.
        'subject' => $subject,
        'body' => $body,
      ];
  
      // Send the email.
      $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }
  }
  


        }
      }
      \Drupal::messenger()->addStatus($this->t('Email(s) sent successfully.'));
    } else {
      \Drupal::messenger()->addWarning($this->t('No users selected.'));
    }
  }
}
