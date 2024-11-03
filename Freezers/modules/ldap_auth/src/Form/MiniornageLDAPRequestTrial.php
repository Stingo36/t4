<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\MiniorangeLdapSupport;
use Drupal\ldap_auth\Utilities;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\HtmlCommand;

/**
 *
 */
class MiniornageLDAPRequestTrial extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'mo_ldap_request_support';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
    ];

    $this->config_factory->set('miniorange_ldap_trial_page_visited','True')->save();
    $user_email = Utilities::getCustomerEmail();


    $form['mo_ldap_auth_trial_email_address'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#default_value' => $user_email,
        '#required' => TRUE,
        '#attributes' => [
            'placeholder' => $this->t('Enter your email'),
            'style' => 'width:99%;margin-bottom:1%;',
        ],
    ];

    $feature_list = [
        "Auto Create user in Drupal",
        "Authentication restrictions",
        "NTLM/Kerberos Login",
        "Attribute Mapping",
        "Import users from LDAP server",
        "Role Mapping",
        "LDAP Provisioning",
        "Group Mapping",
        "Custom redirect after Login and Logout",
        "Password Sync",
        "Disable user profile fields",
        "Group/OU based login restriction",
    ];


    $form['select_feature_heading'] = [
        '#markup' => $this->t('<b>Select required features:</b>'),
    ];

    $form['select_all_feature'] = [
        '#type' => 'checkbox',
        '#title' => 'Select all',
    ];

    $form['ldap_feature_list'] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['mo_trial_features']],
    ];

    $i=0;
    foreach (array_chunk($feature_list,2) as $feature_list_chuncks) {
      foreach ($feature_list_chuncks as $ldap_feature) {
        $form['ldap_feature_list'][$i][$ldap_feature] = [
            '#type' => 'checkbox',
            '#title' => $ldap_feature,
            '#states' => [
                'checked' => [
                    ':input[name="select_all_feature"]' => ['checked' => TRUE],
                ],
            ],
        ];
      }
      $i++;
    }

    $form['mo_ldap_auth_trial_description'] = [
        '#type' => 'textarea',
        '#rows' => 4,
        '#title' => t('Description'),
        '#attributes' => [
            'placeholder' => $this->t('Describe your use case here!'),
            'style' => 'width:99%;',
        ],
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['send'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#attributes' => [
            'class' => [
                'use-ajax',
                'button--primary',
            ],
        ],
        '#ajax' => [
            'callback' => [$this, 'submitModalFormAjax'],
            'event' => 'click',
        ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   *
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {

    $form_values = $form_state->getValues();
    $response = new AjaxResponse();
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
    }
    else {

      $email = $form_values['mo_ldap_auth_trial_email_address'];

      $selected_features = "";

      if($form_values['select_all_feature']){
        $selected_features = "ALL";
      }
      else{
        foreach ($form_values['ldap_feature_list'] as $chunck){
          foreach ($chunck as $feature => $value){
            if($value == '1'){
              $selected_features = $selected_features ."<li>".$feature."</li>";
            }
          }
        }
      }

      $query = "<b>Required Features: $selected_features </b><br><b>USE CASE </b>" . ' : ' . $form_values['mo_ldap_auth_trial_description'];

      $query_type = 'trial';
      $trial_clicked_on = $_GET['trial_feature'] ?? '';

      $this->config_factory->set('trial_clicked_on',$trial_clicked_on)->save();
      $support = new MiniorangeLdapSupport($email, '', $query, $query_type);

      [$support_response,$status_code] = $support->sendSupportQuery();

      $this->config_factory->clear('trial_clicked_on')->save();

      $redirect = $_SERVER['HTTP_REFERER'] ??  $this->base_url.'/admin/config/people/ldap_auth/ldap_config';

      if( $status_code != 200 ){
        $this->messenger->addError(t('Error while sending query. Please mail us at <a href="mailto:drupalsupport@xecurify.com?subject=Drupal LDAP Login module - Trial Request"><i>drupalsupport@xecurify.com</i></a> and we will get back to you as soon as we can.'));
        $response->addCommand(new RedirectCommand($redirect));
      }
      else {
        $this->messenger->addStatus(t("Success! Trial query successfully sent. We will provide you with the trial version shortly on your provided mail <i>$email</i>."));
        $response->addCommand(new RedirectCommand($redirect));
      }

    }
    return $response;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}