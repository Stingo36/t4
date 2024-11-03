<?php

namespace Drupal\ncbs\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Defines a custom field for sending emails in a view.
 *
 * @ViewsField("send_email")
 */
class SendEmail extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Load the node using the entity ID from the result row.
    $node = $values->_entity;

    // Initialize session and nid variables.
    $session_value = '';
    $nid = $node->id();

    // Check if the field 'field_session_key' exists and has a value.
    if ($node && $node->hasField('field_session_key') && !$node->get('field_session_key')->isEmpty()) {
      // Get the session value from the field.
      $session_value = $node->get('field_session_key')->value;
    }

    // Create the URL, appending nid and session as query parameters.
    $url = Url::fromUri('internal:/node/add/send_emails', [
      'query' => [
        'nid' => $nid,
        'session' => $session_value,
      ],
    ]);

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
