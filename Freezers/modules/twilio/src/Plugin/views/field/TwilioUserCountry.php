<?php

namespace Drupal\twilio\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("twilio_user_country")
 */
class TwilioUserCountry extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // First check whether the field should be hidden
    // if the value(hide_alter_empty = TRUE)
    // the rewrite is empty (hide_alter_empty = FALSE).
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $values->_entity->id();
    $phone_no = $this->getTwilioCountry($uid);

    return $phone_no;
  }

  /**
   * Inster the recored in twilio table.
   *
   * @return bool
   *   Returns TRUE after successful execution
   */
  private function getTwilioCountry($uid) {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');

    $country_code = $connection->select('twilio_user')
      ->fields('twilio_user', ['country'])
      ->condition('uid', $uid)
      ->execute()
      ->fetchField();

    return $country_code;
  }

}
