<?php

namespace Drupal\ldap_auth;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Config;

/**
 *
 */
class LDAPFlow {

  private $ldapconn;

  private $bind;

  private $anon_bind;

  private $server_name;

  private $service_account_username;

  private $service_account_password;

  private $search_base;

  private $search_filter;

  private $custom_base;

  private Config $config_factory;

  private ImmutableConfig $config;

  /**
   *
   */
  public function __construct() {
    $this->server_name = \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server') ? \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server') : "";
    $this->service_account_username = \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server_account_username') ? \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server_account_username') : "";
    $this->service_account_password = \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server_account_password') ? \Drupal::config('ldap_auth.settings')
        ->get('miniorange_ldap_server_account_password') : "";


    $this->config = \Drupal::config('ldap_auth.settings');
    $this->config_factory = \Drupal::configFactory()->getEditable('ldap_auth.settings');
  }

  /**
   *
   */
  public function getConnection() {
    $this->server_name = $this->getServerName();
    LDAPLOGGERS::addLogger('DL2: Server Name: ',$this->server_name,__LINE__,__FUNCTION__,__FILE__);
    $this->ldapconn = @ldap_connect($this->server_name);

    if($this->ldapconn){
      LDAPLOGGERS::addLogger('DL3: ldap_connect: ',ldap_error($this->ldapconn),__LINE__,__FUNCTION__,__FILE__);
    }
    else{
      LDAPLOGGERS::addLogger('DL3: ldap_connect: ', "FALSE",__LINE__,__FUNCTION__,__FILE__);
    }

    if($this->ldapconn){

      if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
        ldap_set_option($this->ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
      }

      ldap_set_option($this->ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_set_option($this->ldapconn, LDAP_OPT_REFERRALS, 0);
      ldap_set_option($this->ldapconn, LDAP_OPT_DIAGNOSTIC_MESSAGE, '');

      $this->setLdapconn($this->ldapconn);

    }

    return $this->ldapconn;
  }

  /**
   * @param mixed $ldapconn
   */
  public function setLdapconn($ldapconn) {
    $this->ldapconn = $ldapconn;
  }

  /**
   * @return mixed
   */
  public function getLdapconn() {
    return $this->ldapconn;
  }

  /**
   * @return mixed
   */
  public function getServerName() {
    return $this->server_name;
  }

  /**
   * @param mixed $server_name
   */
  public function setServerName($server_name) {
    $this->config_factory
        ->set('miniorange_ldap_server', $server_name)
        ->save();
    $this->server_name = $server_name;
  }

  /**
   * @return mixed
   */
  public function getServiceAccountUsername() {
    return $this->service_account_username;
  }

  /**
   * @param mixed $service_account_username
   */
  public function setServiceAccountUsername($service_account_username) {
    $this->config_factory
        ->set('miniorange_ldap_server_account_username', $service_account_username)
        ->save();
    $this->service_account_username = $service_account_username;
  }

  /**
   * @return array|mixed|string|null
   */
  public function getServiceAccountPassword() {
    return $this->service_account_password;
  }

  /**
   * @param mixed $service_account_password
   */
  public function setServiceAccountPassword($service_account_password) {
    $this->config_factory
        ->set('miniorange_ldap_server_account_password', $service_account_password)
        ->save();
    $this->service_account_password = $service_account_password;
  }

  /**
   * @return mixed
   */
  public function getSearchBase() {
    $search_bases = $this->config->get('miniorange_ldap_search_base');
    if ($search_bases == 'custom_base') {
      $search_bases = $this->config->get('miniorange_ldap_custom_sb_attribute');
    }
    return $search_bases;
  }

  /**
   * @param mixed $search_base
   */
  public function setSearchBase($search_base, $custom_base = NULL) {
    if (!is_null($custom_base)) {
      $this->config_factory
          ->set('miniorange_ldap_custom_sb_attribute', $custom_base)
          ->save();
      $this->custom_base = $custom_base;
    }
    $this->config_factory
        ->set('miniorange_ldap_search_base', $search_base)
        ->save();
    $this->search_base = $search_base;
  }

  /**
   * @return mixed
   */
  public function getSearchFilter() {
    return $this->config
        ->get('miniorange_ldap_username_attribute');
  }

  /**
   * @param mixed $search_filter
   */
  public function setSearchFilter($search_filter) {
    $this->config_factory
        ->set('miniorange_ldap_username_attribute', $search_filter)
        ->save();
  }

  /**
   *
   */
  public function anonymousBind() {
    // Anonymous binding with LDAP server. Used to ensure that the LDAP Server is reachable.
    $this->anon_bind = @ldap_bind($this->getLdapconn());

    $errors = Utilities::getLDAPDiagnosticError($this->ldapconn);
    LDAPLOGGERS::addLogger('DL5: Anonymous LDAP Bind: ', $errors,__LINE__,__FUNCTION__,__FILE__);
    return $this->anon_bind;
  }

  /**
   * @return array|mixed|string|null
   */
  public function getAnonBind() {
    return $this->anon_bind;
  }


  public function setAnonBind($anon_bind){
    $this->config_factory
        ->set('miniorange_ldap_enable_anony_bind', $anon_bind)
        ->save();
    $this->anon_bind = $anon_bind;
  }


  /**
   * Login function.
   */
  public function ldap_login($username, $password) {
    // 3rd parameter while test authentication done by admin
    if (empty($username)) {
      Utilities::add_message(t('Username can not be empty'), 'error');
      return;
    }
    if (empty($password)) {
      Utilities::add_message(t('The Password can not be empty'), 'error');
      return;
    }

    $username = ldap_escape($username,"",LDAP_ESCAPE_FILTER);
    $authStatus = NULL;
    $auth_response = new Mo_Ldap_Auth_Response();
    $auth_response->userDn = '';
    $ldapconn = $this->getConnection();

    LDAPLOGGERS::addLogger('L19: Anonymous LDAP Bind: ', $ldapconn,__LINE__,__FUNCTION__,__FILE__);
    if ($ldapconn) {
      LDAPLOGGERS::addLogger('L20: Entered LDAPFlow:ldapconn ','',__LINE__,__FUNCTION__,__FILE__);
      $search_filter = $this->config
          ->get('miniorange_ldap_username_attribute');
      $value_filter = '(&(objectClass=*)(' . $search_filter . '=?))';
      $search_bases = $this->config
          ->get('miniorange_ldap_search_base');
      if ($search_bases == 'custom_base') {
        $search_bases = $this->config
            ->get('miniorange_ldap_custom_sb_attribute');
      }
      LDAPLOGGERS::addLogger('DL8: search_base: ',$search_bases,__LINE__,__FUNCTION__,__FILE__);
      $ldap_bind_dn = $this->service_account_username;
      $ldap_bind_password = $this->service_account_password;

      $filter = str_replace('?', $username, $value_filter);
      LDAPLOGGERS::addLogger('DL9: Search Filter: ',$filter,__LINE__,__FUNCTION__,__FILE__);
      $user_search_result = NULL;
      $entry = NULL;
      $info = NULL;

      $email_attribute = is_null($this->config->get('miniorange_ldap_email_attribute')) ? 'mail' : $this->config->get('miniorange_ldap_email_attribute');

      $bind = @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);
      $err = ldap_error($ldapconn);

      $errors = Utilities::getLDAPDiagnosticError($ldapconn);

      LDAPLOGGERS::addLogger('L21: LDAPFlow ldap_error: ', $errors,__LINE__,__FUNCTION__,__FILE__);
      if (strtolower($err) != 'success') {
        LDAPLOGGERS::addLogger('L22: LDAPFlow strtolower(err) not success: ','',__LINE__,__FUNCTION__,__FILE__);
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'LDAP_BIND_FAILED';
        return $auth_response;
      }

      LDAPLOGGERS::addLogger('L24: LDAPFlow login flow: ','',__LINE__,__FUNCTION__,__FILE__);
      $s1 = @ldap_search($ldapconn, $search_bases, $filter);

      $userDn = '';
      if ($s1) {
        $user_search_result = ldap_search($ldapconn, $search_bases, $filter, ['*', '+']);
        LDAPLOGGERS::addLogger('L25: LDAPFlow ldap search: ', $user_search_result,__LINE__,__FUNCTION__,__FILE__);
        $info = ldap_first_entry($ldapconn, $user_search_result);
        $entry = ldap_get_entries($ldapconn, $user_search_result);
        LDAPLOGGERS::addLogger('L27: LDAPFlow ldap_first_entry: ', $info,__LINE__,__FUNCTION__,__FILE__);

        if ($info) {
          $userDn = ldap_get_dn($ldapconn, $info);
          LDAPLOGGERS::addLogger('L28: LDAPFlow userDn: ', $userDn,__LINE__,__FUNCTION__,__FILE__);
        }
        else {
          LDAPLOGGERS::addLogger('L29: LDAPFlow User Not Found ','',__LINE__,__FUNCTION__,__FILE__);
          $user_auth = \Drupal::service('user.auth')->authenticate($username,$password);

          if(!$user_auth) {
            $audits = new AuditAndLogs($username,time(),AuditAndLogs::USER_NOT_EXIST_IN_LDAP);
            $audits->addAudits();
          }
          else {
            $audits = new AuditAndLogs($username,time(),AuditAndLogs::USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS);
            $audits->addAudits();
          }

          $auth_response->status = FALSE;
          $auth_response->statusMessage = 'USER_NOT_EXIST_IN_LDAP';
          return $auth_response;
        }
      }
      else {

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);

        LDAPLOGGERS::addLogger('L26: '.$errors,'',__LINE__,__FUNCTION__,__FILE__);
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'Error while search: '.$errors;
        return $auth_response;
      }

      $authentication_response = self::authenticate($userDn, $password);
      LDAPLOGGERS::addLogger('L31: LDAPFlow authenticate_response status message Bind: ', $auth_response->statusMessage,__LINE__,__FUNCTION__,__FILE__);

      if ($authentication_response->statusMessage == 'SUCCESS') {
        $attributes_array = [];
        $profile_attributes = [];

        if (!empty($email_attribute)) {
          if (isset($entry[0][$email_attribute]) && is_array($entry[0][$email_attribute])) {
            $profile_attributes['mail'] = $entry[0][$email_attribute][0];
          }
          else {
            $profile_attributes['mail'] = isset($entry[0][$email_attribute]) ? $entry[0][$email_attribute] : '';
          }
        }

        if (isset($entry[0]['userprincipalname']) && is_array($entry[0]['userprincipalname'])) {
          $profile_attributes['userprincipalname'] = $entry[0]['userprincipalname'][0];
        }
        else {
          $profile_attributes['userprincipalname'] = isset($entry[0]['userprincipalname']) ? $entry[0]['userprincipalname'] : '';
        }

        $authentication_response->profileAttributesList = $profile_attributes;
        $authentication_response->attributeList = $attributes_array;
      }
      else{
        $user_auth = \Drupal::service('user.auth')->authenticate($username,$password);
        if(!$user_auth){
          $audits = new AuditAndLogs($username,time(),AuditAndLogs::WRONG_PASSWORD);
          $audits->addAudits();
        }else{
          $audits = new AuditAndLogs($username,time(),AuditAndLogs::USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS);
          $audits->addAudits();
        }

      }

      LDAPLOGGERS::addLogger('L32: LDAPFlow authenticate_response status message not SUCCESS ','',__LINE__,__FUNCTION__,__FILE__);
      return $authentication_response;
    }
    else {
      LDAPLOGGERS::addLogger('L33: LDAPFlow ldapconn if failed','',__LINE__,__FUNCTION__,__FILE__);
      $auth_response->status = FALSE;
      $auth_response->statusMessage = 'LDAP_CONNECTION_FAILED';
      return $auth_response;
    }

  }

  /**
   * Authenticate LDAP Credentials.
   */
  public function authenticate($userDn, $password) {


    $ldapconn = $this->getConnection();
    $auth_response = new Mo_Ldap_Auth_Response();
    if($ldapconn){

      $this->bind = @ldap_bind($this->ldapconn, $userDn, $password);
      // Verify binding.
      $search_filter = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_username_attribute');
      $value_filter = '(&(objectClass=*)(' . $search_filter . '=?))';
      $filter = str_replace('?', $userDn, $value_filter);
      LDAPLOGGERS::addLogger('L30: LDAPFlow authenticate() Bind: ', $this->bind,__LINE__,__FUNCTION__,__FILE__);
      if ($this->bind) {
        $auth_response->status = TRUE;
        $auth_response->statusMessage = 'SUCCESS';
        $auth_response->userDn = $userDn;
        return $auth_response;
      }
      else{
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'WRONG PASSWORD';
        $auth_response->userDn = $userDn;
      }

    }
    else{
      $auth_response->status = FALSE;
      $auth_response->statusMessage = 'LDAP NOT RESPONDING';
      $auth_response->userDn = $userDn;

    }

    return $auth_response;

  }

  /**
   * Returns all search Bases from AD.
   */
  public function getSearchBases() {

    $ldapconn = $this->getConnection();

    LDAPLOGGERS::addLogger('DL11: ldapconn: ',$ldapconn ? ldap_error($ldapconn) : "FALSE",__LINE__,__FUNCTION__,__FILE__);

    $search_base_list = [];

    if($ldapconn){

      $ldap_bind_dn = $this->getServiceAccountUsername();
      $ldap_bind_password = $this->getServiceAccountPassword();
      $bind = $ldapconn && @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);

      $errors = Utilities::getLDAPDiagnosticError($ldapconn);

      LDAPLOGGERS::addLogger('DL12: bind: ',$errors,__LINE__,__FUNCTION__,__FILE__);

      if ($bind) {
        $result = ldap_read($ldapconn, '', '(objectclass=*)', ['namingContexts']);

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);

        LDAPLOGGERS::addLogger('DL13: result: ',$errors,__LINE__,__FUNCTION__,__FILE__);
        $data = ldap_get_entries($ldapconn, $result);

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);

        LDAPLOGGERS::addLogger('DL14: data: ',$errors,__LINE__,__FUNCTION__,__FILE__);
        $count = $data[0]['namingcontexts']['count'];
        for ($i = 0; $i < $count; $i++) {
          if ($i == 0) {
            $base_dn = $data[0]['namingcontexts'][$i];
          }

          $search_base_list[] = $data[0]['namingcontexts'][$i];
        }

        $filter = "(|(objectclass=organizationalUnit)(&(objectClass=top)(cn=users)))";
        $search_attr = ["dn", "ou"];

        @$ldapsearch = ldap_search($ldapconn, $base_dn, $filter, $search_attr);

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);
        LDAPLOGGERS::addLogger('DL15: ldapsearch status: ',$errors,__LINE__,__FUNCTION__,__FILE__);
        if ($ldapsearch) {
          @$info = ldap_get_entries($ldapconn, $ldapsearch);
          $errors = Utilities::getLDAPDiagnosticError($ldapconn);
          LDAPLOGGERS::addLogger('DL16: info status: ',$errors,__LINE__,__FUNCTION__,__FILE__);
          if ($info) {
            for ($i = 0; $i < $info["count"]; $i++) {
              $textvalue = $info[$i]["dn"];
              $search_base_list[] = $info[$i]["dn"];
            }
          }

        }

      }
    }

    return $search_base_list;
  }

}
