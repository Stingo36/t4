<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting SMS Message Template entities.
 *
 * @ingroup smssystem
 */
class SmsMessageTemplateDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Set redirect to listing SMS message templates listing page.
    $form_state->setRedirect('entity.sms_message_template.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.sms_message_template.collection');
  }

}
