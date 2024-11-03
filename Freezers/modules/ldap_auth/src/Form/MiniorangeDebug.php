<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\ldap_auth\AuditAndLogs;
use Drupal\ldap_auth\LDAPLOGGERS;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniorangeDebug extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_debug';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_status = $this->config->get('miniorange_ldap_status');
    $debug_log_url = $this->getRouteUrl('dblog.overview').'?type%5B%5D=ldap_auth';

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];

    $form['markup_14'] = [
        '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout_logs">',
    ];

    $form['loggers'] = [
        '#type' => 'checkbox',
        '#name' => 'loggers',
        '#title' => t('Enable Logging'),
        '#description' => t('Enabling this checkbox will add additional debug logs under the <a target = "_blank" href='.$debug_log_url.'>Reports</a> section.&nbsp&nbsp'),
        '#default_value' => $this->config->get('miniorange_ldap_enable_logs'),
        '#prefix' => '<div class="container-inline">'
    ];

    $form['loggers_save_button'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value'=> t('Save'),
        '#submit' => ['::save_logs_option'],
    ];

    $form['username'] = [
        '#type' => 'textfield',
        '#default_value' => $this->getUsername(),
        '#attributes' => ['placeholder' => t('Search user here')],
        '#prefix' => '<div class="container-inline">'
    ];

    $form['error'] = [
        '#title' => 'Error',
        '#type' => 'select',
        '#options' => $this->getAllError(),
        '#default_value' =>$this->getErrorFilter(),
        '#attributes' => [
            'placeholder' => t('Search user'),
        ],
    ];

    $form['filter'] = [
        '#type' => 'submit',
        '#value' => t('Filter'),
        '#submit' => ['::setFilter']
    ];

    $form['reset'] = [
        '#type' => 'submit',
        '#value' => t('Reset'),
        '#submit' => ['::resetFilter'],
    ];

    $form['clear_logs'] = [
        '#type' => 'submit',
        '#value' => t('Clear logs'),
        '#limit_validation_errors' => [],
        '#submit' => ['::clearLogs'],
        '#suffix' => '</div>',
    ];


    $row = $this->getCurrentLogs($this->getUsername(),$this->getErrorFilter());

    $rows = [];
    foreach ($row as $index => $value) {
      $value = (array) $value;
      $auditAndLogs = new AuditAndLogs($value['user'],$value['date'],$value['error'],$value['mail']);
      $rows[$index + 1] = [
          'User' => $value['user'],
          'Date' => date("m/d/Y - h:i:s a", $value['date']),
          'Error' => $value['error'],
          'Operation' => "Login",
          'Possible solution' => Markup::create($auditAndLogs->getPossibleSolution()),
      ];
    }

    $form['mo_audits_and_logs']['table'] = [
        '#type' => 'table',
        '#header' => ['User', 'Date', 'Error', 'Operation','Possible solution'],
        '#rows' => $rows,
        '#responsive' => TRUE,
        '#sticky' => TRUE,
        '#empty' => t('No record found.'),
        '#size' => 3,
        '#prefix' => '<br/>',
    ];

    $form['mo_audits_and_logs']['pager'] = [
        '#type' => 'pager',
    ];

    $form['layout_1_clos_div'] = [
        '#markup' => '</div></div>',
    ];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }



  /**
   *
   */
  public function save_logs_option(array &$form, FormStateInterface $form_state) {
    $enable_loggers = $form_state->getValue('loggers');;
    $this->config_factory->set('miniorange_ldap_enable_logs', $enable_loggers)->save();
    Utilities::add_message(t('Settings Saved Successfully.'), 'status');
  }



  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function setFilter(array &$form, FormStateInterface $form_state){
    $filter_username = trim($form_state->getValue('username'));
    $filter_error = $form_state->getValue('error');
    if(!empty($filter_username)){
      $this->config_factory->set('filter_username',$filter_username)->save();
    }
    else{
      $this->config_factory->clear('filter_username')->save();
    }
    $this->config_factory->set('filter_error',$filter_error)->save();
  }

  public function resetFilter(){
    $this->config_factory->clear('filter_username')->save();
    $this->config_factory->set('filter_error','ALL')->save();
  }

  public function clearLogs(array &$form, FormStateInterface $form_state){
    try {
      $this->database->delete('mo_ldap_audits_and_logs')->execute();
    }
    catch (\Exception $exception){
      LDAPLOGGERS::addLogger($exception->getMessage());
    }
  }

  private function getUsername(){
    $username = $this->config->get('filter_username');
    return $username;
  }

  private function getErrorFilter(){
    $filter_error = $this->config->get('filter_error');
    if(is_null($filter_error))
      return 'ALL';
    return $filter_error;
  }

  private function getCurrentLogs($username,$filter_error){
    try {
      $query = $this->database->select('mo_ldap_audits_and_logs','audit_log')->fields('audit_log',['user','mail','date','error']);

      if (!is_null($username) && $filter_error!='ALL') {
        $query->condition('user', '%' . $username . '%', 'LIKE')->condition('error',$filter_error,'=');
      } else if (!is_null($username)) {
        $query->condition('user', '%' . $username . '%', 'LIKE');
      } else if ($filter_error!='ALL' ){
        $query->condition('error',$filter_error,'=');
      }

      $pager = $query->orderBy('uid','DESC')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit(8)
          ->execute()
          ->fetchAll();
      return json_decode(json_encode($pager), true);

    }
    catch (\Exception $exception){
      LDAPLOGGERS::addLogger($exception->getMessage());
      return null;
    }
  }

  private function getAllError() {
    $errors = [
        'ALL' => "ALL",
        AuditAndLogs::BLOCKED_USER => AuditAndLogs::BLOCKED_USER,
        AuditAndLogs::WRONG_PASSWORD => AuditAndLogs::WRONG_PASSWORD,
        AuditAndLogs::EMAIL_NOT_RECEIVED => AuditAndLogs::EMAIL_NOT_RECEIVED,
        AuditAndLogs::USER_NOT_EXIST_IN_LDAP => AuditAndLogs::USER_NOT_EXIST_IN_LDAP,
        AuditAndLogs::USER_NOT_EXIST_IN_DRUPAL => AuditAndLogs::USER_NOT_EXIST_IN_DRUPAL,
        AuditAndLogs::USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS => AuditAndLogs::USER_LOGGED_IN_USING_DRUPAL_CREDENTIALS,
    ];
    return $errors;
  }

}
