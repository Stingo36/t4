<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SmsMessageTemplateSettingsForm to configure sms template.
 *
 * @ingroup smssystem
 */
class SmsMessageTemplateSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_message_template_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sms_message_template_settings']['#markup'] = 'Settings form for SMS Message Template entity.';
    return $form;
  }

}
