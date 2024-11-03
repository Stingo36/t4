<?php

namespace Drupal\ldap_auth;

use Drupal\Core\Url;

class AuditAndLogs {

  private $username;
  private $time;
  private $error_occured;
  private $user_email = '';

  const EMAIL_NOT_RECEIVED = "EMAIL_NOT_RECEIVED";
  const USER_NOT_EXIST_IN_DRUPAL = "USER_NOT_EXIST_IN_DRUPAL";
  const USER_NOT_EXIST_IN_LDAP = "USER_NOT_EXIST_IN_LDAP";
  const WRONG_PASSWORD = "WRONG_PASSWORD";
  const BLOCKED_USER = "BLOCKED_USER";
  const LDAP_NOT_RESPONDING = "LDAP_NOT_RESPONDING";
  const USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS = "USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS";

  public function __construct($username,$time,$error_occured,$user_email='') {
    $this->username = $username;
    $this->time = $time;
    $this->error_occured = $error_occured;
    $this->user_email = $user_email;
  }

  public function getPossibleSolution(){

    $base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();
    $email_attribute = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_email_attribute');
    $ldap_connect = new LDAPFlow();
    $search_filter = $ldap_connect->getSearchFilter();
    $search_bases = $ldap_connect->getSearchBase();

    $link = Url::fromRoute('ldap_auth.attribute_mapping')->toString(TRUE)->getGeneratedUrl();

    switch ($this->error_occured) {

      case "EMAIL_NOT_RECEIVED":
        return "Email address not received in the <b>" . $email_attribute . " </b> attribute of LDAP for the user <i>" . $this->username . "</i>. Please check your <a  href=".\Drupal::request()->getSchemeAndHttpHost().$link." target='_blank'>email attribute mapping</a> .";
        break;
      case "USER_NOT_EXIST_IN_DRUPAL" :
        return "User found in LDAP server but no account with <u><i>username = ". $this->username ."</i></u> or <u><i>email = ".$this->user_email ." </i></u> exist in Drupal database. Please enable the <b>auto create user feature</b> under the <i>LDAP Configuration</i> tab. Or <a href='".$base_url."/admin/people/create' target='_blank'>click here</a> to add the user in Drupal manually.";
        break;
      case "USER_NOT_EXIST_IN_LDAP":
        return "User with <var>" . $search_filter . " = " . $this->username . "</var> not found under <i>" . $search_bases . "</i>. Please verify if the user exist in your selected search base.  ";
        break;
      case "WRONG_PASSWORD":
        return "User found in LDAP server but password does not match.";
        break;
      case "BLOCKED_USER":
        return "Enable to log in user (" . $this->username . ") as it is blocked in Drupal.";
        break;
      case 'LDAP_NOT_RESPONDING':
        return "Please check the bindDn username and password.";
        break;
      case 'USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS':
        return "User logged in using Drupal credentials.";
        break;
      default:
        echo "Error occurred";

    }
  }

  public function addAudits(){

    $values = [
     $this->username,
     $this->time,
     $this->error_occured,
     $this->user_email,
    ];

    try {
      $connection = \Drupal::database();
      $connection->insert('mo_ldap_audits_and_logs')
        ->fields(['user','date','error','mail'], $values)
        ->execute();
    } catch (Exception $exception) {
      LDAPLOGGERS::addLogger($exception->getMessage());
      return null;
    }
  }

  public function getUserEmail(){
    return $this->user_email;
  }

}
