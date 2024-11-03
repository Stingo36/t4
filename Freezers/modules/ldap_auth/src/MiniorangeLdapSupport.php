<?php

namespace Drupal\ldap_auth;

use Drupal\ldap_auth\Controller\miniorange_ldapController;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @file
 * This class represents support information for customer.
 */
/**
 * @file
 * Contains miniOrange Support class.
 */
/**
 *
 */
class MiniorangeLdapSupport {
  public $email;
  public $phone;
  public $query;
  public $plan;
  public $mo_timezone;
  public $mo_date;
  public $mo_time;
  /**
   *
   */
  public function __construct($email, $phone, $query, $plan = '', $mo_timezone = '', $mo_date = '', $mo_time = '') {
    $this->email = $email;
    $this->phone = $phone;
    $this->query = $query;
    $this->plan = $plan;
    $this->mo_timezone = $mo_timezone;
    $this->mo_date = $mo_date;
    $this->mo_time = $mo_time;
  }

  /**
   *
   */
  public function sendSupportQuery() {

    $base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();

    $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('ldap_auth');
    $modules_version = $modules_info['version'];
    $customerKey = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_customer_id');
    $trial_clicked_on = \Drupal::config('ldap_auth.settings')->get('trial_clicked_on');

    if($trial_clicked_on){
      $trial_module_version = "<br><br>Clicked on: $trial_clicked_on";
    }
    else{
      $trial_module_version = '';
    }

    if (!Utilities::isCurlInstalled()) {
      return json_encode(array(
          "statusCode" => 'ERROR',
          "statusMessage" => 'cURL is not enabled on your site. Please enable the cURL module.',
      ));
    }

    $apikey = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_customer_api_key');

    if ($customerKey == '') {
      $customerKey = "16555";
      $apikey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
    }

    [$result,$error_no] = $this->get_ldap_timestamp();

    if($error_no == 200) {
      $currentTimeInMillis = $result;
    }
    else{
      return [$result,$error_no];
    }

    $stringToHash = $customerKey . $currentTimeInMillis . $apikey;
    $hashValue = hash("sha512", $stringToHash);

    if ($this->plan == 'demo' || $this->plan == 'trial' || $this->plan == 'schedule_call' || $this->plan == 'request_quote') {

      $url = MiniorangeLDAPConstants::BASE_URL . '/moas/api/notify/send';

      $request_for = $this->plan == 'demo' ? 'Demo' : (($this->plan == 'trial') ? 'Trial' : (($this->plan == 'schedule_call') ? 'Setup Meeting/Call' : 'Quote'));

      $subject = $request_for . ' request for Drupal-' . \DRUPAL::VERSION . ' Active Directory / LDAP Integration - NTLM & Kerberos Login Module | ' . $modules_version;
      $this->query = $request_for . ' required for - ' . $this->query;

      if ($this->plan == 'schedule_call') {
        $content = '<div >Hello, <br><br>Company: <a href="' . $base_url . '" target="_blank" >' . $base_url . '</a><br><br>Phone Number:' . $this->phone . '<br><br>Email: <a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br> Timezone: <b>' . $this->mo_timezone . '</b><br><br> Date: <b>' . $this->mo_date . '</b>&nbsp;&nbsp; Time: <b>' . $this->mo_time . '</b><br><br>Query: [DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' ] ' . $this->query . '</div>';
      }
      elseif ($this->plan == 'request_quote') {
        $content = '<div >Hello, <br><br>Company: <a href="' . $base_url . '" target="_blank" >' . $base_url . '</a><br>Email: <a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br>' . '</b><br>Query: [DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' ] ' . $this->query . '</div>';
      }
      else {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? "";
        $content = '<div >Hello, <br><br>Company: <a href="' . $base_url . '" target="_blank" >' . $base_url . '</a><br><br>Phone Number: ' . $this->phone . '<br><br>Email: <a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a>' . $trial_module_version . '<br><br>Query: [DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' | ' . $server . ' ] ' . $this->query . '</div>';
      }

      $fields = [
          'customerKey' => $customerKey,
          'sendEmail' => TRUE,
          'email' => [
              'customerKey' => $customerKey,
              'fromEmail' => $this->email,
              'fromName' => 'miniOrange',
              'toEmail' => MiniorangeLDAPConstants::SUPPORT_EMAIL,
              'toName'  => MiniorangeLDAPConstants::SUPPORT_NAME,
              'subject' => $subject,
              'content' => $content,
          ],
      ];

      $header = [
          'Content-Type' => 'application/json',
          'Customer-Key' => $customerKey,
          'Timestamp' => $currentTimeInMillis,
          'Authorization' => $hashValue,
      ];

    }
    else {

      $this->query = "<div style='border:1px solid #444;padding:10px;width:80%;'><code>$this->query</code></div>";
      $this->query = '[Drupal ' . \DRUPAL::VERSION . ' Active Directory / LDAP Integration - NTLM & Kerberos Login Free Module | ' . $modules_version . ' | '.phpversion().' ] <br>' . $this->query;

      $fields = [
          'company' => $base_url,
          'email' => $this->email,
          'phone' => $this->phone,
          'ccEmail' => MiniorangeLDAPConstants::SUPPORT_EMAIL,
          'query' => $this->query,
      ];

      $url = MiniorangeLDAPConstants::BASE_URL . '/moas/rest/customer/contact-us';

      $header = [
          'Content-Type' => 'application/json',
          'charset' => 'UTF-8',
          'Authorization' => 'Basic',
      ];
    }

    $field_string = json_encode($fields);

    try {
      $response = \Drupal::httpClient()
          ->request('POST', $url, [
              'body' => $field_string,
              'allow_redirects' => TRUE,
              'http_errors' => FALSE,
              'decode_content'  => TRUE,
              'verify' => FALSE,
              'headers' => $header,
          ]);

      \Drupal::logger('ldap_auth')->notice('Error at %method of %file: %error (%error_code) - URL_try => %url',
          [
              '%method' => __METHOD__,
              '%file' => __FILE__,
              '%error_code' => $response->getStatusCode(),
              '%error' => $response->getBody()->getContents(),
              '%url' => $url,
          ]);

      return [$response->getBody()->getContents(),$response->getStatusCode()];
    }
    catch (\Exception $exception) {

      \Drupal::logger('ldap_auth')->notice('Error at %method of %file: %error (%error_code) - URL_extection => %url',
          [
              '%method' => __METHOD__,
              '%file' => __FILE__,
              '%error_code' => $exception->getCode() ,
              '%error' => $exception->getMessage(),
              '%url' => $url,
          ]);

      return [$exception->getMessage(),$exception->getCode()];
    }

  }

  /**
   * This function is used to get the timestamp value.
   */
  public function get_ldap_timestamp() {
    $url = MiniorangeLDAPConstants::BASE_URL.'/moas/rest/mobile/get-timestamp';

    $http_client = \Drupal::httpClient();

    $options = [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'allow_redirects' => TRUE,
        'http_errors' => FALSE,
        'decode_content'  => TRUE,
        'verify' => FALSE,
        'body' => json_encode([]),
    ];

    $content = '';
    $status_code = '';
    try {
       $response = $http_client->post($url,$options);
      $content = $response->getBody()->getContents();
      $status_code  = $response->getStatusCode();
    }
    catch (\Exception $exception){
      \Drupal::logger('ldap_auth')->error('HTTP request failed with error: @error (@error_code)', [
          '@error' => $exception->getMessage(),
          '@error_code' => $exception->getCode()
      ]);
      $status_code = $exception->getCode();
    }

    if (empty($content)) {
      $currentTimeInMillis = round(microtime(TRUE) * 1000);
      $currentTimeInMillis = number_format($currentTimeInMillis, 0, '', '');
    }

    return [empty($content) ? $currentTimeInMillis : $content,$status_code];

  }

}