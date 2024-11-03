<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\Utilities;
use Drupal\user\Entity\User;

class MiniorangeAdvanceSetting extends LDAPFormBase {

  public function getFormId()
  {
    return "advance_settings";
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];

    $form['markup_start'] = [
        '#type' => 'markup',
        '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container" >',
    ];

    //Redirect user after login
    $form['redirect'] = [
        "#type" => "details",
        '#title' => $this->t('Custom Login/Logout Redirect URL'),
    ];

    $form['redirect']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['redirect']['info']['sync_markup_note'] = [
        '#markup' => $this->t('<div><ul style="line-height:150%">
<li><b>Configure the user redirection after user login and logout upon LDAP Authentication.</b></li>
<li>This feature is available in the <a href='.$upgrade_plan_link.' target="_blank">All-Inclusive</a> version of the module.</li></ul>
</div>'),
    ];

    $form['redirect']['miniorange_ldap_login_redirect'] = [
        '#type' => 'url',
        '#title' => t('Redirect URL After Login:'),
        '#disabled' => true,
        '#description' => $this->t('<strong>Note: </strong>Enter the entire URL (<em> including https:// </em>) where you want to redirect user after successful authentication.'),
        '#attributes' => ['placeholder' => 'Eg. https://www.example.com'],
    ];

    $form['redirect']['miniorange_ldap_logout_redirect'] = [
        '#type' => 'url',
        '#title' => t('Redirect URL After Logout:'),
        '#disabled' => true,
        '#description' => t('<strong>Note: </strong>Enter the entire URL (<em> including https:// </em>) where you want to redirect user after logout.'),
        '#attributes' => ['placeholder' => 'Eg. https://www.example.com'],
    ];


    // UPDATE LOGIN FORM
    $form['user_login_interface'] = [
          '#type' => 'details',
          '#title'=> $this->t("User Login Interface"),
          '#open' => true,
      ];

      $form['user_login_interface']['username_description'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Username Description'),
          '#maxlength' => 2048,
          '#description' => $this->t('The above description will override the current Username field description on user login form.'),
          '#default_value' => $this->config->get('username_description')
      ];

      $form['user_login_interface']['password_description'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Password Description'),
          '#maxlength' => 2048,
          '#description' => $this->t('The above description will override the current Password field description on user login form.'),
          '#default_value' => $this->config->get('password_description')
      ];

   // DISABLE USER PROFILE FIELDS

    $form['miniorange_ldap_auto_disable_fieldset'] = [
        '#type' => 'details',
        '#title' => $this->t('User Profile Fields'),
    ];

    $form['miniorange_ldap_auto_disable_fieldset']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['miniorange_ldap_auto_disable_fieldset']['info']['sync_markup_note'] = [
        '#markup' => $this->t('<div><ul style="line-height:150%">
<li>Disable or hide user profile fields in the user form. <i>eg registration form, custom_user form</i></li>
<li>Prevent user password updates by disabling or hiding the password field in the user profile edit form or any other custom form.</li>
<li>This feature is available in the <a href='.$upgrade_plan_link.' target="_blank">All-Inclusive</a> version of the module.</li></ul>
</div>'),
    ];

    $form['miniorange_ldap_auto_disable_fieldset']['miniorange_ldap_disable_profile_field'] = [
        '#type' => 'checkbox',
        '#disabled' => true,
        '#title' => t('<b>Enable this checkbox to disable users profile attribute fields.</b>'),
    ];

    $form['miniorange_ldap_auto_disable_fieldset']['miniorange_ldap_disable_user_profile_attributes'] = [
        '#type' => 'textarea',
        '#title' => t('Enter semicolon(;) separated profile attribute machine names that you disable.'),
        '#disabled' => true,
        '#description' => '<b>Note: </b>The users would not be able to changes these attributes.',
        '#attributes' => ['placeholder' => 'Enter semicolon(;) separated profile attribute machine names that you disable.'],
    ];

    $form['miniorange_ldap_auto_disable_fieldset']['miniorange_ldap_disable_pass_confirm_pass'] = [
        '#title' => t('Disable/Hide "<u>Current password</u>", "<u>Password</u>" and "<u>Current Password</u>" fields of user profile page:'),
        '#type' => 'radios',
        '#options' => [
            'editable' => t('Keep Editable'),
            'disable' => t('Disable'),
            'hide' => t('Disable and Hide'),
        ],
        '#attributes' => ['class' => ['container-inline']],
        '#disabled' => true,
    ];


    $form['ldap_group_restriction'] = [
        '#type' => 'details',
        '#title'=> $this->t("Restrict LDAP Groups"),
        '#disabled' => true,
    ];

    $form['ldap_group_restriction']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['ldap_group_restriction']['info']['sync_markup_note'] = [
        '#markup' => $this->t('<div><ul style="line-height:150%">
<li>Configure LDAP Group Based Login Restriction in this section.</li>
<li>Allow or block user logins based on listed LDAP groups.</li>
<li>This feature is available in the <a href='.$upgrade_plan_link.' target="_blank">All-Inclusive</a> version of the module.</li></ul>
<a  class="button button--primary button--small" href='.MiniorangeLDAPConstants::GROUP_LOGIN_RESTRICTION_GUIDE.' target="_blank">🕮 Setup guide</a>
</div>'),
    ];

    $form['ldap_group_restriction']['group_restriction'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Allowing and Blocking LDAP Groups'),
    ];

    $form['ldap_group_restriction']['whitelist_blacklist_radio_buttons'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select restriction option'),
        '#default_value' => $this->config->get('selected_whitelist_blacklist_option') ?? 'whitelist',
        '#options' => [
            'whitelist' => $this->t('Allow'),
            'blacklist' => $this->t('Block'),
        ],
        '#disabled' => false,
    ];

    $form['ldap_group_restriction']['whitelist_textarea'] = [
        '#type' => 'textarea',
        '#title' => $this->t('<span style="color: green">Allowed LDAP Groups and OU</span>'),
        '#description' => $this->t('Only users belonging to the above entered LDAP groups or OU are allowed to log in to your Drupal site. Enter one per line such as <pre>cn=drupal_users,dc=example,dc=com<br>cn=admin_users,dc=example,dc=com</pre>'),
        '#states' => [
            'visible' => [
                ':input[name=whitelist_blacklist_radio_buttons]' => ['value' => 'whitelist'],
            ],
            'disabled' => [
                'input[name=group_restriction]'=> ['checked' => FALSE]
            ],
        ],
        '#rows' => 3,
    ];

    $form['ldap_group_restriction']['blacklist_textarea'] = [
        '#type' => 'textarea',
        '#title' => $this->t('<span style="color: red">Blocked LDAP Groups and OU</span>'),
        '#description' => $this->t('Above entered LDAP groups or OU users are <strong>NOT</strong> allowed to log in to your Drupal site. Enter one per line such as <pre>cn=restricted_users,dc=example,dc=com<br>cn=blocked_user,dc=example,dc=com</pre>'),
        '#states' => [
            'visible' => [
                ':input[name=whitelist_blacklist_radio_buttons]' => ['value' => 'blacklist']
            ],
            'disabled' => [
                'input[name=group_restriction]'=> ['checked' => FALSE]
            ]
        ],
        '#rows' => 3,
    ];

    $form['miniorange_save_advance_settings'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save Settings'),
    ];

    $form['markup_close'] = [
        '#type' => 'markup',
        '#markup' => '</div></div>'
    ];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

      $form_values = $form_state->getValues();

      $username_description = $form_values['username_description'] ?? "";
      $password_description = $form_values['password_description'] ?? "";

      $this->config_factory->set('username_description',$username_description)
                           ->set('password_description',$password_description)
                           ->save();

      $this->messenger->addStatus('Configuration saved successfully');

  }

}