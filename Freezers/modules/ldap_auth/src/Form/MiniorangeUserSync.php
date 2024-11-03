<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\ldap_auth\Utilities;
use Drupal\ldap_auth\MiniorangeLDAPConstants;

/**
 *
 */
class MiniorangeUserSync extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'user_sync';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');
    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];
    
    /*
     *  All Inclusive feature showcase
     */


    $form['markup_start'] = [
        '#type' => 'markup',
        '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container" >',
    ];
    $form['user_sync'] = [
        '#type' => 'fieldset',
    ];
    $form['user_sync']['markup_top'] = [
        '#markup' => $this->t('<h2>User & Password Sync  <a href='.$upgrade_plan_link.'><span style="font-size: medium">[All-Inclusive]</span></a></h2><hr>'),
    ];


    $form['user_sync']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['user_sync']['info']['sync_markup_note'] = [
        '#markup' => $this->t('<div>
Sync changes from <b>DRUPAL </b><span style="font-size:25px;">&#8594;</span><b> LDAP</b>&nbsp;&nbsp;
<a class="button button--primary button--small" href='.MiniorangeLDAPConstants::USER_SYNC_GUIDE.' target="_blank">🕮 Setup guide</a>
<ul style="font-size:small">
<li>With this feature, you will be able to make changes to your LDAP server directly from your Drupal site.</li>
<li>Supports password synchronization from Drupal to LDAP. </li></ul>
</div>'),
    ];

    $form['user_sync']['create_user_in_ldap'] = [
        '#type' => 'checkbox',
        '#disabled' => True,
        '#title' => $this->t('Create users in Active Directory/LDAP Server when a user is created in Drupal.'),
        '#default_value' => False
    ];

    $search_bases = $this->config->get('miniorange_ldap_search_base');
    if ($search_bases == 'custom_base') {
      $search_bases = $this->config->get('miniorange_ldap_custom_sb_attribute');
    }
    if(empty($search_bases)){
      $search_bases = 'dc=exapmle,dc=com';
    }


    $form['user_sync']['delete_user_in_ldap'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => $this->t('Delete users in Active Directory/LDAP Server when a user is deleted in Drupal.'),
    ];

    $form['user_sync']['miniorange_ldap_update_user_info'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => $this->t('Update user information in Active Directory/LDAP Server when user information is updated in Drupal.'),
    ];

    $form['user_sync']['miniorange_ldap_enable_password_sync'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => $this->t('Update user password in your LDAP/AD server when a user resets the password in Drupal.'),
        '#description' => $this->t('<b>Note:- </b>You need LDAPS for password related operations.'),
    ];

    $form['user_sync']['miniorange_ldap_save_import_users_settings'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Changes'),
        '#disabled' => TRUE,
    ];

    $form['import_users'] = [
        '#type' => 'fieldset'
    ];
    $form['import_users']['miniorange_ldap_enable_ldap_markup2'] = [
        '#markup' => $this->t("<div id='import_ldap_users'><h2>Import Users From LDAP to Drupal <a href= ".$upgrade_plan_link." ><span style='font-size: medium'>[All-Inclusive]</span></a></h2></div><hr>"),
    ];

    $form['import_users']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['import_users']['info']['import_users_markup_note'] = [
        '#markup' => $this->t('<div>
<ul style="font-size:small">
<li>Import users from a LDAP server to your Drupal site on a single click.</li>
</ul>
<a class="button button--primary button--small" href='.MiniorangeLDAPConstants::LDAP_IMPORT_VIDEO.' target="_blank">▶ Watch video</a>
<a  class="button button--primary button--small" href='.MiniorangeLDAPConstants::IMPORT_USERS.' target="_blank">🕮 Setup guide</a>
</div>'),
    ];


    $form['import_users']['miniorange_ldap_import_at_cron'] = [
        '#type' => 'select',
        '#title' => $this->t('Select the frequency of import'),
        '#options' => [
            'always' => $this->t('On every cron run'),
            'daily' => $this->t('Daily'),
            'weekly' => $this->t('Weekly'),
            'monthly' => $this->t('Monthly'),
            'never' => $this->t('Never'),
        ],
        '#disabled' => false,
        '#attributes' => ['style' => ['width:250px']],
    ];


    $form['import_users']['miniorange_ldap_load_account_with_email'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => $this->t('Search User By Email, if not found by Username'),
    ];

    $form['import_users']['miniorange_ldap_import_mapping'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => $this->t('Enable Attribute and Role mapping during User sync'),
    ];

    $form['import_users']['miniorange_ldap_import_auto_create_users'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Auto Create users in Drupal'),
        '#description' => $this->t("When importing users from LDAP to Drupal, choose how to create Drupal accounts."),
        '#disabled' => TRUE,
        '#default_value' => FALSE,
    ];

    $form['import_users']['auto_create_fieldset'] = [
        '#type' => 'fieldset',
        '#attributes' => ['style' => ['width:80%;']],
    ];

    $form['import_users']['auto_create_fieldset']['miniorange_ldap_set_of_radiobuttons1'] = [
        '#type' => 'radios',
        '#disabled' => TRUE,
        '#options' => [
            'block_ad' => $this->t('Block the new users which are not present in Drupal and present in AD'),
            'block_drupal' => $this->t('Block the users which are not present in AD and present in Drupal'),
            'block_none' => $this->t('Do not block any user'),
        ],
    ];

    $form['import_users']['miniorange_ldap_import_username_attribute'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username Attribute:'),
        '#disabled' => TRUE,
        '#description' => $this->t('Enter the LDAP attribute in which you get the Drupal username of your users.Example: sAMAccountName, mail, userPrincipalName'),
        '#attributes' => ['placeholder' => 'Enter Username Attribute'],
    ];

    $form['import_users']['miniorange_ldap_save_import_users_settings'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Changes'),
        '#disabled' => TRUE,
    ];

    $form['import_users']['miniorange_ldap_import_users'] = [
        '#type' => 'submit',
        '#value' => $this->t('Import All Users From LDAP'),
        '#disabled' => TRUE,
    ];

    $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
