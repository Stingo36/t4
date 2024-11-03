<?php 
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_form_alter().
 *
 * This function modifies various node forms to add a submission handler that updates 
 * a user field with the node ID, and removes the revision field in edit mode.
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

  // Check if the current form ID matches any of the specified node forms.
  // If it does, add the custom submit handler and set the relevant user field name.
  if (isset($form_field_mapping[$form_id])) {
    $form['actions']['submit']['#submit'][] = 'ncbs_handle_form_submission'; // Add custom submit handler.
    $form['#field_name'] = $form_field_mapping[$form_id]; // Assign the field name to the form.
  }

  // Remove the revision field for specific node forms in edit mode.
  $target_form_ids = [
    'node_basic_information_edit_form',
    'node_academic_qualification_edit_form',
    'node_other_relevant_information_edit_form',
    'node_list_of_referees__edit_form',
    'node_update_publications_edit_form',
    'node_research_proposal_edit_form',
  ];

  // If the current form ID is one of the target edit forms, hide the revision field.
  if (in_array($form_id, $target_form_ids)) {
    $form['revision']['#access'] = FALSE; // Disable access to the revision field.
  }
}

/**
 * Custom submit handler for node forms.
 *
 * This function is triggered after a node form submission and updates the corresponding 
 * user field with the ID of the newly created or edited node.
 */
function ncbs_handle_form_submission(array &$form, FormStateInterface $form_state) {
  // Retrieve the node entity from the form state.
  $node = $form_state->getFormObject()->getEntity();
  
  // Retrieve the user field name that corresponds to this node form.
  $field_name = $form['#field_name'];
  
  // Call helper function to update the user field with the node ID.
  ncbs_update_user_field($field_name, $node->id());
}

/**
 * Helper function to update user fields with a node reference.
 *
 * This function loads the current user and updates the specified user field with the 
 * given node ID.
 */
function ncbs_update_user_field($field_name, $node_id) {
  // Load the current user entity.
  $user = User::load(\Drupal::currentUser()->id());
  
  // If the user entity is loaded successfully, update the specified field with the node ID.
  if ($user) {
    $user->set($field_name, ['target_id' => $node_id]); // Set the node reference.
    $user->save(); // Save the user entity with the updated field.
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for the submit application edit form.
 *
 * This function adds custom validation to the "Submit Application" form for users with 
 * the "user" role and modifies the submit button and body default value.
 */
function ncbs_form_node_submit_application_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Check if the current form is the submit application edit form.
  if ($form_id == 'node_submit_application_edit_form') {
    // Get the current user.
    $current_user = \Drupal::currentUser();

    // If the user has the "user" role, add custom validation and modify the form.
    if (in_array('user', $current_user->getRoles())) {
      $form['#validate'][] = 'ncbs_custom_validation'; // Add custom validation function.
      $form['actions']['submit']['#value'] = 'Submit Application'; // Modify the submit button text.
      $form['body']['widget'][0]['value']['#default_value'] = 'Your Application is not submitted'; // Set a default message in the body field.
    }

    // Add a message indicating the role of the current user.
    if (in_array('dean', $current_user->getRoles())) {
      \Drupal::messenger()->addMessage(t('You have the dean role.')); // Message for dean role.
    } else {
      \Drupal::messenger()->addMessage(t('You have the User role.')); // Message for user role.
    }
  }
}
