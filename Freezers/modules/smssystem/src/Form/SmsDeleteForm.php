<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting SMS entities.
 *
 * @ingroup smssystem
 */
class SmsDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Set redirect to listing SMS reporting listing page.
    $form_state->setRedirect('view.reporting.sms_reporting');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.reporting.sms_reporting');
  }

}
