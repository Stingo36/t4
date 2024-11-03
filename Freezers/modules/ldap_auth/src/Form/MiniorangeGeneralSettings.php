<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\ldap_auth\MiniorangeLdapSupport;
use Drupal\ldap_auth\Utilities;
use Drupal\ldap_auth\MiniorangeLDAPConstants;

/**
 *
 */
class MiniorangeGeneralSettings extends LDAPFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_general_settings';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];

    $form['markup_14'] = [
        '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
    ];

    $form['ntlm_gateway'] = [
        '#type' => 'horizontal_tabs',
        '#default_tab' => 'edit-debug',
    ];

    $upgrade_tab_link = $this->getRouteUrl('ldap_auth.licensing');
    $premium_all_inclusive_tag = "<a href=$upgrade_tab_link target='_self'>[Premium, All-Inclusive]</a>";

    $form['Ntlm'] = [
        '#type' => 'details',
        '#title' => $this
            ->t('Windows Auto-Login/SSO using NTLM/Kerberos  ' .$premium_all_inclusive_tag),
        '#group' => 'ntlm_gateway',
        '#open' => true,
    ];


    $form['Ntlm']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['Ntlm']['info']['ntlm_markup_note'] = [
        '#markup' => $this->t('<div>
<ul style="font-size:small">
<li>LDAP Single Sign-On (SSO) using Active Directory (AD) credentials</li>
<li>Automatic login to Drupal site using AD credentials.</li>
<li>Remote login using desktop credentials into Drupal site.</li>
<li>Check out the <a href="'.MiniorangeLDAPConstants::NTLM_KERBEROS_CASE_STUDY.'" target="_blank">Integrated Windows Authentication - IWA</a> case study on drupal.org</li>
<a  class="button button--small" href='.MiniorangeLDAPConstants::GUIDE_ENABLE_KERBEROS_LOGIN.' target="_blank">🕮 Setup guide</a>
<a  class="button button--small" href='.MiniorangeLDAPConstants::GUIDE_KERBEROS.' target="_blank">🕮 Setup NTLM/Kerberos Authentication</a>
</ul>
</div>'),
    ];

    $form['Ntlm']['miniorange_ldap_enable_ntlm'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#description' => t('<b style="color: red">Note:</b> Enabling NTLM/Kerberos login will automatically log in the currently logged-in Windows user. You need to setup NTLM/Kerberos Authentication for your Drupal site.'),
        '#title' => t('Enable NTLM/ Kerberos Login'),
    ];

    $form['Ntlm']['miniorange_ldap_user_server_variable'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Server variable holding the user'),
        '#disabled' => true,
        '#description' => $this->t('Enter the server variable name containing the user. This is generally REMOTE_USER or REDIRECT_REMOTE_USER.'),
        '#default_value' => 'REMOTE_USER',
    ];

    $form['Ntlm']['strip_server_variable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Strip SERVER_VARIABLE domain name'),
        '#description' => $this->t('Use this if you get users via SSO (user@realm) but also need manual authentication without a realm, and want to prevent duplicate accounts.'),
        '#disabled' => true,
    ];


    $form['Ntlm']['miniorange_ldap_nltm_desc'] = [
        '#markup' => t('<div><br>
              <h1>What is Microsoft NTLM?</h1><hr>
              <p>NTLM is the authentication protocol used on networks that include systems running the Windows operating system and on stand-alone systems.</p>
              <p>NTLM credentials are based on data obtained during the interactive logon process and consist of a domain name, a user name, and a one-way hash of the users password. NTLM uses an encrypted challenge/response protocol to authenticate a user without sending the user password over the wire. Instead, the system requesting authentication must perform a calculation that proves it has access to the secured NTLM credentials.<br></p></div>'),
    ];

    $form['Ntlm']['miniorange_ldap_kerbeors_desc'] = [
        '#markup' => t('<br>
            <h1>What is Kerberos?</h1><hr>
            <p>Kerberos is a client-server authentication protocol that enables mutual authentication –  both the user and the server verify each other’s identity – over non-secure network connections.  The protocol is resistant to eavesdropping and replay attacks, and requires a trusted third party.</p>
            <p>The Kerberos protocol uses a symmetric key derived from the user password to securely exchange a session key for the client and server to use. A server component is known as a Ticket Granting Service (TGS) then issues a security token (AKA Ticket-Granting-Ticket TGT) that can be later used by the client to gain access to different services provided by a Service Server.<br></p><br>'),
    ];

    $form['gateway'] = [
        '#type' => 'details',
        '#title' => $this
            ->t('Gateway Login <a class="guide_link" href ="https://plugins.miniorange.com/guide-to-configure-ldap-ad-integration-module-for-drupal" target="_blank">&#128366; Setup Guide</a>'),
        '#group' => 'ntlm_gateway',
        '#open' => TRUE,
    ];

    $form['gateway']['miniorange_ldap_enable_gateway'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#description' => $this->t('<b style="color: red">Note:</b> Enabling this checkbox allows your users to login to your Drupal site using credentials stored in a privately/publicly hosted LDAP/Active Directory server. Upgrade to the <a href='.$upgrade_tab_link.'><b>All-Inclusive</b></a> version of the module to use this feature. '),
        '#title' => t('Enable Gateway Login'),
    ];

    $form['gateway']['miniorange_ldap_gateway_desc'] = [
        '#markup' => $this->t('<br><h1>What is Gateway Login? </h1> <hr><p>
      LDAP Gateway allows you to log in to your Drupal site using credentials stored in a privately/publicly hosted Active Directory, OpenLDAP and other LDAP servers. If the LDAP Server is not publicly accessible from your site,
      this module can be used in conjunction with the miniOrange LDAP Gateway, which is deployed at the DMZ server in the intranet.'),
    ];

    $module_path = $this->moduleList->getPath("ldap_auth");

    $form['gateway']['gateway_login_img'] = [
        '#type' => 'markup',
        '#prefix' => '<div id="box" class="image_class">',
        '#suffix' => '</div>',
        '#markup' => '<img src="' . $this->base_url . '/' . $module_path . '/resources/gateway_login.png" alt= "Gateway_login_image" >',
    ];

    $form['save_signin_setting'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#disabled' => true,
        '#value' => t('Save Settings'),
    ];

    $form['register_close'] = [
        '#markup' => '</div></div>',
    ];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
