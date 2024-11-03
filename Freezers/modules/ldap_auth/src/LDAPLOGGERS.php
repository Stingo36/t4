<?php

namespace Drupal\ldap_auth;

/**
 *
 */
class LDAPLOGGERS {

  /**
   *
   */
  public static function addLogger($log_info, $log_val = '',$line='', $function='', $file='') {
    $enable_logs = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_enable_logs');
    if ($enable_logs) {
      $location = 'In function ' . $function . '() (line ' . $line . ' of ' . $file . ')';
      $variable = '<pre>' . print_r($log_val, TRUE) . '</pre>';
      $message = $log_info . "\n" . $variable . "\n" . $location;

      \Drupal::logger('ldap_auth')->notice($message,);
    }
    return;
  }

}
