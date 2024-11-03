<?php

namespace Drupal\ldap_auth\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_auth\LDAPFlow;
use Drupal\ldap_auth\Mo_Ldap_Auth_Response;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class miniorange_ldapController extends ControllerBase {

  private $base_url;
  private ImmutableConfig $config;
  private Config $config_factory;
  private $moduleList;

  /**
   *
   */
  public function __construct() {
    $this->base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();
    $this->config = \Drupal::config('ldap_auth.settings');
    $this->config_factory = \Drupal::configFactory()->getEditable('ldap_auth.settings');
    $this->moduleList = \Drupal::service('extension.list.module');
  }


  /**
   * @param $username
   * @return \Drupal\ldap_auth\Mo_Ldap_Auth_Response
   */
  public function search_user_attributes($username) {

    $ldap_connect = new LDAPFlow();
    $ldapconn = $ldap_connect->getConnection();
    $auth_response = new Mo_Ldap_Auth_Response();
    if ($ldapconn) {
      $ldap_bind_dn = $ldap_connect->getServiceAccountUsername();
      $ldap_bind_password = $ldap_connect->getServiceAccountPassword();
      $search_base = $ldap_connect->getSearchBase();
      $search_filter_1 = $ldap_connect->getSearchFilter();
      $search_filter = '(&(objectClass=*)(' . $search_filter_1 . '=?))';
      $filter = str_replace('?', $username, $search_filter);

      $user_search_result = NULL;
      $entry              = NULL;
      $info               = NULL;
      $bind               = @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);
      $err                = ldap_error($ldapconn);

      if (strtolower($err) != 'success') {

        $errors = Utilities::getLDAPDiagnosticError($ldapconn);

        $auth_response->status = FALSE;
        $auth_response->statusMessage = "LDAP_NOT_RESPONDING, $err";
        $auth_response->userDn = '';
        return $auth_response;
      }

      if (@ldap_search($ldapconn, $search_base, $filter)) {

        $user_search_result = ldap_search($ldapconn, $search_base, $filter, ['*', '+']);

        $info = ldap_first_entry($ldapconn, $user_search_result);
        $entry = ldap_get_entries($ldapconn, $user_search_result);
        $user_attributes = [];
        $i = 0;

        if (!$info) {
          $err = ldap_error($ldapconn);
          $auth_response->status = FALSE;
          $auth_response->statusMessage = 'User with <small><code>' . $search_filter_1 .'="'. $username . '"</code></small> does not exist in selected Search Base.<br>
                <ul>
                 <li>Selected Search Base : <small><code><i>' . $search_base . '</i></code></small></li>
                 <li>Selected Username Attribute: <small><code><i>' . $search_filter_1 . '</i></code></small></li>
                </ul>';

          $auth_response->userDn = NULL;
          return $auth_response;
        }

        foreach ($entry[0] as $key => $value) {
          if (!is_int($key) && $key != 'count') {
              if(is_array($value) && sizeof($value)>2){
                  unset($value['count']);
                  $user_attributes[$key] = json_encode($value);
              }
              else{
                  $user_attributes[$key] = $value[0];
              }
          }
        }

        $user_attributes['dn'] = ldap_get_dn($ldapconn, $info);

        if (isset($entry[0]['memberof']) && is_array($entry[0]['memberof'])) {
          $user_attributes['memberof'] = [];
          foreach ($entry[0]['memberof'] as $member) {
            if ($i > 0) {
              array_push($user_attributes['memberof'], $member);
            }
            $i++;
          }
        }

        $auth_response->status = TRUE;
        $auth_response->statusMessage = "Attributes fetched Successfully.";
        $auth_response->userDn = ldap_get_dn($ldapconn, $info);
        $auth_response->attributeList = $user_attributes;
        return $auth_response;
      }
      else {
        $errors = Utilities::getLDAPDiagnosticError($ldapconn);
        $auth_response->status = FALSE;
        $auth_response->statusMessage = "Error fetching user information. <br><br>$errors";
        $auth_response->userDn = NULL;
        return $auth_response;
      }
    }
    else {
      // Error message.
      $auth_response->status = FALSE;
      $auth_response->statusMessage = 'ERROR : Cannot connect to your LDAP Server';
      $auth_response->userDn = NULL;
      return $auth_response;
    }

  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function uninst_mod() {
    $this->config_factory->clear('miniorange_ldap_feedback_status')->save();
    \Drupal::service('module_installer')->uninstall(['ldap_auth']);
    $uninstall_redirect = $this->base_url . '/admin/modules';
    $response = new RedirectResponse($uninstall_redirect);
    $response->send();
    return new Response();
  }


  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
   */
  public function test_configuration() {

    $base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();

    if ( isset($_POST['user']) && $_POST['user'] == '') {
      echo '<div style="color: #9f1e2b;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #e8bcbc; font-size:16pt;"><b>Username or Password cannot be empty.</b></div>';
      exit;
    }

    echo "<style>body {  font-family: Trebuchet MS, sans-serif;}</style>";

    $username = trim($_POST['user']) ?? '';

    $this->config_factory->set('mo_last_authenticated_user', $username)->save();

    $attributes = self::search_user_attributes($username);

    $module_path = $this->moduleList->getPath("ldap_auth");


    if (!$attributes->status) {
      echo '<div style="color: #961f1f; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;">TEST AUTHENTICATION FAILED</div><div style="display:block;text-align:center;margin-bottom:0%;"></div>
            <div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;"src="' . $base_url . '/' . $module_path . '/assets/img/wrong.png"></div>';

      echo '<div style="min-width:75%;color: #101010;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #e8bcbc; width: fit-content; font-size:16pt;">' . $attributes->statusMessage . '</div>';
      echo '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;" type="button" value="Close" onClick="self.close();"/></div>';

      exit;
      return new Response();
    }

    unset($attributes->attributeList['objectsid']);
    unset($attributes->attributeList['objectguid']);
    $name = $attributes->attributeList['cn'];


    $attributes_list = [];
    Utilities::show_attr($attributes->attributeList, $attributes_list);

    foreach ($attributes_list as $value) {
      $attr_list[$value['att_name']] = $value['att_value'];
    }

    $ldap_user_attribute_list = $attr_list ?? [];
    $remove_keys = ['jpegphoto','thumbnailphoto','document','userpkcs12','usercertificate'];
    foreach ($remove_keys as $remove_key){
      unset($ldap_user_attribute_list[$remove_key]);
    }
    $this->config_factory->set('ldap_user_attributes_and_values',json_encode($ldap_user_attribute_list))->save();

    $attr_list_option = [];
    foreach ($attributes->attributeList as $key => $value){
      $attr_list_option[] = $key;
    }
    $this->config_factory->set('ldap_attribute_list',json_encode($attr_list_option))->save();

    if (isset($_POST['pass']) && $_POST['pass'] != 'undefined') {

      $user_pass = $_POST['pass'];
      $ldap_connect = new LDAPFlow();
      $ldapconn = $ldap_connect->getConnection();

      $auth_response = $ldap_connect->ldap_login($username, $user_pass);

    }

    echo '<style>select {
  background-color: transparent;
  border: none;
  padding: 0 1em 0 0;
  margin: 0;
  width: 100%;
  font-family: inherit;
  font-size: inherit;
  cursor: inherit;
  line-height: inherit;
  z-index: 1;
}
                .select {
  display: grid;
  grid-template-areas: "select";
  align-items: center;
  position: relative;
  min-width: 15ch;
  max-width: 30ch;
  border: 1px solid;
  border-radius: 0.25em;
  padding: 0.25em 0.5em;
  font-size: 1.25rem;
  cursor: pointer;
  line-height: 1.1;
  background-color: #fff;
  background-image: linear-gradient(to top, #f9f9f9, #fff 33%);
}
                .message_div{color: #3c763d;background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #AEDB9A; font-size:16pt;}
                .title_div {color: #3c763d; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;}
              </style>';


    if (isset($auth_response) && !$auth_response->status) {

      echo '<div style="color: #961f1f; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;">TEST AUTHENTICATION FAILED</div><div style="display:block;text-align:center;margin-bottom:0%;"></div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;"src="' . $base_url . '/' . $module_path . '/assets/img/wrong.png"></div>';
      echo '<div style="color: #9f1e2b;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left;width:fit-content; border:1px solid #e8bcbc; font-size:16pt;"><b>' . $auth_response->statusMessage . '</b>: User found in the LDAP server but the password does not match. Please try again with valid password.<br>
 Below are the LDAP user attributes found for reference. </div>';

      echo '<div style="margin:3%;display:block;text-align:center;"><input
                            style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"
                            type="button" value="Close" onClick="self.close();"/></div>';

    }
    else{
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div class="title_div">TEST SUCCESSFUL</div><div style="display:block;text-align:center;margin-bottom:0;"></div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;" src="' . $base_url . '/' . $module_path . '/assets/img/green_check.png"></div>';
      echo '<div class="message_div">Congratulations, your test authentication is successful.</div>';
    }

    $this->display_attributes($name, $attr_list);

    exit;
    return new Response();
  }

  /**
   *
   */
  public function display_attributes($name, $attr_list) {
    echo '<p style="font-size:13pt;margin-left:1%;"> Hello <b>' . $name . ',</b></p>
            <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:13pt;background-color:#ffffff;">';
    foreach ($attr_list as $key => $value) {

      if(preg_match('/.*jpegphoto.*/', $key) || preg_match('/.*thumbnailphoto.*/', $key)){
        $value = "<img src='data:image/jpeg;base64,".base64_encode($value)."' alt='User photo' / width='100' height='120'>";
      }

      echo ' <tr style="text-align:left;">
                       <td style="font-weight:bold;border:2px solid #949090;padding:2%;"><b>' . $key . '</b></td>
                       <td style="padding:2%;border:2px solid #949090; word-wrap:break-word;">' . $value . '</td>
                </tr>';
    }
    echo '</table><br><br>';
  }

}
