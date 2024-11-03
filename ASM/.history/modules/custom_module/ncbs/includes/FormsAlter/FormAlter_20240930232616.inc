<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_form_alter().
 * Modifies node forms to add a submission handler that updates a user field with the node ID.
 */
function ncbs_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  // Mapping form IDs to user field names
  $form_field_mapping = [
    'node_basic_information_form' => 'field_user_basic_info_ref',
    'node_academic_qualification_form' => 'field_user_academic_qualifi_ref',
    'node_other_relevant_information_form' => 'field_user_other_rel_info_ref',
    'node_update_publications_form' => 'field_user_update_pub_ref',
    'node_research_proposal_form' => 'field_user_research_proposal_ref',
    'node_list_of_referees__form' => 'field_user_list_of_referees_ref',
  ];

  // Add submission handler for forms that need to update user fields
  if (isset($form_field_mapping[$form_id])) {
    $form['actions']['submit']['#submit'][] = 'ncbs_handle_form_submission';
    $form['#field_name'] = $form_field_mapping[$form_id];
  }

  // Removing the revision field in edit mode for specific forms
  $target_form_ids = [
    'node_basic_information_edit_form',
    'node_academic_qualification_edit_form',
    'node_other_relevant_information_edit_form',
    'node_list_of_referees__edit_form',
    'node_update_publications_edit_form',
    'node_research_proposal_edit_form',
  ];

  if (in_array($form_id, $target_form_ids)) {
    $form['revision']['#access'] = FALSE;
  }
}


/**
 * Submission handler for node forms.
 * Updates a user field with the node ID when a node form is submitted.
 */
function ncbs_handle_form_submission(array &$form, FormStateInterface $form_state)
{
  $node = $form_state->getFormObject()->getEntity();
  $field_name = $form['#field_name'];
  ncbs_update_user_field($field_name, $node->id());
}

/**
 * Helper function to update user fields.
 * Updates a specified user field with a given value.
 */
function ncbs_update_user_field($field_name, $node_id)
{
  $user = User::load(\Drupal::currentUser()->id());
  if ($user) {
    $user->set($field_name, ['target_id' => $node_id]);
    $user->save();
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for node_submit_application_edit_form.
 * Adds custom validation and message on submit Application.
 */
function ncbs_form_node_submit_application_edit_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id == 'node_submit_application_edit_form') {
    $current_user = \Drupal::currentUser();

    if (in_array('user', $current_user->getRoles())) {
      $form['#validate'][] = 'ncbs_custom_validation';
      $form['actions']['submit']['#value'] = 'Submit Application';
      $form['body']['widget'][0]['value']['#default_value'] = 'Your Application is not submitted';
    }

    // Add a message based on the user role
    if (in_array('dean', $current_user->getRoles())) {
      \Drupal::messenger()->addMessage(t('You have the dean role.'));
    } else {
      \Drupal::messenger()->addMessage(t('You have the User role.'));
    }
  }
}

// The `ncbs_node_presave` function has been removed.
