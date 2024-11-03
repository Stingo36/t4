<?php

namespace Drupal\ldap_auth;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\Form\LDAPFormBase;

class SetupNavbarHeader extends LDAPFormBase{
  public static function insertForm(array &$form, FormStateInterface $form_state, $navbar_val){
    if ($navbar_val != 100) {
      $form['#prefix'] = '<div class="mo_ldap_table_layout_1">
                        <div class="mo_ldap_table_layout_nav_bar">
            <div class="container_m1">
                                <div class="table_navbar">
                    <table><th>Step 1: <br>Contact LDAP Server</th><th>Step 2: <br>Perform Test Connection</th><th>Step 3: <br>Select Search Base & Filter</th><th>Step 4: <br>Enable Login using LDAP</th></table>
                </div>
                <progress id="determinate"  value="' . $navbar_val . '" min="0" max="100"> 25% </progress>
            </div>
            </div>
       ';
    }
    return $form;
  }
}
