<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\MiniorangeLdapCustomer;
use Drupal\ldap_auth\Utilities;
use Drupal\Core\Render\Markup;

/**
 *
 */
class MiniorangeLdapCustomerSetup extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_customer_setup';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_status = $this->config->get('miniorange_ldap_status');
    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
            ],
        ],
    ];


    if ($current_status == 'VALIDATE_OTP') {

      $form['miniorange_ldap_customer_otp_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('OTP'),
          '#prefix' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
          '#suffix' => '<br>',
      ];

      $form['miniorange_ldap_customer_validate_otp_button'] = [
          '#type' => 'submit',
          '#value' => $this->t('Validate OTP'),
          '#submit' => ['::miniorange_ldap_validate_otp_submit'],
      ];

      $form['miniorange_ldap_customer_setup_resendotp'] = [
          '#type' => 'submit',
          '#value' => $this->t('Resend OTP'),
          '#submit' => ['::miniorange_ldap_resend_otp'],
      ];

      $form['miniorange_ldap_customer_setup_back'] = [
          '#type' => 'submit',
          '#value' => $this->t('&#171; Back'),
          '#submit' => ['::miniorange_ldap_back'],
          '#button_type' => 'danger',
          '#suffix' => '<br>',
      ];

      $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];

      Utilities::addSupportButton( $form, $form_state);

      return $form;
    }
    elseif ($current_status == 'PLUGIN_CONFIGURATION') {

      $form['header_top_style_1'] = [
          '#markup' => $this->t('<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">
          <div class="mo_ldap_welcome_message">Thank you for registering with miniOrange</div><br><h4>Your Profile: </h4>'),
      ];
      $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('ldap_auth');
      $modules_version = $modules_info['version'];

      $header = [ t('Attribute'),  t('Value'),];
      $miniorangeBaseUrl = MiniorangeLDAPConstants::PORTAL_URL."/initializePayment?requestOrigin=drupal_ldap_allinclusive_plan";

      $options = [
          ['Customer Email' ,$this->config->get('miniorange_ldap_customer_admin_email')],
          ['Customer ID' , $this->config->get('miniorange_ldap_customer_id')],
          ['Drupal Version', \DRUPAL::VERSION],
          ['PHP Version' , phpversion()],
          ['Module Version' , $modules_version],
          ['Upgrade', Markup::create("<a class='button button--primary' href=$miniorangeBaseUrl target='_blank'>Upgrade</a>")],
      ];

      $form['fieldset']['customerinfo'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $options,
          '#suffix' => '<br>',
      ];

      $form['fieldset']['remove_account_button_info'] = [
          '#type'=> 'submit',
          '#button_type' => 'primary',
          '#value' => $this->t('Remove Account'),
          '#disabled' => true,
          '#suffix' => "<br>"
      ];
      $form['fieldset']['remove_account_button'] = [
          '#type' => 'markup',
          '#markup' => "<small>".$this->t('Remove Account is available in the <a href='.$upgrade_plan_link.'>[Premium , All-Inclusive]</a> version.')."</small>",
      ];

      $form['markup_idp_attr_header_top_support'] = ['#markup' => '</div>',
      ];

      Utilities::addSupportButton( $form, $form_state);

      return $form;
    }
    else if($current_status == 'ALREADY_REGISTERED'){

      $form['header_top_style_1'] = ['#markup' => '<div class="mo_ldap_table_layout_1">'];

      $form['markup_top'] = [
          '#markup' => '<div class="mo_ldap_table_layout container"><h2>Login with mini<span class="mo_orange"><b>O</b></span>range</h2><hr>',
      ];

      $form['mo_ldap_customer_email'] = [
          '#type' => 'textfield',
          '#title' => t('Email'),
          '#required' => True,
          '#attributes' => [
              'style' => 'width:35%'
          ],
      ];

      $form['mo_ldap_customer_password'] = [
          '#type' => 'password',
          '#title' => t('Password'),
          '#required' => True,
          '#attributes' => [
              'style' => 'width:35%'
          ],
      ];

      $form['login_submit'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => t('Login')
      ];

      $form['back_button'] = [
          '#type' => 'submit',
          '#submit' => array('::back_to_register_tab'),
          '#value' => t('Create an account?'),
          '#limit_validation_errors' => [],
          '#suffix' => '</div>'
      ];

      Utilities::addSupportButton( $form, $form_state);

      return $form;
    }

    $form['markup_reg'] = [
        '#prefix' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
        '#markup' => '<div><h2>Register with mini<span class="mo_orange"><b>O</b></span>range</h2><hr>',
    ];

    $form['registration_description_1'] = [
        '#markup' => $this->t('<br><div class="mo_ldap_highlight_background_note_1">You should register so that in case you need help, we can help you with step-by-step instructions.
                <b>You will also need a miniOrange account to upgrade to the Premium version of the module.</b>
                We do not store any information except the email that you will use to register with us. Please enter a valid email ID that you have access to. We will send OTP to this email for verification.</div>'),
        '#suffix' => '<br>'
    ];

    $form['registration_description_2'] = [
        '#type' => 'markup',
        '#markup' => "<div class='mo_ldap_highlight_background_note_1'>".$this->t('If you face any issues during registration then you can <b><a href="https://www.miniorange.com/businessfreetrial" target="_blank">click here</a></b> to register and use the same credentials below to login into the module.')."</div>",
    ];


    $form['miniorange_ldap_customer_setup_username'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#attributes' => ['style' => 'width:30%;', 'placeholder' => 'Enter your email'],
        '#required' => TRUE,
    ];

    $form['miniorange_ldap_customer_setup_password'] = [
        '#type' => 'password_confirm',
        '#required' => TRUE,
        '#attributes' => ['style' => 'width:30%;'],
    ];


    $form['miniorange_ldap_customer_setup_button'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Register'),
        '#attributes' => ['style' => 'float:left;'],
        '#prefix' => '<br><span>',
    );

    $form['miniorange_ldap_customer_setup_already_registered_button'] = array(
        '#type' => 'submit',
        '#value' => t('Already have an account?'),
        '#submit' => ['::already_registered'],
        '#limit_validation_errors' => [],
        '#suffix' => '</span>',
    );

    $form['register_close'] = [
        '#markup' => '</div>',
    ];

    $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }


  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $current_status = $this->config->get('miniorange_ldap_status');

    if($current_status == "ALREADY_REGISTERED"){
      $username = $form_values['mo_ldap_customer_email'];
      $password = $form_values['mo_ldap_customer_password'];
    }
    else{
      $username = $form_values['miniorange_ldap_customer_setup_username'];
      $password = $form_values['miniorange_ldap_customer_setup_password'];
    }

    if (!$this->emailValidator->isValid($username)) {
      Utilities::add_message(t('The email address <i>' . $username . '</i> is not valid.'), 'error');
      return;
    }

    $customer_config = new MiniorangeLdapCustomer($username, '', $password, NULL);
    $check_customer_response = json_decode($customer_config->checkCustomer());

    if ($check_customer_response->status == 'CUSTOMER_NOT_FOUND') {
      $this->config_factory->set('miniorange_ldap_customer_admin_email', $username)
          ->set('miniorange_ldap_customer_admin_password', $password)->save();

      if($current_status == 'ALREADY_REGISTERED'){
        \Drupal::messenger()->addMessage(t('Account with username @username is not registered with miniOrange, Please <a href="https://www.miniorange.com/businessfreetrial" target="_blank">Register with miniOrange</a> to login.', [
            '@username' => $username
        ]),'error');
        return;
      }

      $send_otp_response = json_decode($customer_config->sendOtp());

      if ($send_otp_response->status == 'SUCCESS') {
        $this->config_factory->set('miniorange_ldap_tx_id', $send_otp_response->txId)->save();
        $current_status = 'VALIDATE_OTP';
        $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
        Utilities::add_message(t('Verify email address by entering the passcode sent to @username', ['@username' => $username]), 'status');
      }
      else {
        Utilities::add_message(t('Error while processing the request. Please try after some time or Register from <a href="https://www.miniorange.com/businessfreetrial" target="_blank"><i>here</i></a>.'), 'error');
        return;
      }

    }
    elseif ($check_customer_response->status == 'CURL_ERROR') {
      Utilities::add_message(t('cURL is not enabled. Please enable cURL'), 'error');
    }
    else {
      $customer_keys_response = json_decode($customer_config->getCustomerKeys());

      if (json_last_error() == JSON_ERROR_NONE) {
        $this->config_factory->set('miniorange_ldap_customer_id', $customer_keys_response->id)
            ->set('miniorange_ldap_customer_admin_token', $customer_keys_response->token)
            ->set('miniorange_ldap_customer_admin_email', $username)
            ->set('miniorange_ldap_customer_api_key', $customer_keys_response->apiKey)->save();
        $current_status = 'PLUGIN_CONFIGURATION';
        $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
        Utilities::add_message(t('Successfully retrieved your account.'), 'status');
      }
      else if (is_object($check_customer_response) && $check_customer_response->status == 'TRANSACTION_LIMIT_EXCEEDED')
      {
        \Drupal::messenger()->addMessage(t('An error has occurred. Please try after some time or contact us at <a href="mailto:drupalsupport@xecurify.com" target="_blank">drupalsupport@xecurify.com</a>.'), 'error');
      }
      else {
        Utilities::add_message(t('Invalid credentials.'), 'error');
        return;
      }

    }

  }

  public function already_registered(array &$form, FormStateInterface $form_state){
    $this->config_factory->set('miniorange_ldap_status','ALREADY_REGISTERED')->save();
  }

  /**
   *
   */
  public function miniorange_ldap_back(&$form, $form_state) {
    $current_status = 'CUSTOMER_SETUP';
    $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
    $this->config_factory->clear('miniorange_miniorange_ldap_customer_admin_email')
        ->clear('miniorange_ldap_tx_id')->save();

  }

  /**
   *
   */
  public function miniorange_ldap_resend_otp(&$form, $form_state) {
    $this->config_factory->clear('miniorange_ldap_tx_id')->save();
    $username = $this->config->get('miniorange_ldap_customer_admin_email');
    $customer_config = new MiniorangeLdapCustomer($username, '', NULL, NULL);
    $send_otp_response = json_decode($customer_config->sendOtp());
    if ($send_otp_response->status == 'SUCCESS') {
      // Store txID.
      $this->config_factory->set('miniorange_ldap_tx_id', $send_otp_response->txId)->save();
      $current_status = 'VALIDATE_OTP';
      $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
      Utilities::add_message(t('Verify email address by entering the passcode sent to @username', ['@username' => $username]), 'status');
    }
  }

  /**
   *
   */
  public function miniorange_ldap_validate_otp_submit(&$form, $form_state) {
    $otp_token = $form['miniorange_ldap_customer_otp_token']['#value'];
    $username = $this->config->get('miniorange_ldap_customer_admin_email');
    $tx_id = $this->config->get('miniorange_ldap_tx_id');
    $customer_config = new MiniorangeLdapCustomer($username, '', NULL, $otp_token);
    $validate_otp_response = json_decode($customer_config->validateOtp($tx_id));

    if ($validate_otp_response->status == 'SUCCESS') {
      $this->config_factory->clear('miniorange_ldap_tx_id')->save();
      $password = $this->config->get('miniorange_ldap_customer_admin_password');
      $customer_config = new MiniorangeLdapCustomer($username, '', $password, NULL);
      $create_customer_response = json_decode($customer_config->createCustomer());
      if ($create_customer_response->status == 'SUCCESS') {
        $current_status = 'PLUGIN_CONFIGURATION';
        $this->config_factory->set('miniorange_ldap_status', $current_status)
            ->set('miniorange_ldap_customer_admin_email', $username)
            ->set('miniorange_ldap_customer_admin_token', $create_customer_response->token)
            ->set('miniorange_ldap_customer_id', $create_customer_response->id)
            ->set('miniorange_ldap_customer_api_key', $create_customer_response->apiKey)->save();
        Utilities::add_message(t('Customer account created.'), 'status');

      }
      elseif (trim($create_customer_response->message) == 'Email is not enterprise email.') {
        Utilities::add_message(t('There was an error creating an account for you.<br> You may have entered an invalid Email-Id
            <strong>(We discourage the use of disposable emails) </strong>
            <br>Please try again with a valid email.'), 'error');
        return;
      }
      else {
        Utilities::add_message(t('Error creating customer :- '.$create_customer_response->message), 'error');
        return;
      }
    }
    else {
      Utilities::add_message(t('Error validating OTP'), 'error');
      return;
    }
  }

  public function back_to_register_tab(&$form, $form_state){
    $this->config_factory->set('miniorange_ldap_status','')->save();
  }

}
