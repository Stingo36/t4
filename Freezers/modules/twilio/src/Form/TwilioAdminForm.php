<?php

namespace Drupal\twilio\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\twilio\Controller\TwilioController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for Twilio config.
 */
class TwilioAdminForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  final public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twilio_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('twilio.settings');
    // Skip unnecessary fields.
    $skip_fields = [
      'form_build_id',
      'form_token',
      'form_id',
      'twilio_callback_container',
      'actions',
    ];
    foreach (Element::children($form) as $variable) {
      if (!in_array($variable, $skip_fields) ) {
        $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
      }
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twilio.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twilio.settings');
    $key_exists = $this->moduleHandler->moduleExists('key');

    if ($key_exists) {
      $form['account'] = [
        '#type' => 'key_select',
        '#required' => TRUE,
        '#default_value' => $config->get('account'),
        '#title' => $this->t('Twilio Account SID'),
      ];
      $form['token'] = [
        '#type' => 'key_select',
        '#required' => TRUE,
        '#default_value' => $config->get('token'),
        '#title' => $this->t('Twilio authentication token'),
      ];
    }
    else {
      $form['account'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Twilio Account SID'),
        '#default_value' => $config->get('account'),
        '#description' => $this->t('Enter your Twilio account id'),
      ];
      $form['token'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Twilio Auth Token'),
        '#default_value' => $config->get('token'),
        '#description' => $this->t('Enter your Twilio token id'),
      ];
    }
    $form['number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Twilio Phone Number'),
      '#default_value' => $config->get('number'),
      '#description' => $this->t('Enter your Twilio phone number'),
    ];
    $form['confirmation_code_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edit label for Confirmation Code'),
      '#default_value' => $config->get('confirmation_code_text'),
      '#description' => $this->t("Uses 'Confirmation code' by default"),
    ];
    $form['long_sms'] = [
      '#type' => 'radios',
      '#title' => $this->t('Long SMS handling'),
      '#description' => $this->t('How would you like to handle SMS messages longer than 160 characters.'),
      '#options' => [
        $this->t('Send multiple messages'),
        $this->t('Truncate message to 160 characters'),
      ],
      '#default_value' => $config->get('long_sms'),
    ];
    $form['registration_form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show mobile fields during user registration'),
      '#description' => $this->t('Specify if the site should collect mobile information during registration.'),
      '#options' => [
        $this->t('Disabled'),
        $this->t('Optional'),
        $this->t('Required'),
      ],
      '#default_value' => $config->get('registration_form'),
    ];

    $form['registration_send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send confirmation SMS on registration'),
      '#default_value' => $config->get('registration_send'),
    ];

    $form['twilio_country_codes_container'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Country codes'),
      '#description' => $this->t('Select the country codes you would like available, If none are selected all will be available.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $container = $config->get('twilio_country_codes_container') ?? [];
    $default = $container['country_codes'] ?? [];
    $form['twilio_country_codes_container']['country_codes'] = [
      '#type' => 'checkboxes',
      '#options' => TwilioController::countryDialCodes(TRUE),
      '#default_value' => $default,
    ];
    // Expose the callback URLs to the user for convenience.
    $form['twilio_callback_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Module callbacks'),
      '#description' => $this->t('Enter these callback addresses into your Twilio phone number configuration on the Twilio dashboard to allow your site to respond to incoming voice calls and SMS messages.'),
    ];

    // Initialize URL variables.
    $voice_callback = $GLOBALS['base_url'] . '/twilio/voice';
    $sms_callback = $GLOBALS['base_url'] . '/twilio/sms';
    $status_callback = $GLOBALS['base_url'] . '/twilio/status';

    $form['twilio_callback_container']['voice_callback'] = [
      '#type' => 'item',
      '#title' => $this->t('Voice request URL'),
      '#markup' => '<p>' . $voice_callback . '</p>',
    ];

    $form['twilio_callback_container']['sms_callback'] = [
      '#type' => 'item',
      '#title' => $this->t('SMS request URL'),
      '#markup' => '<p>' . $sms_callback . '</p>',
    ];

    $form['twilio_callback_container']['status_callback'] = [
      '#type' => 'item',
      '#title' => $this->t('Status callback URL'),
      '#markup' => '<p>' . $status_callback . '</p>',
    ];

    $form['capture_messages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capture messages to log locally, without sending SMS'),
      '#default_value' => $config->get('capture_messages'),
      '#description' => $this->t('This will prevent SMS from being sent. Messages will be available in the <a href=":log_link">Twilio log</a>.', [
        ':log_link' => Url::fromRoute('twilio.twilio_log')->toString(),
      ])
    ];

    return parent::buildForm($form, $form_state);
  }

}
