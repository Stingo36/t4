<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Configure SmsSystemConfigForm form.
 */
class SmsSystemConfigForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'smssystem.config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smssystem_admin_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#default_value' => $config->get('mode') ? $config->get('mode') : 0,
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#description' => $this->t('Be CAREFUL with this config.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('mode', $form_state->getValue('mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
