<?php

namespace Drupal\twilio\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

/**
 * Provides a user register form.
 */
class NewUserRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['test'] = [
      '#markup' => '<p>Test extended form</p>',
    ];
    return $form;
  }

}
