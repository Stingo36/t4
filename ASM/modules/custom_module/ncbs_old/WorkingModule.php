<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

//ANCHOR - ncbs_mail_alter
/* --------------------- //!  TO GET ALL THE EMAIL KEYS --------------------- */
// function ncbs_mail_alter(&$message) {
//     \Drupal::logger('mail_debug')->debug('Message key: @key', array('@key' => $message['key']));
//   }

/* ---------------------------------- //--- --------------------------------- */

//ANCHOR -  hook_form_FORM_ID_alter -
/* --------------- //!User registration form alter function   - -------------- */

function ncbs_form_user_register_form_alter(&$form, &$form_state, $form_id)
{
    // Add a checkbox for agreeing to terms and conditions.
    $form['instem_register'] = array(
        '#type' => 'checkbox',
        '#title' => '<strong>' . t('Select checkbox if you have registered at InStem for Faculty position') . '</strong>',
        '#weight' => 10,
    );
    // Add a custom submit handler to the form.
    $form['actions']['submit']['#submit'][] = 'ncbs_user_register_form_submit';
}

//ANCHOR -  Custom submit handler for user_register_form 
/* ----------- //! when user Registration form is submitted this function is trigger ---------- */
function ncbs_user_register_form_submit(&$form, &$form_state) {
    $user = $form_state->getFormObject()->getEntity();

    // Activate the user account.
    $user->set('status', 1);    //  to make the profile active
    $user->addRole('user');     //  add role
    $user->notify = FALSE;      //  Notify False
    $user->save();

    // Get the user's name from the profile.
    $userName = $user->get('name')->value;

    // Create a new node.
    $node = \Drupal\node\Entity\Node::create([
        'type' => 'submit_application',
        'title' => 'Submit Application: ' . $userName,
        'field_user_reference' => [
            'target_id' => $user->id(), // Reference the newly created user
        ],
        'uid' => $user->id(), // Set the node author to the newly created user ID
        // Set other fields of the node as needed
    ]);

    // // Set the node author to the newly created user.
    $node->setOwner($user);

    // Save the node.
    $node->save();

    // Add debug messages using Drupal's messenger service.
    // \Drupal::messenger()->addMessage('User registered: ' . $user->id());
    // \Drupal::messenger()->addMessage('Node created with title: ' . $node->getTitle());
    // \Drupal::messenger()->addMessage('Node author updated: ' . $userName);
    // \Drupal::messenger()->addMessage('Node saved with ID: ' . $node->id());

    // Redirect the user to the specified URL.
    //$form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
}



//ANCHOR - hook_form_alter
/* ---------------- //! Hiding user fields when role is user ---------------- */
function ncbs_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
    // Get the current user.
    $user = \Drupal::currentUser();

    // Check if the form is the user edit form and the current user has the "user" role.
    if ($form_id == 'user_form' && $user->hasRole('user')) {
        // Hide the specified fields.
        $form['field_gender']['#access'] = FALSE;
        $form['field_centres']['#access'] = FALSE;
        $form['field_date_of_birth']['#access'] = FALSE;
        $form['field_valid_indian_passport']['#access'] = FALSE;
        $form['field_program']['#access'] = FALSE;

        // Hide the mail field in the user profile.
        $form['account']['mail']['#access'] = FALSE;

        // Add a custom submit handler redirecting it to user view.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_form_submit_handler';
    } else {
        //\Drupal::messenger()->addMessage('Thasdasasd.');
    }

     // Define a mapping from form IDs to user fields
     //!SAVING CT TO USER
    $form_field_mapping = [
        'node_basic_information_form' => 'field_basic_information_referenc',
        'node_academic_qualification_form' => 'field_academic_qualification_ref',
        'node_other_relevant_information_form' => 'field_other_relevant_info_ref',
        'node_update_publications_form' => 'field_update_publications_ref',
        'node_research_proposal_form' => 'field_research_proposal_ref',
        'node_list_of_referees__form' => 'field_list_of_referees_ref',
        
    ];

    // Check if the current form should be altered
    if (isset($form_field_mapping[$form_id])) {
        // Add a submission handler with an argument for the field name
        $form['actions']['submit']['#submit'][] = 'ncbs_handle_form_submission';
        $form['#field_name'] = $form_field_mapping[$form_id];
    }





















}

//ANCHOR - Custom submit handler for the user form.
/* --------------------- //! Redirecting to User Profile -------------------- */
function ncbs_user_form_submit_handler($form, \Drupal\Core\Form\FormStateInterface $form_state)
{
    // Define the redirect URL.
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $redirect_url = 'http://172.16.218.200/ASM/user/' . $user_id;

    // Perform the redirect.
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri($redirect_url, [], ['absolute' => TRUE]));
}

//ANCHOR - hook_menu_local_tasks_alter
/* -------------------- //! Hiding the view tab for user -------------------- */
function ncbs_menu_local_tasks_alter(&$data, $route_name, $route_parameters)
{
    $user = \Drupal::currentUser();

    // Check if the form is the user edit form and the current user has the "user" role.
    if ($user->hasRole('user')) {
        // Check if the current route is the user profile page.
        foreach ($data['tabs'] as &$tabs) {
            foreach ($tabs as $key => $tab) {
                if ($tab['#link']['title'] == 'View') {
                    unset($tabs[$key]);
                }
            }
        }
    }
}

