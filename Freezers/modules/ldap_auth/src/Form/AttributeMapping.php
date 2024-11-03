<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\AuditAndLogs;
use Drupal\ldap_auth\Utilities;
use Drupal\Component\Utility\Html;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\ldap_auth\MiniorangeLDAPConstants;

/**
 *
 */
class AttributeMapping extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_attrmapping';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $upgrade_tab_link = $this->getRouteUrl('ldap_auth.licensing');
    $premium_tag = "<a href=$upgrade_tab_link target='_self'>Premium</a>";
    $all_inclusive_tag = "<a href=$upgrade_tab_link target='_self'>All-Inclusive</a>";

    $form['markup_library'] = [
        '#attached' => [
            'library' => [
                "ldap_auth/ldap_auth.admin",
                "ldap_auth/ldap_auth.testconfig",
                "core/drupal.dialog.ajax"
            ],
        ],
    ];

    $form['markup_top'] = [
        '#markup' => t('<div class="mo_ldap_table_layout_1_mapping"><div class="mo_ldap_table_layout_mapping container" >
          <span><h2>Attribute Mapping <a class="button button--primary button--small" style="float:right;margin: 1%;" href ='.MiniorangeLDAPConstants::ATTRIBUTE_MAPPING.' target="_blank">&#128366;  How to Perform Mapping</a></h2></span><hr>'),
    ];

    $form['miniorange_ldap_email_attribute'] = [
        '#type' => 'textfield',
        '#title' => t('Email Attribute'),
        '#required' => TRUE,
        '#attributes' => [
            'style' => 'width:700px;',
            'placeholder' => t('Enter email attribute eg. mail, userprincipalname'),
        ],
        '#default_value' => $this->config->get('miniorange_ldap_email_attribute'),
        '#description' => t("Enter the LDAP attribute in which you get the email address of your users."),
    ];

    $form['miniorange_ldap_mapping_submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save Configuration'),
        '#suffix' => '<br>',
    ];

    $form['user_attr_mapping'] = [
        '#type' => 'details',
        '#title' => $this->t("Custom Attribute Mapping"),
        '#open' => true,
    ];

    $form['user_attr_mapping']['markup_cam'] = [
        '#markup' => '<div class="mo_ldap_highlight_background_note_1">In this section you can map any attribute of the AD/LDAP Server to the Drupal user profile field.
      To add a new Drupal field go to Configuration->Account Settings-><a href = "'.$this->base_url.'/admin/config/people/accounts/fields" target="_blank">Manage fields</a> and then click on Add field.
      <br><br>
      <li><b>LDAP Attribute Name</b>: Select attribute name recieved from LDAP Server which you want to map with custom Drupal user profile field.</li>
      <li><b>Drupal Field Machine Name</b>: Machine Name of the Drupal user profile field.</li>
      <p>This feature is available in the '.$premium_tag.','.$all_inclusive_tag.' version of the module.</p>
      </div>',
    ];

    $form['user_attr_mapping']['info'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<small><strong>NOTE</strong> : This mapping will be useful for both the cases : <br> <ul><li>Mapping from <b>LDAP</b> <span style="font-size:25px;">&#8594;</span> Drupal</li><li>Mapping from Drupal <span style="font-size:25px;">&#8594;</span> <b>LDAP</b> <a href='.$this->getRouteUrl('ldap_auth.user_sync').'><b>[LDAP Provisioning]</b></a></li></ul></small>'),
        '#prefix' => '<br>',
    ];


    $row = [];

    $row['drupal_attribute'] = [
        '#type' => 'select',
        '#options' => $this->getDrupalFieldList(),
    ];

    $row['ldap_attribute'] = [
        '#type' => 'select',
        '#options' => $this->getLDAPattributeList(),
       '#attributes' => ['style' => 'width:250px'],
    ];

    $row['button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'primary',
        '#disabled' => true,
    ];

    $form['user_attr_mapping']['attribute_table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#sticky' => TRUE,
        '#header' => [
            $this->t("Drupal Field Machine Name"),
            $this->t("LDAP Attribute Name"),
            $this->t("")
        ],
    ];

    $form['user_attr_mapping']['attribute_table']['row1'] = $row;

    $form['user_attr_mapping']['addRow'] = [
        '#type'        => 'submit',
        '#button_type' => 'primary',
        '#value'       => $this->t('<b>Add more</b>'),
        '#disabled' => true,
    ];

    $form['user_attr_mapping']['miniorange_ldap_attribute_submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save Configuration'),
        '#disabled' => true,
        '#suffix' => '<br>',
    ];

    /**
     * User Role Mapping Feature
     */

    $form['role_mapping'] = [
        '#type' => 'details',
        '#title' => $this->t('LDAP Group/OU to Drupal Role Mapping   <a style="float: right" class="js-form-submit form-submit use-ajax" data-dialog-options="{&quot;width&quot;:&quot;40%&quot;}" data-dialog-type="modal"  href='.$this->getRouteUrl('ldap_auth.request_trial').'?trial_feature=Role_Mapping>Try this feature!</a>'),
    ];

    $form['role_mapping']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
    ];

    $form['role_mapping']['info']['role_mapping_markup_note'] = [
        '#markup' => $this->t('<div>
<ul style="font-size:small">
<li><b>Assign Drupal Roles based on the users AD/LDAP Groups.</b></li>
<li>This feature is available in the '.$premium_tag.','.$all_inclusive_tag.' version of the module.</li>
<a class="button button--primary button--small" href='.MiniorangeLDAPConstants::VIDEO_LDAP_ROLE_MAPPING.' target="_blank">▶ Watch video</a>
<a  class="button button--primary button--small" href='.MiniorangeLDAPConstants::ROLE_MAPPING_GUIDE.' target="_blank">🕮 Setup guide</a>
</ul>
</div>'),
    ];

    $form['role_mapping']['miniorange_ldap_enable_rolemapping'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable Role Mapping'),
        '#description' => t('Automatically assign Drupal roles based on below configured LDAP Groups.'),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#disabled' => TRUE,
    ];

    $form['role_mapping']['miniorange_ldap_disable_role_update'] = [
        '#type' => 'checkbox',
        '#title' => t("Keep existing roles if roles are not mapped below"),
        '#disabled' => TRUE,
    ];

    $form['role_mapping']['miniorange_ldap_enable_ntlm_role_mapping'] = [
        '#type' => 'checkbox',
        '#disabled' => TRUE,
        '#title' => t('Enable Role Mapping for NTLM Users'),
        '#description' => t('Upon windows auto-login/kerberos authentication assign Drupal roles based on below configured LDAP Groups.'),
    ];

    $mrole = array_map(function (RoleInterface $role) { return $role->label();},Role::loadMultiple());
    unset($mrole['anonymous']);

    $drole = array_values($mrole);

    $form['role_mapping']['miniorange_ldap_default_mapping'] = [
        '#type' => 'select',
        '#title' => t('Select default role for the new users'),
        '#options' => $mrole,
        '#default_value' => $drole,
        '#attributes' => ['style' => 'width:45%;'],
        '#disabled' => FALSE,
    ];

    $form['role_mapping']['miniorange_ldap_memberOf'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#title' => t('LDAP Group Attribute Name'),
        '#attributes' => ['style' => 'width:45%;', 'placeholder' => 'memberOf'],
        '#description' => "LDAP attribute in which you will get your user's LDAP group. Default value is memberof"
    ];

    $row = [];
    $row['drupal_roles'] = [
        '#type' => 'select',
        '#options' => $mrole,
        '#disabled' => false,
    ];
    $row['ldap_group_dn'] = [
        '#type' => 'textfield',
        '#disabled' => true,
        '#attributes' => [
            'placeholder' => $this->t('Enter the LDAP Group DN semicolon(;) seperated'),
        ],
    ];

    $row['button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'primary',
        '#disabled' => true,
    ];

    $form['role_mapping']['role_maping_table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#sticky' => TRUE,
        '#header' => [
            $this->t("Drupal Role"),
            $this->t("LDAP Group DN"),
            $this->t(""),
        ],
    ];

    $form['role_mapping']['role_maping_table']['row1'] = $row;

    $form['role_mapping']['role_mapping_addRow'] = [
        '#type'        => 'submit',
        '#button_type' => 'primary',
        '#value'       => $this->t('<b>Add more</b>'),
        '#disabled' => true,
    ];

    $form['role_mapping']['miniorange_ldap_rolemapping_submit'] = [
        '#type' => 'submit',
        '#value' => t('Save Configuration'),
        '#disabled' => TRUE,
    ];

    // Group mapping feature advertise

    $form['group_mapping'] = [
        '#type' => 'details',
        '#title' => $this->t('LDAP Group to Drupal Group Mapping  <a style="float: right" class="js-form-submit form-submit use-ajax"  data-dialog-options="{&quot;width&quot;:&quot;40%&quot;}" data-dialog-type="modal"  href='.$this->getRouteUrl('ldap_auth.request_trial').'?trial_feature=Group_Mapping>Try this feature!</a>'),
        "#disabled" => true,
    ];

    $form['group_mapping']['info'] = [
        '#type' => 'fieldset',
        '#attributes' => [
            'style' => 'background-color:#f3f3f3;box-shadow:none;border:none;width:80%;',
            'class' => ['ldap-user-sync'],
        ],
      '#prefix' => '<div>',
    ];

    $form['group_mapping']['info']['group_mapping_markup_note'] = [
        '#markup' => $this->t('<div>
<ul style="font-size:small">
<li><b>Assign Drupal Groups to user based on their AD/LDAP Groups.</b></li>
<li>You can create the Drupal groups using the Drupal <a href="https://www.drupal.org/project/group" target="_blank">Group module</a>.</li>
<li>This feature is available in the '.$all_inclusive_tag.' version of the module.</li>
<a  class="button button--primary button--small" href='.MiniorangeLDAPConstants::GROUP_MAPPING_GUIDE.' target="_blank">🕮 Setup guide</a>
</ul>
</div>'),
    ];

    $form['group_mapping']['enable_group_mapping'] = [
        "#type" => 'checkbox',
        "#title" => $this->t("Enable Group mapping."),
        "#description" => $this->t("Enabling Group Mapping will automatically map Users from LDAP Groups to below mapped Drupal Group."),
    ];

    $form['group_mapping']['enable_group_mapping_ntlm'] = [
        "#type" => 'checkbox',
        "#title" => $this->t("Enable Group mapping for NTLM users."),
        "#description" => $this->t("Enabling Group Mapping will automatically map Users from LDAP Groups to below mapped Drupal Group in NTLM flow."),
        "#suffix" => "</div>",
    ];

    $row = [];
    $row['drupal_group'] = [
        '#type' => 'select',
        '#disabled' => true,
    ];
    $row['ldap_group'] = [
        '#type' => 'textfield',
        '#disabled' => true,
    ];
    $row['button'] = [
        '#type' => 'submit',
        '#value' =>'Delete',
        '#button_type' => 'primary',
        '#disabled' => true,
    ];

    $form['group_mapping']['group_table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#sticky' => TRUE,
        '#disabled' => TRUE,
        '#header' => [
            $this->t("Drupal Group Name"),
            $this->t("LDAP Group DN"),
            $this->t(""),
        ],
    ];

    $form['group_mapping']['group_table']['row1'] = $row;

    $form['group_mapping']['addRow'] = [
        '#type'        => 'submit',
        '#button_type' => 'primary',
        '#value'       => $this->t('<b>&#43;</b>'),
    ];

    $form['group_mapping']['save_attributes'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Save Group Mapping'),
        '#suffix' => "</div>",
    ];

    $this->AddShowAttributeButton($form, $form_state);

    Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config_factory->set('miniorange_ldap_email_attribute', strtolower(trim($form_state->getValue('miniorange_ldap_email_attribute'))))->save();
    $this->messenger->addStatus($this->t('Attribute Mapping saved successfully.'));
  }


  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function AddShowAttributeButton(array &$form, FormStateInterface $form_state) {

    $base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();;

    $ldap_user_attributes_and_values = \Drupal::config('ldap_auth.settings')->get('ldap_user_attributes_and_values');
    $ldap_user_attributes_and_values = json_decode($ldap_user_attributes_and_values ?? '',true);

    $form['ldap_show_attributes'] = [
      '#type' => 'markup',
      '#markup' => '<div class="mo_ldap_table_layout_support_1">'
    ];

    $form['markup_support_1'] = [
      '#type' => 'markup',
      '#markup' => t("<h4>Users LDAP Attributes:</h4>"),
    ];


    $attr_table_content = [];

    if(is_array($ldap_user_attributes_and_values)){
      foreach ($ldap_user_attributes_and_values as $ldap_attribute_name => $ldap_attribute_value){
        $attr_table_content[] = [$ldap_attribute_name,$ldap_attribute_value];
      }
    }

    $header = [
      'LDAP Attribute Name',
      'LDAP Attribute Value',
    ];

    $form['show_attribute_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $attr_table_content,
      '#empty' => t('<b>No attribute received from the LDAP server.</b>'),
      '#attributes' => ['class' => ['mo_ldap_attr_table']],
      '#prefix' => '<div style="width:10%">',
      '#suffix' => '</div>',
    ];

    if(sizeof($attr_table_content)!=0) {
      $form['mo_ldap_clear_attribute_button']= array(
        '#type' => 'submit',
        '#prefix'=> '<br>',
        '#value' => t('Clear Attribute'),
        '#button_type' => 'warning',
        '#submit' => array('::clearAttribute'),
      );
      $form['ldap_show_attributes_note'] = [
        '#markup' => '<p><b>NOTE : </b>Please clear this list after configuring the module to hide your confidential attributes.<br>
                            Click on <a href='.$base_url."/admin/config/people/ldap_auth/ldap_config?action=testing".'>Test Authentication</a> under the <b>LDAP Configuration</b> tab to populate the list again.</p>',
      ];
    }
    else{
      $form['ldap_show_attributes_note'] = [
        '#markup' => t('<p><b>NOTE :</b> Please do the <a href='.$base_url."/admin/config/people/ldap_auth/ldap_config?action=testing".'>Test Authentication</a> under the <b>LDAP Configuration</b> tab to populate the users LDAP Attributes list here.</p>'),
      ];
    }

    $form['ldap_show_attributes_end_tag'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];

  }

  public function clearAttribute(){
      $this->config_factory->clear('ldap_user_attributes_and_values')->save();
  }

  private function getLDAPattributeList() {

    $ldapAttributeOptions = $this->config->get('ldap_attribute_list');
    $ldapAttributeOptions = json_decode($ldapAttributeOptions ?? '',TRUE);

    return array_merge(['select' => '-Select LDAP Attribute-'],$ldapAttributeOptions ?? []);
  }

  private function getDrupalFieldList() {

    $allDrupalFieldsOption['select'] = '-Select Drupal Field-';
    $allDrupalFields =  \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user', 'user');

    foreach ($allDrupalFields as $field_name => $field_object){
      $allDrupalFieldsOption[$field_name] = $field_name;
    }

    $fields_to_remove = ['uid','uuid','roles','init','access'];
    foreach ($fields_to_remove as $field){
      unset($allDrupalFieldsOption[$field]);
    }

    return $allDrupalFieldsOption;
  }

}