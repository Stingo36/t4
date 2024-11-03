<?php 


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Alters various node forms to add a submission handler and remove the revision field in edit mode.
 */
function ncbs_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Mapping form IDs to corresponding user field names.
  $form_field_mapping = [
    'node_basic_information_form' => 'field_user_basic_info_ref',
    'node_academic_qualification_form' => 'field_user_academic_qualifi_ref',
    'node_other_relevant_information_form' => 'field_user_other_rel_info_ref',
    'node_update_publications_form' => 'field_user_update_pub_ref',
    'node_research_proposal_form' => 'field_user_research_proposal_ref',
    'node_list_of_referees__form' => 'field_user_list_of_referees_ref',
  ];

  // Add submit handler and assign the relevant user field name for specific forms.
  if (isset($form_field_mapping[$form_id])) {
    $form['actions']['submit']['#submit'][] = 'ncbs_handle_form_submission';
    $form['#field_name'] = $form_field_mapping[$form_id];
  }

  // Remove the revision field for specific node edit forms.
  $target_form_ids = [
    'node_basic_information_edit_form',
    'node_academic_qualification_edit_form',
    'node_other_relevant_information_edit_form',
    'node_list_of_referees__edit_form',
    'node_update_publications_edit_form',
    'node_research_proposal_edit_form',
  ];

  // Hide the revision field for edit forms.
  if (in_array($form_id, $target_form_ids)) {
    $form['revision']['#access'] = FALSE;
  }
}

/**
 * Custom submit handler for node forms to update user fields with node ID.
 */
function ncbs_handle_form_submission(array &$form, FormStateInterface $form_state) {
  $node = $form_state->getFormObject()->getEntity();
  $field_name = $form['#field_name'];
  ncbs_update_user_field($field_name, $node->id());
}

/**
 * Updates a user field with a node reference.
 */
function ncbs_update_user_field($field_name, $node_id) {
  $user = User::load(\Drupal::currentUser()->id());
  
  if ($user) {
    $user->set($field_name, ['target_id' => $node_id]);
    $user->save();
  }
}

/**
 * Alters the submit application edit form for specific user roles and adds custom validation.
 */
function ncbs_form_node_submit_application_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_id == 'node_submit_application_edit_form') {
    $current_user = \Drupal::currentUser();

    // Add custom validation and modify form for 'user' role.
    if (in_array('user', $current_user->getRoles())) {
      $form['#validate'][] = 'ncbs_custom_validation';
      $form['actions']['submit']['#value'] = 'Submit Application';
      $form['body']['widget'][0]['value']['#default_value'] = 'Your Application is not submitted';
      $form['revision']['#access'] = FALSE;
    }

    // Display messages based on user role.
    if (in_array('dean', $current_user->getRoles())) {
      \Drupal::messenger()->addMessage(t('You have the dean role.'));
    } else {
      \Drupal::messenger()->addMessage(t('You have the User role.'));
    }
  }
}
