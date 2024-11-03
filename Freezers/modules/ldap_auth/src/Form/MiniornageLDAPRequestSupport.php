<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\MiniorangeLdapSupport;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniornageLDAPRequestSupport extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'mo_ldap_auth_request_support';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $content_type = NULL) {

    $form['#prefix'] = '<div id="modal_support_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
    ];

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "core/drupal.dialog.ajax",
            ],
        ],
    ];

    $user_email = Utilities::getCustomerEmail();

    if($content_type == 'addLdapServer'){

      $form['#title'] = 'Add LDAP Server';
      $form['markup_1'] = [
          '#markup' => $this->t("Please note that you can add only 1 LDAP server in this module. If you wish to configure multiple LDAP Servers, you'll need to upgrade to the All-Inclusive version of the module. Alternatively, you can reach out to us at <a href='mailto::drupalsupport@xecurify.com'>".MiniorangeLDAPConstants::SUPPORT_EMAIL."</a> for further assistance."),
      ];

      $form['miniorange_ldap_upgrade_multiple_server'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => t('Upgrade Now'),
          '#prefix' => '<br><br>',
          '#submit' => ['::upgradeNow'],
          '#attributes'=> ['class' => ['button--small']]
      ];

      return $form;
    }

    $form['markup_1'] = [
        '#markup' => $this->t('<p>Need any help? We can help you with configuring <strong>miniOrange LDAP Login module</strong> on your site. Just send us a query, and we will get back to you soon.</p>'),
    ];

    $form['mo_ldap_auth_support_email_address'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#default_value' => $user_email,
        '#required' => TRUE,
        '#attributes' => [
            'placeholder' => $this->t('Enter your email'),
            'style' => 'width:99%;margin-bottom:1%;',
        ],
    ];

    $form['mo_ldap_auth_customer_support_method'] = [
        '#type' => 'select',
        '#title' => t('What are you looking for'),
        '#options' => [
            'I need Technical Support' => t('I need Technical Support'),
            'I want to Schedule a Setup Call/Demo' => t('I want to Schedule a Setup Call/Demo'),
            'I have Sales enquiry' => t('I have Sales enquiry'),
            'I have a custom requirement' => t('I have a custom requirement'),
            'My reason is not listed here' => t('My reason is not listed here'),
        ],
    ];

    $form['mo_ldap_auth_support_query'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => t('Query'),
        '#attributes' => [
            'placeholder' => $this->t('Describe your query here!'),
            'style' => 'width:99%',
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
      $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
    }
    else {
      $email = $form_values['mo_ldap_auth_support_email_address'];
      $looking_for = $form_values['mo_ldap_auth_customer_support_method'];
      $query = "<b>Looking For: </b>".$looking_for."<br><br>".trim($form_values['mo_ldap_auth_support_query']);
      $query_type = 'Support';

      $support = new MiniorangeLdapSupport($email, '', $query, $query_type);
      [$support_response,$status_code] = $support->sendSupportQuery();

      if($status_code != 200){
        $this->messenger->addError(t('Error while sending query.<br>'.$support_response.'<br>Please mail us at <a href="mailto:drupalsupport@xecurify.com?subject=Drupal LDAP Login module - Need assistance"><i>drupalsupport@xecurify.com</i></a> ,and we will get back to you as soon as we can.'));
        $redirect = $_SERVER['HTTP_REFERER'] ??  $this->base_url.'/admin/config/people/ldap_auth/ldap_config';
        $response->addCommand(new RedirectCommand($redirect));
      }
      else{
        $this->messenger->addStatus(t("Support query successfully sent. We will get back to you shortly on your provided mail <i>$email</i>"));
        $redirect = $_SERVER['HTTP_REFERER'] ??  $this->base_url.'/admin/config/people/ldap_auth/ldap_config';
        $response->addCommand(new RedirectCommand($redirect));
      }
    }

    return $response;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function upgradeNow(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('ldap_auth.licensing');
  }

}