<?php

namespace Drupal\ncbs\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Defines a custom field for sending emails in a view.
 *
 * @ViewsField("send_referee_email")
 */
class SendRefereeEmail extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Load the node using the entity ID from the result row.
    $node = $values->_entity;

    // Initialize session, nid, and email variables.
    $session_value = '';
    $nid = $node->id();
    $email = '';

    // Debug: Log the node ID.
    \Drupal::logger('ncbs')->debug('Node ID: @nid', ['@nid' => $nid]);

    // Check if the field 'field_session_key' exists and has a value.
    if ($node && $node->hasField('field_session_key') && !$node->get('field_session_key')->isEmpty()) {
      // Get the session value from the field.
      $session_value = $node->get('field_session_key')->value;
      // Debug: Log the session key value.
      \Drupal::logger('ncbs')->debug('Session Key Value: @session', ['@session' => $session_value]);
    } else {
      \Drupal::logger('ncbs')->debug('Session Key is empty or does not exist.');
    }

    // Check if the node has the paragraph reference field (e.g., 'field_list_of_referees').
    if ($node->hasField('field_list_of_referees') && !$node->get('field_list_of_referees')->isEmpty()) {
      // Load the first referenced paragraph entity.
      $paragraph = $node->get('field_list_of_referees')->entity;
      
      // Check if the paragraph has the 'field_email' and retrieve its value.
      if ($paragraph && $paragraph->hasField('field_email') && !$paragraph->get('field_email')->isEmpty()) {
        $email = $paragraph->get('field_email')->value;
        // Debug: Log the email value from the paragraph.
        \Drupal::logger('ncbs')->debug('Paragraph Email Value: @field_email', ['@field_email' => $email]);
      } else {
        \Drupal::logger('ncbs')->debug('Email field is empty or does not exist in the paragraph.');
      }
    } else {
      \Drupal::logger('ncbs')->debug('Paragraph reference field is empty or does not exist on the node.');
    }

    // Create the URL, appending nid, session, type, and email as query parameters.
    $url = Url::fromUri('internal:/node/add/send_emails', [
      'query' => [
        'nid' => $nid,
        'session' => $session_value,
        'type' => 'Referee', // Add the type parameter with value 'Referee'
        'email' => $email,    // Append the email value to the URL
      ],
    ]);

    // Debug: Log the final URL.
    \Drupal::logger('ncbs')->debug('Generated URL: @url', ['@url' => $url->toString()]);

    // Return a render array for the "Send Email" link.
    return [
      '#type' => 'link',
      '#title' => $this->t('Send Email'),
      '#url' => $url,
      '#attributes' => [
        'target' => '_blank',  // Open in a new tab
        'class' => ['send-email-link'],  // Add a CSS class
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Ensure that the field is not added to the query, as it's computed.
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // You can add extra options here, if necessary.
    return $options;
  }
}
