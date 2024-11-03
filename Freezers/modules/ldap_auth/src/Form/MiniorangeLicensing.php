<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\MiniorangeLDAPConstants;
use Drupal\ldap_auth\MiniorangeLdapSupport;
use Drupal\ldap_auth\Utilities;
use Drupal\Core\Render\Markup;

/**
 *
 */
class MiniorangeLicensing extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_licensing';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "ldap_auth/ldap_auth.style_settings",
                "ldap_auth/ldap_auth.main",
                "core/drupal.dialog.ajax",
                "ldap_auth/ldap_auth.mo_ldap_tooltip",
            ],
        ],
    ];

    $this->config_factory->set('miniorange_ldap_license_page_visited', "True")->save();
    $upgrade_plan_link = $this->getRouteUrl('ldap_auth.licensing');

    $refer = $_SERVER['HTTP_REFERER'] ?? $this->getRouteUrl('ldap_auth.ldap_config');

    if($refer == $upgrade_plan_link){
      $refer = $this->getRouteUrl('ldap_auth.ldap_config');
    }

    $form['markup_1'] = [
        '#markup' => $this->t('<div class="mo_ldap_licensing_table_layout">
            <div class="mo_ldap_license_layout">'),
    ];

    $form['heading'] = [
        "#markup" => $this->t('<a href='. $refer .' class="button button--danger" style="float:left;">&#11164;&nbsp;BACK</a><h2 style="text-align: center;">Licensing Plans</h2><br>'),
    ];

    $module_path = $this->moduleList->getPath("ldap_auth");

    $features = $this->getFeatureListInRows();

    $form['ldap_upgrade_tab'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#rows' => $features,
        '#size' => 5,
        '#attributes' => ['class' => ['mo_upgrade_plans_features']],
        '#suffix' => '<br>'
    ];

    $form['instance_info'] = [
        '#type' => 'details',
        "#title" => $this->t("<b>WHAT IS INSTANCE ?</b>"),
        '#open' => TRUE,
    ];

    $form['instance_info']['explanation'] = [
        '#markup' => $this->t("A Drupal instance refers to a single installation of a Drupal site. It refers to each individual website where the module is activated. In the case of multisite/subsite Drupal setup, each site with a separate database will be counted as a single instance. For eg. If you have the dev-staging-prod type of environment then you will require 3 licenses of the module (with additional discounts applicable on pre-production environments). Contact us at <a href='mailto:drupalsupport@xecurify.com'>drupalsupport@xecurify.com</a> for bulk discounts.")
    ];

    $form['upgrade_steps'] = [
        '#type' => 'details',
        '#title' => $this->t('<b>HOW TO UPGRADE TO THE LICENSED VERSION OF THE MODULE ?</b>'),
        '#open' => TRUE,
    ];

    $form['upgrade_steps']['steps'] = [
        '#markup' => '<div class="row">
   <div class="col-md-6">
     <div class="upgrade_step"><div class="upgrade_steps_wise">1</div> Click on Upgrade Now button for required licensed version plan and you will be redirected to miniOrange login console.</div>
     <div class="upgrade_step"><div class="upgrade_steps_wise">2</div> Enter your username and password with which you have created an account with us. If you do not have an account with us, you can create an account from the <a href="https://www.miniorange.com/businessfreetrial" target="_blank">link here</a>. After that you will be redirected to payment page.</div>
     <div class="upgrade_step"><div class="upgrade_steps_wise">3</div> Enter your card details and proceed for payment. On successful payment completion, the licensed version will be available for download.</div>
   </div>
   <div class="col-md-6">
        <div class="upgrade_step"><div class="upgrade_steps_wise">4</div> Download the licensed version module from under the Releases and Downloads section.</div>
    <div class="upgrade_step"><div class="upgrade_steps_wise">5</div> Uninstall and then delete the free version of the module from your Drupal site. Now install the downloaded latest version of the module</div>
   </div>
  </div>',
    ];

    $form['quote_request_1'] = [
        '#type' => 'details',
        '#title' => $this->t("<b>REQUEST QUOTE</b>"),
        '#open' => TRUE,
    ];

    $form['quote_request_1']['miniorange_ldap_email'] = [
        '#type' => 'email',
        '#default_value' => Utilities::getCustomerEmail(),
        '#title' => t('Email:'),
        '#attributes' => ['placeholder' => 'name@example.com','style' => 'max-width : 42%;'],
        '#required' => TRUE,
    ];

    $form['quote_request_1']["miniorange_ldap_number_of_instances"] = [
        "#type" => "number",
        "#title" => t("Enter the number of instances: "),
        '#default_value' => t("3"),
        '#attributes' => ['style' => 'width : 42%;'],
        '#min' => 1,
        '#max' => 100,
    ];

    $form['quote_request_1']["foobar_options"]["miniorange_ldap_plan_select"] = [
        "#type" => "radios",
        "#title" => t("Select your plan:"),
        "#options" => [
            "Standard" => t("Standard &nbsp;&nbsp;"),
            "Premium" => t("Premium &nbsp;&nbsp;"),
            "All-Inclusive" => t("All-Inclusive &nbsp;&nbsp;"),
            "Not-Sure" => t("Not Sure"),
        ],
        '#default_value' => 'All-Inclusive',
        '#attributes' => ['style' => 'max-width : 42%;'],
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>'
    ];

    $form['quote_request_1']['miniorange_ldap_support_comment'] = [
        '#type' => 'textarea',
        '#title' => t('Specify your use case:'),
        '#cols' => '10',
        '#rows' => '5',
        '#attributes' => ['style' => 'max-width : 42%;height:80px;', 'placeholder' => 'Write your use case here.'],
        '#required' => TRUE,
    ];

    $form['quote_request_1']['miniorange_ldap_support_submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit Query'),
        '#submit' => ['::saved_request_quote'],
    ];

    $form['payment_methods'] = [
        '#type' => 'details',
        '#title' => $this->t('PAYMENT METHODS'),
        '#open' => TRUE,
    ];

    $form['payment_methods']['all_methods'] = [
        '#markup' => '<div class="row">
        <div class="col-md-3 payment_method_inner_divs">
            <br><div><img src="' . $this->base_url . '/' . $module_path . '/resources/card_payment.png" width="120" ></div><hr>
            <p>If the payment is made through Credit Card/International Debit Card, the license will be created automatically once the payment is completed.</p>
        </div>
        <div class="col-md-3 payment_method_inner_divs">
            <br><div><img src="' . $this->base_url . '/' . $module_path . '/resources/bank_transfer.png" width="150" ></div><hr>
            <p>If you want to use bank transfer for the payment then contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> so that we can provide you the bank details.</p>
        </div>
    </div>',
        '#suffix' => '</div><div>'
    ];

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  public static function saved_request_quote(array &$form, FormStateInterface $form_state) {

    $email = trim($form_state->getValue('miniorange_ldap_email'));

    if (!\Drupal::service('email.validator')->isValid($email)) {
      Utilities::add_message(t('The email address <b><i>' . $email . '</i></b> is not valid.'), 'error');
      return;
    }

    $instances = $form_state->getValue('miniorange_ldap_number_of_instances');
    $planname = $form_state->getValue('miniorange_ldap_plan_select');
    $query1 = trim($form_state->getValue('miniorange_ldap_support_comment'));
    $query = $query1 . '<br> No of Instances: ' . $instances . '<br> Plan: ' . $planname;

    if (empty($email) || empty($query)) {
      Utilities::add_message(t('The <b><u>Email</u></b> and <b><u>Query</u></b> fields are mandatory.'), 'error');
      return;
    }$support = new MiniorangeLdapSupport($email, '', $query, 'request_quote');
    [$support_response,$status_code] = $support->sendSupportQuery();

    if( $status_code != 200 ){
      \Drupal::messenger()->addError(t('Error while sending query. Please mail us at <a href="mailto:drupalsupport@xecurify.com?subject=Drupal LDAP Login module - Trial Request"><i>drupalsupport@xecurify.com</i></a> and we will get back to you as soon as we can.'));
    }
    else {
      \Drupal::messenger()->addStatus(t("Quotation request sent successfully! We'll be in touch soon on <i>$email</i>."));
    }

  }

  private function getFeatureListInRows() :array{

    $miniorangeBaseUrl = MiniorangeLDAPConstants::PORTAL_URL."/initializePayment?requestOrigin=";

    $standard = Markup::create(t('<a class="button button--primary button--small" target="_blank" href = "'.$miniorangeBaseUrl.'drupal8_ldap_standard_plan">Upgrade Now</a>')) ;
    $premium = Markup::create(t('<a class="button button--primary button--small" target="_blank" href="'.$miniorangeBaseUrl.'drupal8_ldap_premium_plan">Upgrade Now</a>'));
    $allincluisve  = Markup::create(t('<a class="button button--primary button--small" target="_blank" href="'.$miniorangeBaseUrl.'drupal_ldap_allinclusive_plan">Upgrade Now</a>'));

    return [
        [ Markup::create(t('<h3>FEATURES</h3>')), Markup::create(t('<br><h2>Free</h2> <p class="mo_ldap_pricing-rate"><sup>$</sup> 0</p><h4>  &nbsp;</h4>')), Markup::create(t("<br><h2>Standard</h2><p class='mo_ldap_pricing-rate'><sup>$</sup> 249</p>")) , Markup::create(t('<br><h2>Premium</h2><p class="mo_ldap_pricing-rate"><sup>$</sup> 399 </p>')), Markup::create(t('<br><h2>All-Inclusive</h2><p class="mo_ldap_pricing-rate"><sup>$</sup> 449</p>')),],

        [ '', Markup::create(t('<span class="button button--small">Current Plan</span>')),$standard , $premium, $allincluisve],

        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_AUTHENTICATION))),              Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_DIRECTORY))),              Markup::create(t('Single')), Markup::create(t('Single')), Markup::create(t('Single')), Markup::create(t('Multiple')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_SEARCH_FILTER))),              Markup::create(t('Single attribute')), Markup::create(t('Single attribute')), Markup::create(t('Multiple attributes')), Markup::create(t('Multiple attributes')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_SEARCH_FILTER))),              Markup::create(t('Single Search Base')), Markup::create(t('Single Search Base')), Markup::create(t('Multiple Search Bases')), Markup::create(t('Multiple Search Bases')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_ATTRIBUTE_MAPPING))),              Markup::create(t('Only Email Mapping')), Markup::create(t('Only Email Mapping')), Markup::create(t('Custom Mapping')), Markup::create(t('Custom Mapping')), ],

        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_AUTOCREATE_USER))),              Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_ROLE_MAPPING))),              Markup::create(t('')), Markup::create(t('Only default role')), Markup::create(t('Advanced')), Markup::create(t('Advanced')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_TLS_CONNECTION))),              Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_CUSTOM_INTEGRATION))),              Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],

        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_KERBEROS))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_GROUP_RESTRICTION))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), ],

        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_GROUP_MAPPING))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_REDIRECT))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_PAGE_RESTRICTION))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_IMPORT_USER))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), ],
        [ Markup::create(t($this->tooltips(MiniorangeLDAPConstants::LDAP_SYNC))),              Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), ],
    ];

  }

  private function tooltips($feature_name){

    $feature_list =  $this->getFeaturesList();
    $feature_title = $feature_list[$feature_name]['title'];
    $feature_description = $feature_list[$feature_name]['description'];

    $helper_text = '<div class="mo-ldap--help--content">'.$feature_description. '</div>';
    $helper_text = htmlspecialchars($helper_text, ENT_QUOTES, 'UTF-8');

    return '<b>'.$feature_title.'</b><span role="tooltip" tabindex="0" aria-expanded="false" class="mo-ldap--help js-miniorange-ldap-help miniorange-ldap-help" data-miniorange-ldap-help="'.$helper_text.'"><span aria-hidden="true">?</span></span>';
  }

  private function getFeaturesList(){

    $features = [] ;

    $features[MiniorangeLDAPConstants::LDAP_AUTHENTICATION] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_AUTHENTICATION,
        'description' =>  'Allow your Drupal users to login to your site using their LDAP/AD credentials.',
    ];
    $features[MiniorangeLDAPConstants::LDAP_DIRECTORY] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_DIRECTORY,
        'description' =>  "You can configure the single LDAP server in the module. Multiple LDAP server is supported in All-Inclusive version of the module.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_SEARCH_FILTER] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_SEARCH_FILTER,
        'description' =>  "Search filters enable you to define search criteria and provide more efficient and effective searches. Search with multiple attributes are present in the paid version of the module.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_SEARCH_BASE] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_SEARCH_BASE,
        'description' =>  "Search Base denotes the location in the directory where the search for a particular directory object begins. If you want to search your users at multiple location (OUs and DCs) then you will require multiple Search Bases.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_AUTOCREATE_USER] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_AUTOCREATE_USER,
        'description' =>  "This feature allows for automatic creation of users on your Drupal site when a user attempts to log in using their LDAP server credentials and the user does not already exist on your Drupal site.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_ATTRIBUTE_MAPPING] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_ATTRIBUTE_MAPPING,
        'description' =>  "This feature allows you to map your LDAP users information to the Drupal site users. Like you can map the email, sn(last name), cn(common name) to the Drupal user fields.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_ROLE_MAPPING] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_ROLE_MAPPING,
        'description' =>  "This feature allows you to assign the Drupal roles to your users on the basis of their LDAP Group or OU.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_CUSTOM_INTEGRATION] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_CUSTOM_INTEGRATION,
        'description' =>  "We can provide you the customization according to your use case.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_TLS_CONNECTION] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_TLS_CONNECTION,
        'description' =>  "TLS Connection",
    ];
    $features[MiniorangeLDAPConstants::LDAP_KERBEROS] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_KERBEROS,
        'description' =>  "This feature allows your user to login your drupal site using the NTLM and Kerberos Authentication protocol.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_GROUP_MAPPING] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_GROUP_MAPPING,
        'description' =>  "This feature allows you to assign the Drupal groups to your users on the basis of their LDAP Group or OU.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_REDIRECT] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_REDIRECT,
        'description' =>  "This feature allows you to redirect your user on the certain page or some custom url after successfully login and logout.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_PAGE_RESTRICTION] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_PAGE_RESTRICTION,
        'description' =>  "This feature allows you to restrict your drupal site pages/nodes on the basis of the users LDAP Group or OU.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_IMPORT_USER] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_IMPORT_USER,
        'description' =>  "This feature allows you to import all your users present on the LDAP server with considering the Attribute mapping and Role mapping configurations.",
    ];
    $features[MiniorangeLDAPConstants::LDAP_SYNC] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_SYNC,
        'description' =>  "<ol><li>This features allows you to sync your LDAP users with the Drupal site.</li> <li>If user is created/updated on your Drupal site then the user will automatically get created/updated on the LDAP server and vice versa.</li><li>The users Attribute and Roles will also get sync.</li>",
    ];
    $features[MiniorangeLDAPConstants::LDAP_GROUP_RESTRICTION] = [
        'title' =>  MiniorangeLDAPConstants::LDAP_GROUP_RESTRICTION,
        'description' =>  "Manage user logins based on LDAP groups. Specify the groups to allow or restrict user LDAP login.",
    ];

    return $features;
  }
}