<?php


namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\LDAPLOGGERS;
use Drupal\ldap_auth\LDAPFlow;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\Utilities;
use Drupal\Component\Utility\Html;
use Drupal\ldap_auth\SetupNavbarHeader;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ldap_auth\Form\LDAPFormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 *
 */
class MiniorangeLDAP extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_config_client';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $ldap_connect = new LDAPFlow();

    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "ldap_auth/ldap_auth.test",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];

    if (!Utilities::isLDAPInstalled()) {
      $this->config_factory->set('miniorange_ldap_extension_enabled', FALSE)
          ->save();
      $form['markup_reg_msg'] = [
          '#markup' => $this->t('<div class="mo_ldap_enable_extension_message"><b>The PHP LDAP extension is not enabled.</b><br> Please Enable the PHP LDAP Extension for you server to continue. If you want, you refer to the steps given on the link  <a target="_blank" href="https://faq.miniorange.com/knowledgebase/how-to-enable-php-ldap-extension/" >here</a> to enable the extension for your server.</div><br>'),
      ];
    }
    else {
      $this->config_factory->set('miniorange_ldap_extension_enabled', TRUE)->save();
    }

    $is_configured = $this->config->get('miniorange_ldap_is_configured');

    $query_parameter = \Drupal::request()->query->get('action');

    if($query_parameter == 'disable' || $query_parameter == 'enable'){
      $this->config_factory->set('miniorange_ldap_enable_ldap',$query_parameter == 'enable')->save();
      $response = new RedirectResponse($this->getRouteUrl('ldap_auth.ldap_config'));
      $response->send();
      return new Response();
    }
    elseif ($query_parameter == 'delete'){
      self::resetConfiguration($form, $form_state);
      $response = new RedirectResponse($this->getRouteUrl('ldap_auth.ldap_config'));
      $response->send();
      return new Response();
    }

    $form['ldap_css_classes'] = [
        '#markup' => '<div class="mo_ldap_table_layout_1">
                        <div class="mo_ldap_table_layout">',
    ];

    $status = $this->config->get('miniorange_ldap_config_status');

    if(!$is_configured && $status!='review_config' ){
      //show the normal steps to configure the module
      if ($status == '') {
        $status = 'two';
      }

      $config_step = $this->config->get('miniorange_ldap_steps');

      switch ($config_step) {
        case 0:
          $navbar_val = 3;
          break;

        case 1:
          $navbar_val = 25;
          break;

        case 2:
          $navbar_val = 51;
          break;

        case 3:
          $navbar_val = 78;
          break;

        case 4:
          $navbar_val = 100;
          break;

        default:
          $navbar_val = 1;
      }

      /**
       * builds and inserts the Navbar Headers
       */
      SetupNavbarHeader::insertForm($form, $form_state, $navbar_val);

      if ($status == 'one') {
        /**
         * Builds and inserts the Login Settings form
         */
        $this->loginSettingsFormBuilder($form, $form_state, $this->config);

      }
      elseif ($status == 'two') {
        $form['mo_ldap_local_configuration_form_action'] = [
            '#markup' => "<input type='hidden' name='option' id='mo_ldap_local_configuration_form_action' value='mo_ldap_local_save_config'></input>",
        ];
        if ($this->config->get('miniorange_ldap_steps') != 1) {
          /**
           * builds and inserts the Contact LDAP Server Form
           */
          $this->contactLDAPServerFormBuilder($form,$form_state,$this->config);
        }
        if ($this->config->get('miniorange_ldap_steps') == 1) {
          /**
           * builds and inserts the Test Connection Form
           */
              $this->testConnectionFormBuilder($form,$form_state,$this->config);
        }
      }
      elseif ($status == 'three') {
        // Get all Search bases from AD.
        $possible_search_bases = $ldap_connect->getSearchBases();

        $possible_search_bases_in_key_val = [];
        foreach ($possible_search_bases as $search_base) {
          $possible_search_bases_in_key_val[$search_base] = $search_base;
        }
        $possible_search_bases_in_key_val['custom_base'] = 'Provide Custom LDAP Search Base';
        /**
         * Builds and inserts the Select Search Base and Filter Form
         */
          $this->searchBaseAndFilterFormBuilder($form,$form_state,$this->config,$possible_search_bases_in_key_val);
      }
      elseif ($status == 'four') {
        /**
         * Builds and Inserts Test Authentication Form
         */
        $this->testConnectionFormBuilder($form,$form_state,$this->config);
      }

    }
    else{
      if($query_parameter == null){
        //show the table list of the ldap servers
        $this->showLDAPServersTable($form, $form_state,$this->config);
      }
      else if($query_parameter == 'edit'){
        $next_disabled = TRUE;
        if ($this->config->get('miniorange_ldap_test_conn_enabled') == 1) {
          $next_disabled = FALSE;
        }
        $this->reviewConfigFormBuilder($form, $form_state, $this->config, $ldap_connect,$next_disabled);
      }
      else if($query_parameter == 'testing'){
        self::showLDAPTestAuthentication($form,$form_state,$this->config);
      }
    }

    $form['mo_markup_div_imp'] = ['#markup' => '</div>'];

    Utilities::addSupportButton( $form, $form_state);

    return $form;

  }


  /**
   * LDAP Server Configuration reset
   */
  public function resetConfiguration($form, $form_state) {

    $this->config_factory->clear('miniorange_ldap_enable_ldap')
        ->clear('miniorange_ldap_authenticate_admin')
        ->clear('miniorange_ldap_authenticate_drupal_users')
        ->clear('miniorange_ldap_enable_auto_reg')
        ->clear('miniorange_ldap_server')
        ->clear('miniorange_ldap_server_account_username')
        ->clear('miniorange_ldap_server_account_password')
        ->clear('miniorange_ldap_search_base')
        ->clear('miniorange_ldap_username_attribute')
        ->clear('miniorange_ldap_test_username')
        ->clear('miniorange_ldap_test_password')
        ->clear('miniorange_ldap_server_address')
        ->clear('miniorange_ldap_enable_anony_bind')
        ->clear('miniorange_ldap_protocol')
        ->clear('miniorange_ldap_username_attribute_option')
        ->clear('ldap_binding_options')
        ->clear('miniorange_ldap_is_configured')
        ->save();

    $this->config_factory->set('miniorange_ldap_server_port_number', '389')
        ->save();
    $this->config_factory->set('miniorange_ldap_custom_username_attribute', 'samaccountName')
        ->save();
    $this->config_factory->set('miniorange_ldap_config_status', 'two')->save();
    $this->config_factory->set('miniorange_ldap_steps', "0")->save();

    Utilities::add_message($this->t('Configurations removed successfully.'), 'status');
  }


  public function miniorange_ldap_back_3($form, $form_state) {
    $this->config_factory->set('miniorange_ldap_config_status', 'two')->save();
    $this->config_factory->set('miniorange_ldap_steps', "1")->save();
  }

  public function miniorange_ldap_back_5($form, $form_state) {
    $this->config_factory->set('miniorange_ldap_steps', "2")->save();
    $this->config_factory->set('miniorange_ldap_config_status', 'three')
        ->save();
  }


  /**
   * Test Connection.
   */
  public function test_connection_ldap($form, $form_state) {

    $ldap_connect = new LDAPFlow();

    $form_values = $form_state->getValues();
    $ldapconn = $ldap_connect->getConnection();

    if($ldapconn){

      $server_account_username = trim($form_values['miniorange_ldap_server_account_username']);
      $server_account_password = $form_values['miniorange_ldap_server_account_password'];

      $this->config_factory->set("miniorange_ldap_server_account_username",$server_account_username)->save();
      $this->config_factory->set("miniorange_ldap_server_account_password",$server_account_password)->save();

      $bind = @ldap_bind($ldapconn,$server_account_username,$server_account_password);

      if($bind){
        if ($this->config->get('miniorange_ldap_steps') != '4') {
          $this->config_factory->set('miniorange_ldap_steps', "2")->save();
          $this->config_factory->set('miniorange_ldap_config_status', 'three')->save();
        }
        $this->config_factory->set('miniorange_ldap_test_connection','Successfull')->save();
        $this->messenger->addMessage(t("Test Connection is successful."));
      }
      else{

        $msg = 'Unable to make authenticated bind to LDAP server.<br>';

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);
        $msg .= $errors;

        if(ldap_errno($ldapconn) == -1){
          $msg = $msg.'<br> Make sure you have entered correct LDAP server hostname or IP address.If you need further assistance, do not hesitate to contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>.';
        }

        $this->config_factory->set('miniorange_ldap_test_connection',ldap_error($ldapconn).' ['.ldap_errno($ldapconn)."]")->save();
        $this->messenger->addMessage(t($msg),'error');
      }
    }
    else{
      $msg = $this->t("Cannot connect to LDAP Server. Make sure you have entered correct LDAP server hostname or IP address. <br>If there is a firewall, please open the firewall to allow incoming requests to your LDAP server from your Drupal site IP address and below specified port number. <br>If you still face the same issue then contact us drupalsupport@xecurify.com");

      $this->config_factory->set('miniorange_ldap_test_connection',"Cannot contact to LDAP Server")->save();
      $this->messenger->addMessage($msg,'error');
    }

  }


  public function miniorange_ldap_next_1($form, $form_state) {

    $form_values = $form_state->getValues();
    $this->config_factory->set('miniorange_ldap_config_status', 'review_config')->save();
    $this->config_factory->set('miniorange_ldap_steps', "4")->save();
    $this->config_factory->set('miniorange_ldap_is_configured', 1)->save();
    $enable_ldap = $form_values['miniorange_ldap_enable_ldap'];

    $this->config_factory->set('miniorange_ldap_enable_ldap', $enable_ldap)->save();
    $message = 'Congratulations! You have successfully configured the module.<br>Now you can login to your Drupal site using the LDAP Credentials.<br>If you encounter any problems or need assistance, please do not hesitate to contact us at <a href="'.MiniorangeLDAPConstants::SUPPORT_EMAIL.'">'.MiniorangeLDAPConstants::SUPPORT_EMAIL.'</a>. ';
    Utilities::add_message(t($message),'status');
    $form_state->setRedirect('ldap_auth.ldap_config');
  }

  /**
   *
   */
  public function miniorange_ldap_next3($form, $form_state) {
    $this->config_factory->set('miniorange_ldap_config_status', 'one')->save();
    $form_values = $form_state->getValues();

    if (!empty($form['search_base_attribute']['#value'])) {
      $searchBase = $form['search_base_attribute']['#value'];
      $searchBaseCustomAttribute = NULL;
      if ($searchBase == 'custom_base') {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', 'custom')
            ->save();
        $searchBaseCustomAttribute = trim($form['miniorange_ldap_custom_sb_attribute']['#value']);
      }
      $ldap_connect = new LDAPFlow();
      $ldap_connect->setSearchBase($searchBase, $searchBaseCustomAttribute);
      $this->config_factory->set('miniorange_ldap_steps', "3")->save();
    }

    $email_attribute = $form_values['miniorange_ldap_email_attribute'] == 'custom' ? trim($form_values['miniorange_ldap_custom_email_attribute']) : $form_values['miniorange_ldap_email_attribute'];
    $email_attribute = empty($email_attribute) ? 'mail' : $email_attribute;
    $this->config_factory->set('miniorange_ldap_email_attribute', $email_attribute)->save();

    if (!empty($form['ldap_auth']['settings']['username_attribute']['#value'])) {
      $usernameAttribute = $form['ldap_auth']['settings']['username_attribute']['#value'];
      $usernameCustomAttribute = NULL;
      if ($usernameAttribute == 'custom') {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', 'custom')
            ->save();
        $usernameCustomAttribute = trim($form['miniorange_ldap_custom_username_attribute']['#value']);
        if (trim($usernameCustomAttribute) == '') {
          $usernameCustomAttribute = 'samaccountName';
        }
        $this->config_factory->set('miniorange_ldap_custom_username_attribute', $usernameCustomAttribute)
            ->save();
        $ldap_connect->setSearchFilter($usernameCustomAttribute);
      }
      else {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', $usernameAttribute)
            ->save();
        $ldap_connect->setSearchFilter($usernameAttribute);
      }
    }

    if (!empty($form['miniorange_ldap_test_username']['#value'])) {
      $testUsername = $form['miniorange_ldap_test_username']['#value'];
      $this->config_factory->set('miniorange_ldap_test_username', $testUsername)
          ->save();
    }

    if (!empty($form['miniorange_ldap_test_password']['#value'])) {
      $testPassword = $form['miniorange_ldap_test_password']['#value'];
      $this->config_factory->set('miniorange_ldap_test_password', $testPassword)
          ->save();
    }
  }

  public function back_to_contact_server(&$form, &$form_state) {
    $this->config_factory->set('miniorange_ldap_config_status', 'two')
        ->save();
    $this->config_factory->set('miniorange_ldap_steps', "0")->save();
  }

  /**
   * Contact LDAP server.
   */
  public function test_ldap_connection($form, $form_state) {

    LDAPLOGGERS::addLogger('L101: Entered Contact LDAP Server ', '', __LINE__, __FUNCTION__, __FILE__);

    if (!Utilities::isLDAPInstalled()) {
      LDAPLOGGERS::addLogger('L102: PHP_LDAP Extension is not enabled', '', __LINE__, __FUNCTION__, __FILE__);
      Utilities::add_message(t('You have not enabled the PHP LDAP extension'), 'error');
      return;
    }

    $form_values = $form_state->getValues();

    $server_address = "";

    if (!empty(trim($form_values['miniorange_ldap_server_address']))) {
      $server_address = Html::escape(trim($form_values['miniorange_ldap_server_address']));
    }
    else{
      Utilities::add_message(t('LDAP Server Address can not be empty.'), 'error');
      return;
    }

    if (isset($form_values['miniorange_ldap_protocol']) && !empty($form_values['miniorange_ldap_protocol'])) {
      $protocol = Html::escape($form_values['miniorange_ldap_protocol']);
    }

    $server_name = $protocol . $server_address;

    if (!empty(trim($form_values['miniorange_ldap_server_port_number']))) {
      $port_number = Html::escape(trim($_POST['miniorange_ldap_server_port_number']));
      $server_name = $server_name . ':' . $port_number;
    }
    else{
      Utilities::add_message(t('LDAP Server Address Port can not be empty.'), 'error');
      return;
    }


    $this->config_factory->set('miniorange_ldap_server', $server_name)->save();
    $this->config_factory->set('miniorange_ldap_server_address', $server_address)->save();
    $this->config_factory->set('miniorange_ldap_protocol', $protocol)->save();
    $this->config_factory->set('miniorange_ldap_server_port_number', $port_number)->save();

    $ldap_connect = new LDAPFlow();
    $ldap_connect->setServerName($server_name);

    $ldapconn = $ldap_connect->getConnection();
    LDAPLOGGERS::addLogger('DL1: ldapconn getConnection: ', $ldapconn, __LINE__, __FUNCTION__, __FILE__);

    if ($ldapconn) {

      //checking anonymous bind
      $anonymous_bind = @ldap_bind($ldapconn);

      if ($anonymous_bind) {
        $this->config_factory->set("supports_anonymous_bind",1)->save();
      }
      else{
        $this->config_factory->set("supports_anonymous_bind",0)->save();
      }

      if ($this->config->get('miniorange_ldap_steps') != '4') {
        $this->config_factory->set('miniorange_ldap_steps', "1")->save();
      }

      $this->config_factory->set('miniorange_ldap_contacted_server', "Successful")->save();
      $this->config_factory->set('miniorange_ldap_test_conn_enabled', "1")->save();
      $this->messenger->addMessage("Congratulations! You are successfully able to connect to your LDAP Server.",'status');
    }
    else {
      $this->config_factory->set('miniorange_ldap_contacted_server', "Failed")->save();
      $this->config_factory->set('miniorange_ldap_test_conn_enabled', "0")->save();

      $msg = $this->t("Cannot connect to LDAP Server. Make sure you have entered correct LDAP server hostname or IP address. <br>If there is a firewall, please open the firewall to allow incoming requests to your LDAP server from your Drupal site IP address and below specified port number. <br>If you still face the same issue then contact us <a href='mailto::drupalsupport@xecurify.com'>drupalsupport@xecurify.com</a>.");
      $this->messenger->addMessage($msg,'error');
    }

  }

  /**
   *
   */
  public function miniorange_ldap_review_changes($form, $form_state) {

    $ldap_connect = new LDAPFlow();

    $form_values = $form_state->getValues();
    $this->config_factory->set('miniorange_ldap_enable_ldap', $form_values['miniorange_ldap_enable_ldap'])->save();


    $protocol = $form_values['miniorange_ldap_protocol'];
    $server_address = Html::escape(trim($form_values['miniorange_ldap_server_address']));
    $port_number = Html::escape(trim($form_values['miniorange_ldap_server_port_number']));

    if(empty($server_address) || empty($port_number)){
      Utilities::add_message(t('LDAP Server address or Port can not be empty.'), 'error');
      return;
    }

    $server_name = $protocol . $server_address . ":". $port_number;

    $this->config_factory->set("miniorange_ldap_server",$server_name)->save();
    $this->config_factory->set("miniorange_ldap_server_address",$server_address)->save();

    if(!empty($form_values['miniorange_ldap_server_account_username'])){
      $this->config_factory->set('miniorange_ldap_server_account_username', $form_values['miniorange_ldap_server_account_username'])
          ->save();
    }
    if(!empty($form_values['miniorange_ldap_server_account_password'])){
      $this->config_factory->set('miniorange_ldap_server_account_password', $form_values['miniorange_ldap_server_account_password'])
          ->save();
    }


    if (!empty($form_values['search_base_attribute'])) {
      $searchBase = $form_values['search_base_attribute'];
      $searchBaseCustomAttribute = NULL;
      if ($searchBase == 'custom_base') {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', 'custom')
            ->save();
        $searchBaseCustomAttribute = trim($form_values['miniorange_ldap_custom_sb_attribute']);
      }
      $ldap_connect = new LDAPFlow();
      $ldap_connect->setSearchBase($searchBase, $searchBaseCustomAttribute);
    }

    if (!empty($form_values['username_attribute'])) {
      $usernameAttribute = $form_values['username_attribute'];
      if ($usernameAttribute == 'custom') {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', 'custom')->save();
        $usernameCustomAttribute = trim($form_values['miniorange_ldap_custom_username_attribute']);
        if (trim($usernameCustomAttribute) == '') {
          $usernameCustomAttribute = 'samaccountName';
        }
        $this->config_factory->set('miniorange_ldap_custom_username_attribute', $usernameCustomAttribute)->save();
        $this->config_factory->set('miniorange_ldap_username_attribute', $usernameCustomAttribute)->save();
        $ldap_connect->setSearchFilter($usernameCustomAttribute);
      }
      else {
        $this->config_factory->set('miniorange_ldap_username_attribute_option', $usernameAttribute)
            ->save();
        $this->config_factory->set('miniorange_ldap_username_attribute', $usernameAttribute)
            ->save();
        $ldap_connect->setSearchFilter($usernameAttribute);
      }
    }

    //email attribute saving
    $email_attribute = $form_values['miniorange_ldap_email_attribute'] == 'custom' ? trim($form_values['miniorange_ldap_custom_email_attribute']) : $form_values['miniorange_ldap_email_attribute'];
    $email_attribute = empty($email_attribute) ? 'mail' : trim($email_attribute);
    $this->config_factory->set('miniorange_ldap_email_attribute', $email_attribute)->save();

    $this->config_factory->set('miniorange_ldap_steps', "4")->save();
    Utilities::add_message(t('Configuration updated successfully.'), 'status');
    $form_state->setRedirect('ldap_auth.ldap_config');

  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }


  /**
   * LDAP Connection Form
   */
  protected function contactLDAPServerFormBuilder(array &$form, FormStateInterface $form_state,$config){

    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');
    $form['ldap_server'] = [
        '#markup' => t('
        <table class="table-header-properties">
            <tr class="custom-table-properties">
                <td class="shift-text-left custom-table-properties"><h4>Enter Your LDAP Server URL:</h4></td>
                <td class="custom-table-properties"><a class="button button--small btn-right" href ="https://www.youtube.com/watch?v=wBe8T6FLKx4" target="_blank">Setup Video</a><a class="button button--small btn-right" href="https://plugins.miniorange.com/guide-to-configure-ldap-ad-integration-module-for-drupal" target="_blank">Setup Guide</a></td>
            </tr>
        </table>
      '),
    ];
    $form['ldap_server_url_markup_start'] = [
        '#markup' =>'<div class="ldap_Server_row">',
    ];
    $form['miniorange_ldap_options'] = [
        '#type' => 'value',
        '#id' => 'miniorange_ldap_options',
        '#value' => [
            'ldap://' => t('ldap://'),
            'ldaps://' => t('ldaps://'),
        ],
    ];
    $form['miniorange_ldap_protocol'] = [
        '#id' => 'miniorange_ldap_protocol',
        '#type' => 'select',
        '#prefix' => '<div class="ldap-column left">',
        '#suffix' => '</div>',
        '#default_value' => $config->get('miniorange_ldap_protocol'),
        '#options' => $form['miniorange_ldap_options']['#value'],
        '#attributes' => ['style' => 'width:100%'],
    ];
    $form['miniorange_ldap_server_address'] = [
        '#type' => 'textfield',
        '#id' => 'miniorange_ldap_server_address',
        '#prefix' => '<div class="ldap-column middle">',
        '#suffix' => '</div>',
        '#default_value' => $config->get('miniorange_ldap_server_address'),
        '#attributes' => [
            'style' => 'width:100%;',
            'placeholder' => 'Enter your server-address or IP',
        ],
        '#required' => TRUE,
    ];
    $form['miniorange_ldap_server_port_number'] = [
        '#type' => 'textfield',
        '#prefix' => '<div class="ldap-column right">',
        '#suffix' => '</div>',
        '#default_value' => $config->get('miniorange_ldap_server_port_number'),
        '#attributes' => [
            'style' => 'width:100%;',
            'placeholder' => '<port>'
        ],
    ];

    $form['ldap_server_url_markup_end'] = [
        '#markup' => t('</div>'),
    ];

    $form['ldap_server_url_description'] = [
        '#markup' =>t('<small>Specify the host name for the LDAP server eg: ldap://myldapserver.domain:389 , ldap://89.38.192.1:389. When using SSL, the host may have to take the form ldaps://host:636</small>'),
    ];

    $form['miniorange_ldap_enable_tls'] = [
        '#type' => 'checkbox',
        '#id' => 'check',
        '#disabled' => 'true',
        '#title' => t('Enable TLS (Check this only if your server use TLS Connection) <a href='.$upgrade_plan_link.'><strong>[Premium, All-Inclusive]</strong></a>'),
    ];
    $form['miniorange_ldap_contact_server_button'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Contact LDAP Server'),
        '#submit' => ['::test_ldap_connection'],
    ];

  }

  /**
   *  LDAP Binding Form
   */
  protected function testConnectionFormBuilder(array &$form, FormStateInterface $form_state,$config){

    $form['ldap_server_test_bind_connection'] = [
        '#markup' => t('
        <table class="table-header-properties">
            <tr class="custom-table-properties">
                <td class="shift-text-left custom-table-properties"><h4>Service Account / Bind Details</h4></td>
                <td class="custom-table-properties"><a class="button button--small btn-right" href ="https://www.youtube.com/watch?v=wBe8T6FLKx4" target="_blank">Setup Video</a><a class="button button--small btn-right" href="https://plugins.miniorange.com/guide-to-configure-ldap-ad-integration-module-for-drupal" target="_blank">Setup Guide</a></td>
            </tr>
        </table>
      '),
    ];

    // description when anonymous bind support
    if($config->get('supports_anonymous_bind')){
      $form['miniorange_ldap_anonymous_bind_markup'] = [
          '#markup' => t('<div class="mo_ldap_highlight_background_note_1" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">If you want to bind anonymously to your LDAP server click on the <strong>Test Connection & Proceed</strong> without entering any credentials.</div>'),
      ];
    }

    $form['miniorange_ldap_server_account_username'] = [
        '#type' => 'textfield',
        '#title' => t('Bind Account DN:'),
        '#default_value' => $config->get('miniorange_ldap_server_account_username'),
        '#description' => t("Enter the <i>Service Account username</i> or the <i>Distinguished Name (DN)</i> for the account you wish to bind connection to your LDAP Server"),
        '#attributes' => [
            'placeholder' => 'CN=service,DC=domain,DC=com',
        ],
        '#required' => $config->get('supports_anonymous_bind') == 0,
        '#size' => 60,
    ];
    $form['miniorange_ldap_server_account_password'] = [
        '#type' => 'password',
        '#title' => t('Bind Account Password:'),
        '#description' => t('Enter the password for your Service Account'),
        '#default_value' => $config->get('miniorange_ldap_server_account_password'),
        '#attributes' => [
            'placeholder' => 'Enter password here',
        ],
        '#required' => $config->get('supports_anonymous_bind') == 0 ,
        '#size' => 60,
    ];

    if($config->get('miniorange_ldap_server_account_password')){
        $form['miniorange_ldap_server_account_password']['#attributes'] = ['value' => $config->get('miniorange_ldap_server_account_password')];
    }

    $form['miniorange_ldap_test_connection_button'] = [
        '#type' => 'submit',
        '#value' => t('&#171; Back'),
        '#button_type' => 'danger',
        '#limit_validation_errors' => [],
        '#submit' => ['::back_to_contact_server'],
    ];

    $form['next_step_x'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Test Connection & Proceed &#187;'),
        '#attributes' => ['style' => 'float: right;display:block;'],
        '#submit' => ['::test_connection_ldap'],
    ];

  }

  /**
   * search Base and Filter Form
   */
  protected function searchBaseAndFilterFormBuilder(array &$form, FormStateInterface $form_state,$config,$possible_search_bases_in_key_val=[]){

   $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');
    $form['search_base_attribute'] = [
        '#id' => 'miniorange_ldap_search_base_attribute',
        '#title' => t('Search Base(s):'),
        '#type' => 'select',
        '#description' => t('Search Base indicates the location in your LDAP server where your users reside. Select the Distinguished Name(DN) of the Search Base object from the above dropdown.<br>Multiple Search Bases are supported in the <a href='.$upgrade_plan_link.'><strong>[Premium, All-Inclusive]</strong></a> version of the module.'),
        '#default_value' => $config->get('miniorange_ldap_search_base'),
        '#options' => $possible_search_bases_in_key_val,//$form['miniorange_search_base_options']['#value'],
        '#attributes' => ['style' => 'width:65%;height:30px'],
    ];
    $form['miniorange_ldap_custom_sb_attribute'] = [
        '#type' => 'textfield',
        '#title' => t('Other Search Base(s):'),
        '#default_value' => empty($config->get('miniorange_ldap_custom_sb_attribute')) ? reset($possible_search_bases_in_key_val) : $config->get('miniorange_ldap_custom_sb_attribute'),
        '#states' => ['visible' => [':input[name = "search_base_attribute"]' => ['value' => 'custom_base']]],
        '#attributes' => ['style' => 'width:65%;'],
        '#maxlength' => 1024,
    ];

    // Username Attribute
    $ldap_attribute_option = [
        'samaccountname' => t('samaccountname'),
        'mail' => t('mail'),
        'userprincipalname' => t('userprincipalname'),
        'cn' => t('cn'),
        'sn' => t('sn'),
        'givenname' => t('givenname'),
        'uid' => t('uid'),
        'displayname' => t('displayname'),
        'custom' => t('other'),
    ];
    $form['ldap_auth']['settings']['username_attribute'] = [
        '#id' => 'miniorange_ldap_username_attribute',
        '#title' => t('LDAP Username Attribute / Search Filter:'),
        '#type' => 'select',
        '#description' => t('Select the LDAP attribute by which the user will be searched in the LDAP server. Using this LDAP attribute value your user can login to Drupal.<br> <b>For example:</b> If you want the user to login to Drupal using their samaccountName ( the one present in the LDAP server), you can select <b>samaccountName</b> in the dropdown.<br>You can even search for your user using a Custom Search Filter in the <a href='.$upgrade_plan_link.'><strong>[Premium, All-Inclusive]</strong></a> version of the module<div><br>'
        ),
        '#default_value' => $config->get('miniorange_ldap_username_attribute_option'),
        '#options' => $ldap_attribute_option,
        '#attributes' => ['style' => 'width:65%;'],
    ];

    $form['miniorange_ldap_custom_username_attribute'] = [
        '#type' => 'textfield',
        '#title' => t('Other Username Attribute'),
        '#default_value' => $config->get('miniorange_ldap_custom_username_attribute'),
        '#states' => [
            'visible' => [
                ':input[name = "username_attribute"]' => ['value' => 'custom']
            ],
            'required' => [
                ':input[name = "username_attribute"]' => ['value' => 'custom']
            ]
        ],
        '#attributes' => ['style' => 'width:65%;'],
    ];

    // Email Attribute
    $saved_email_attribute = $config->get('miniorange_ldap_email_attribute');
    $form['miniorange_ldap_email_attribute'] = [
        '#type' => 'select',
        '#title' => t('LDAP Email Attribute'),
        '#options' => $ldap_attribute_option,
        '#required' => false,
        '#attributes' => ['style' => 'width:65%;'],
        '#default_value' => $saved_email_attribute != NULL && in_array($saved_email_attribute,$ldap_attribute_option)? $saved_email_attribute : 'custom',
        '#description' => t("Select the LDAP attribute in which you get the email address of your LDAP users."),
    ];

    $form['miniorange_ldap_custom_email_attribute'] = [
        '#type' => 'textfield',
        '#title' => t('Other LDAP Email Attribute'),
        '#default_value' => $saved_email_attribute,
        '#states' => [
            'visible' => [
                ':input[name = "miniorange_ldap_email_attribute"]' => ['value' => 'custom']
            ],
            'required' => [
                ':input[name = "miniorange_ldap_email_attribute"]' => ['value' => 'custom']
            ]
        ],
        '#attributes' => ['style' => 'width:65%;'],
    ];

    //image attribute
    $form['miniorange_ldap_photo_attribute'] = [
        '#type' => 'textfield',
        '#title' => t('Image/Profile Attribute'),
        '#disabled' => TRUE,
        '#attributes' => [
            'style' => 'width:65%;',
            'placeholder' => t('Enter image attribute eg. jpegphoto, thumbnailphoto'),
        ],
        '#description' => t("Enter the LDAP attribute in which you get the profile photo/image of your users. <a href=".$upgrade_plan_link."><b>[All-Inclusive]</b></a>"),
    ];

    $form['back_step_3'] = [
        '#type' => 'submit',
        '#button_type' => 'danger',
        '#prefix' => "<div class='pito_enable_alignment'>",
        '#value' => t('&#171; Back'),
        '#submit' => ['::miniorange_ldap_back_3'],
        '#attributes' => ['style' => 'display: inline-block;'],
    ];

    $form['next_step_3'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Next &#187; '),
        '#suffix' => "</div></div>",
        '#attributes' => ['style' => 'float: right;display:block;'],
        '#submit' => ['::miniorange_ldap_next3'],
    ];
  }

  /**
   * Login Setting Form
   */
  protected function loginSettingsFormBuilder(array &$form, FormStateInterface $form_state,$config){

    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');

    $form['miniorange_ldap_enable_ldap_markup'] = [
        '#markup' => t("<h3 style='margin-top: 0%'>Login Settings:</h3><hr style='margin-top: -0.5%'>"),
    ];

    $form['miniorange_ldap_enable_ldap'] = [
        '#type' => 'checkbox',
        '#description' => t('Select this checkbox to enable Login using LDAP/Active Directory credentials.'),
        '#title' => t('Enable Login with LDAP'),
        '#default_value' => $config->get('miniorange_ldap_enable_ldap'),
    ];

    $form['miniorange_ldap_enable_auto_reg'] = [
        '#type' => 'checkbox',
        '#title' => t('Automatically Create LDAP Users in Drupal if they DO NOT EXIST in Drupal.<a href='.$upgrade_plan_link.'><strong>[Premium, All-Inclusive]</strong></a>'),
        '#disabled' => 'true',
        '#default_value' => $config->get('miniorange_ldap_enable_auto_reg'),
    ];

    $form['set_of_radiobuttons']['miniorange_ldap_authentication'] = [
        '#type' => 'radios',
        '#disabled' => true,
        '#title' => t('Authentication restrictions: <a href='.$upgrade_plan_link.'>[Premium, All-Inclusive]</a>'),
        '#default_value' => is_null($config->get('miniorange_ldap_authentication')) ? 0 : $config->get('miniorange_ldap_authentication'),
        '#options' => [
            0 => t('User can login using both their Drupal and LDAP credentials'),
            1 => t('User can login in Drupal using their LDAP credentials and Drupal admins can also login using their local Drupal credentials'),
            2 => t('Users can only login using their LDAP credentials'),
        ],
        '#disabled_values' => array(1, 2),
    ];

    $form['back_step_3'] = [
        '#type' => 'submit',
        '#button_type' => 'danger',
        '#value' => t('&#171; Back'),
        '#submit' => ['::miniorange_ldap_back_5'],
        '#attributes' => ['style' => 'width: fit-content;display:inline-block;'],
    ];

    $form['next_step_1'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save & Next &#187; '),
        '#attributes' => ['style' => 'float: right;display:block;'],
        '#submit' => ['::miniorange_ldap_next_1'],
    ];

  }

  /**
   * Review of the LDAP configuration Form
   */
  protected function reviewConfigFormBuilder(array &$form, FormStateInterface $form_state,$config,$ldap_connect,$next_disabled){

    //Contact LDAP Server
    $form['review_config'] = array(
        '#type' => 'details',
        '#title' => t('Contact LDAP Server [ '.$config->get('miniorange_ldap_server').' ]'),
    );
    $this->contactLDAPServerFormBuilder($form['review_config'],$form_state,$config);
    //unset the 'Contact LDAP Server' primary button_type and change the submit function
    unset($form['review_config']['miniorange_ldap_contact_server_button']['#button_type']);
    $form['review_config']['miniorange_ldap_contact_server_button']['#submit'] = ['::test_ldap_connection_review'];

    //LDAP Binding
    $form['review_config_test_connection'] = array(
        '#type' => 'details',
        '#title' => t('LDAP Binding'),
        '#open'=> FALSE,
    );
    $this->testConnectionFormBuilder($form['review_config_test_connection'],$form_state,$config);
    unset($form['review_config_test_connection']['miniorange_ldap_server_account_password']['#required']);
    unset($form['review_config_test_connection']['miniorange_ldap_test_connection_button']);

    //Change the name of 'Test Connection & Proceed' button and unset the css and button_type
    unset($form['review_config_test_connection']['next_step_x']['#attributes']);
    unset($form['review_config_test_connection']['next_step_x']['#button_type']);
    $form['review_config_test_connection']['next_step_x']['#value'] = $this->t('Test Connection');

    //LDAP search Base and Filter
    $form['review_config_set_filter_base'] = array(
        '#type' => 'details',
        '#title' => t('Set Search Base & Filter'),
        '#open' => FALSE,
    );

    $possible_search_bases = $ldap_connect->getSearchBases();
    $possible_search_bases_in_key_val = [];
    foreach ($possible_search_bases as $search_base) {
      $possible_search_bases_in_key_val[$search_base] = $search_base;
    }
    $possible_search_bases_in_key_val['custom_base'] = 'Provide Custom LDAP Search Base';

    $this->searchBaseAndFilterFormBuilder($form['review_config_set_filter_base'],$form_state,$config,$possible_search_bases_in_key_val);
    unset($form['review_config_set_filter_base']['back_step_3']);
    unset($form['review_config_set_filter_base']['next_step_3']);

    //Login settings
    $form['review_login_settings_config'] = array(
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('User Login Settings'),
    );
    $this->loginSettingsFormBuilder( $form['review_login_settings_config'],$form_state,$config);
    unset($form['review_login_settings_config']['back_step_3']);
    unset($form['review_login_settings_config']['next_step_1']);

    $form['save_config_edit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save Changes '),
        '#submit' => ['::miniorange_ldap_review_changes'],
        '#prefix' => '<br><div class="container-inline">'
    ];
    $form['reset_configuration'] = [
        '#type' => 'submit',
        '#value' => t('Reset Configurations'),
        '#submit' => ['::resetConfiguration'],
    ];

    $form['miniorange_back_button'] = [
        '#type' => 'link',
        '#title' => t('&#171; Back'),
        '#attributes' => [
            'class' => [
                'button',
                'button--danger',
            ],
        ],
        '#url' => Url::fromRoute('ldap_auth.ldap_config'),
        '#suffix' => '</div><br><br>',
    ];

  }

  /**
   * Contact LDAP server.
   */
  public function test_ldap_connection_review($form, $form_state) {

    LDAPLOGGERS::addLogger('LR101: Entered Review Contact LDAP Server ', '', __LINE__, __FUNCTION__, __FILE__);

    if (!Utilities::isLDAPInstalled()) {
      LDAPLOGGERS::addLogger('LR102: PHP_LDAP Extension is not enabled', '', __LINE__, __FUNCTION__, __FILE__);
      Utilities::add_message(t('You have not enabled the PHP LDAP extension'), 'error');
      return;
    }

    $anony_bind = "";

    $form_values = $form_state->getValues();

    $protocol = $form_values['miniorange_ldap_protocol'];
    $server_address = Html::escape(trim($form_values['miniorange_ldap_server_address']));
    $port_number = Html::escape(trim($form_values['miniorange_ldap_server_port_number']));

    if(empty($server_address) || empty($port_number)){
      Utilities::add_message(t('LDAP Server address or Port can not be empty.'), 'error');
      return;
    }

    $this->config_factory->set('miniorange_ldap_protocol', $protocol)->save();
    $this->config_factory->set('miniorange_ldap_server_port_number', $port_number)->save();
    $server_name = $protocol . $server_address . ":". $port_number;

    $this->config_factory->set('miniorange_ldap_enable_anony_bind', $anony_bind)->save();

    $ldap_connect = new LDAPFlow();
    $ldap_connect->setServerName($server_name);
    $ldapconn = $ldap_connect->getConnection();
    LDAPLOGGERS::addLogger('DLR1: ldapconn getConnection: ', $ldapconn, __LINE__, __FUNCTION__, __FILE__);
    if ($ldapconn) {
      if ($this->config->get('miniorange_ldap_steps') != '4') {
        $this->config_factory->set('miniorange_ldap_steps', "1")->save();
      }
      $this->config_factory->set('miniorange_ldap_contacted_server', "Successful")
          ->save();
      $this->config_factory->set('miniorange_ldap_test_conn_enabled', "1")
          ->save();
      Utilities::add_message(t('Congratulations, you were able to successfully connect to your LDAP Server'), 'status');
      return;
    }
    else {
      $this->config_factory->set('miniorange_ldap_contacted_server', "Failed")
          ->save();
      $this->config_factory->set('miniorange_ldap_test_conn_enabled', "0")
          ->save();
      Utilities::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
      return;
    }
  }

  /**
   * Show the ldap server table
   */

  public function showLDAPServersTable(array &$form, FormStateInterface $form_state,$config = null){

    $caption = Markup::create('<div style="display: flex;justify-content: space-between;"><h3>Configured LDAP server</h3><span><a class="button button--primary use-ajax" data-dialog-options="{&quot;width&quot;:&quot;55%&quot;}"
data-dialog-type="modal" href="requestSupport/addLdapServer">+ Add LDAP Server</a></span></div><br>');
    $header = [
            'ldap_server'=> [
                'data' => t('LDAP Server')
              ],
            'service_account' => [
                'data' => t('Service Account')
              ],
            'status' => [
                'data' => t('LDAP Login')
              ],
            'test' => [
                'data' => t('Test')
              ],
            'action' => [
                'data' => t('Action')
              ],
    ];

    $server_url = $config->get('miniorange_ldap_server') ?? 'Not configured';
    $service_account = $config->get('miniorange_ldap_server_account_username') ?? 'No Account Found';
    $service_account = empty($service_account) && $config->get('supports_anonymous_bind') ? 'Anonymous Bind' : $service_account;

    $ldap_enabled = $config->get('miniorange_ldap_enable_ldap') ? 'Enabled' : 'Disabled';
    $test_button = [
        '#type' => 'link',
        '#title' => t('Test Authentication'),
        '#attributes' => [
            'class' => [
                'button',
                'button--primary',
                'button--small',
            ],
        ],
        '#url' => Url::fromUri($this->getRouteUrl('ldap_auth.ldap_config').'?action=testing'),
    ];

    $status_title = $config->get('miniorange_ldap_enable_ldap') ? 'Disable' : 'Enable';
    $drop_button = [
        '#type' => 'dropbutton',
        '#dropbutton_type' => 'small',
        '#links' => [
            'edit' => [
                'title' => t('Edit'),
                'url' => Url::fromRoute('ldap_auth.ldap_config',['action'=> 'edit']),
            ],
            'status' => [
                'title' => t($status_title),
                'url' => Url::fromRoute('ldap_auth.ldap_config',['action'=> strtolower($status_title)]),
            ],
           'ldap_sso' => [
               'title' => t('SSO/Windows Auto Login'),
               'url' =>  Url::fromRoute('ldap_auth.signin_settings'),
           ],
           'ldap_import' => [
                'title' => t('Import LDAP Users'),
                'url' => Url::fromRoute('ldap_auth.user_sync'),
            ],
            'delete' => [
                'title' => t('Delete'),
                'url' => Url::fromRoute('ldap_auth.ldap_config',['action'=> 'delete']),
            ],
        ],
    ];

    $rows= [
        [
           'ldap_server' => $server_url,
           'service_account' => $service_account,
           'status' => $ldap_enabled,
            'test' => [
                'data' => $test_button
              ],
            'action' => [
               'data' => $drop_button
           ],
        ],
    ];

    $form['ldap_server_list_table'] = [
        '#type' => 'table',
        '#caption' => $caption,
        '#header' => $header,
        '#rows'  => $rows,
    ];

    return $form;
  }

  public static function showLDAPTestAuthentication(array &$form, FormStateInterface $form_state,$config = null){

    $ldap_conn = new LDAPFlow();
    $search_base = $ldap_conn->getSearchBase();
    $filter = $ldap_conn->getSearchFilter();
    $ldapServer = $ldap_conn->getServerName();


    $form['review_test_authentication_config'] = array(
        '#type' => 'fieldset',
    );
    $form['review_test_authentication_config']['miniorange_ldap_testuser'] = [
        '#markup' => t("<div id='test_authentication'><h4>Test Authentication</h4></div><hr>
            <div class='mo_ldap_highlight_background_note_1'>Please enter user's LDAP username and password to test your configurations. The user will be searched based on your search filter i.e <b>$filter</b> of the user present under the search base <b>$search_base</b></div>
            "),
    ];

    $form['review_test_authentication_config']['miniorange_ldap_test_account_username'] = [
        '#type' => 'textfield',
        '#title' => t('Username:'),
        '#id' => 'miniorange_ldap_test_account_username',
        '#default_value' => $config->get('mo_last_authenticated_user'),
    ];

    $form['review_test_authentication_config']['miniorange_ldap_test_account_password'] = [
        '#type' => 'password',
        '#title' => t('Password:'),
        '#id' => 'miniorange_ldap_test_account_password',
    ];

    $form['review_test_authentication_config']['miniorange_test_configuration'] = [
        '#type' => 'submit',
        '#prefix' => "<br>",
        '#value' => t('Test Authentication'),
        '#attributes' => [
            'onclick' => 'ldap_testConfig()',
            'class' => ['use-ajax'],
        ],
        '#ajax' => ['event' => 'click'],
    ];

    $form['review_test_authentication_config']['miniorange_test_back_button'] = [
        '#type' => 'link',
        '#title' => t('&#171; Back'),
        '#url' => Url::fromRoute('ldap_auth.ldap_config'),
    ];

    return $form;

  }
}