//ANCHOR - hooks_entity_insert
/* --------------------- //!When node/content is created -------------------- */
function ncbs_entity_insert(Drupal\Core\Entity\EntityInterface $entity) {
    // Define the content types to handle
    $content_types = [
        'basic_information' => 'basic_information_nodes',
        'academic_qualification' => 'academic_qualification',
        'other_relevant_information' => 'other_relevant_information',
        'list_of_referees_' => 'list_of_referees',
        'update_publications' => 'update_publications',
        'research_proposal' => 'research_proposal'
    ];

    // Check if the entity is a node and its bundle is one of the specified types
    if ($entity->getEntityTypeId() === 'node' && isset($content_types[$entity->bundle()])) {
        $user_id = $entity->getOwnerId();
        $bundle = $entity->bundle();
        
        // Display a generic message to ensure the function is triggered
        \Drupal::messenger()->addMessage("Entity of type {$bundle} inserted.");

        // Invalidate cache tags specific to the content type and user
        \Drupal::service('cache_tags.invalidator')->invalidateTags(['user:' . $user_id . ':' . $content_types[$bundle]]);

        // Optionally, you might want to avoid invalidating all render caches unless absolutely necessary
         \Drupal::cache('render')->invalidateAll(); // This is generally not recommended unless needed for specific reasons
    }

    //submit application
    // Check if the entity being inserted is of type 'basic_information'.
    if ($entity->getEntityTypeId() === 'node') {
        $type = $entity->bundle();
        
        // Get the current user.
        $user = \Drupal::currentUser();
    
        // Check if the author of the node is the current user.
        if ($entity->getOwner()->id() === $user->id()) {
            // Debugging: Log the current user ID.
            \Drupal::messenger()->addMessage('Current User ID: ' . $user->id());
    
            switch ($type) {
                case 'academic_qualification':
                    $reference_field = 'field_academic_qualification_ref';
                    break;
                case 'basic_information':
                    $reference_field = 'field_basic_information_referenc';
                    break;
                case 'other_relevant_information':
                    $reference_field = 'field_other_relevant_info_ref';
                    break;
                case 'list_of_referees_':
                    $reference_field = 'field_list_of_referees_ref';
                    break;
                case 'research_proposal':
                    $reference_field = 'field_research_proposal_ref';
                    break;
                case 'update_publications':
                    $reference_field = 'field_update_publications_ref';
                    break;
                default:
                    // If the content type doesn't match any of the specified types, exit the script.
                    return;
            }
    
            // Check if the user has already submitted a 'submit_application' entity.
            $existing_submission = \Drupal::entityQuery('node')
                ->condition('type', 'submit_application')
                ->condition('uid', $user->id())
                ->range(0, 1)
                ->accessCheck(TRUE)         // Explicitly set access check to TRUE.
                ->execute();
    
            // Debugging: Log the existing submission IDs.
            \Drupal::messenger()->addMessage('Existing Submission IDs: ' . implode(', ', $existing_submission));
    
            if (!empty($existing_submission)) {
                // Get the ID of the existing submission.
                $submission_id = reset($existing_submission);
    
                // Debugging: Log the ID of the existing submission.
                \Drupal::messenger()->addMessage('Existing Submission ID: ' . $submission_id);
    
                // Update the reference field on the 'submit_application' entity.
                $submission = \Drupal\node\Entity\Node::load($submission_id);
                $submission->set($reference_field, $entity->id());
                // Save the changes to the submission entity.
                $submission->save();
    
                // Debugging: Log the entity ID being saved to the reference field.
                \Drupal::messenger()->addMessage(ucwords(str_replace('_', ' ', $type)) . ' Node ID saved to Submission: ' . $entity->id());
            }
        }
    }
}




//ANCHOR - hooks_user_login
/* --------------------------- //! when user login -------------------------- */
function ncbs_user_login($account)
{
    // Invalidate specific cache tags.
    $tags = [
        'user:' . $account->id() . ':content',
        // Add other relevant cache tags here as needed.
    ];
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);

    // Optionally, clear all cache for scenarios where more drastic cache clearing is needed.
    \Drupal::cache('render')->invalidateAll();
}






/**
 * Implements hook_cron().
 */
function ncbs_cron() {
    $threshold = 3 * 3600 + 10 * 60;  // 3 hours and 10 minutes in seconds
    $current_time = \Drupal::time()->getRequestTime();
    $cutoff_time = $current_time - $threshold;

    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
        'status' => 1,  // Only check active users
    ]);

    foreach ($users as $user) {
        // Calculate the age of the user account in seconds
        $account_age = $current_time - $user->getCreatedTime();
        
        // Block the user if their account age is exactly 3 hours and 10 minutes
        if ($account_age >= $threshold && $account_age <= ($threshold + 60)) {  // Adding a 60 second buffer to account for cron execution intervals
            $user->block();
            $user->save();
            \Drupal::logger('ncbs')->notice('Blocked user with UID: @uid due to account age.', ['@uid' => $user->id()]);
        }
    }
}




//!TEST  







/**
 * Universal submission handler for node forms.
 */
function ncbs_handle_form_submission(array &$form, FormStateInterface $form_state) {
    $node = $form_state->getFormObject()->getEntity();
    $field_name = $form['#field_name'];
    ncbs_update_user_field($field_name, $node->id());
  }
  
  /**
   * Helper function to update user fields.
   */
  function ncbs_update_user_field($field_name, $node_id) {
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($user) {
      $user->set($field_name, ['target_id' => $node_id]);
      $user->save();
    }
  }






