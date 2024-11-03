<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Configure SmsSystemApiConfigForm form.
 */
class SmsSystemApiConfigForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'smssystem.api.config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smssystem_admin_api_config';
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

    $form['ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP'),
      '#default_value' => $config->get('ip') ? $config->get('ip') : '',
      '#description' => $this->t('Service IP.'),
      '#required' => TRUE,
    ];
    $form['base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#default_value' => $config->get('base_path') ? $config->get('base_path') : '',
      '#description' => $this->t('Service base route path.'),
      '#required' => TRUE,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth username'),
      '#default_value' => $config->get('username') ? $config->get('username') : '',
      '#description' => $this->t('Service auth username.'),
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Auth password'),
      '#default_value' => $config->get('password') ? $config->get('password') : '',
      '#description' => $this->t('Service auth password.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('ip', $form_state->getValue('ip'))
      ->set('base_path', $form_state->getValue('base_path'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
