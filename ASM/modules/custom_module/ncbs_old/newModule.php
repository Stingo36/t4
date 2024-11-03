<?php

//  TO RUN CRON
//  sudo crontab -u www-data -e
//  inside crontab 
//  * * * * * /usr/bin/php /var/www/html/ASM/vendor/bin/drush --root=/var/www/html/ASM core:cron >> /var/www/html/ASM/drupal_cron.log 2>&1

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**   
 * Implementing hook_form_FORM_ID_alter()
 */
// function jobportal_form_node_application_form_alter(&$form, FormStateInterface $form_state, $form_id) 
// {
//   if($form_id == "node_application_form")




// function ncbs_form_node_test_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
//     if ($form_id == 'node_test_edit_form') {
//         // Set the default value of 'field_status1' to 'Closed'.
//         $form['field_status1']['widget']['#default_value'] = 'Closed';
//     }
// }


















/* ----------------------------- //! FORM ALTER ----------------------------- */
/**   
 * Implementing hook_form_FORM_ID_alter()
 */
//ANCHOR -   user Register form
function ncbs_form_user_register_form_alter(&$form, &$form_state, $form_id)
{
    if ($form_id == "user_register_form") {
        // Add a checkbox for agreeing to terms and conditions.
        // $form['instem_register'] = array(
        //     '#type' => 'checkbox',
        //     '#title' => '<strong>' . t('Select checkbox if you have registered at InStem for Faculty position') . '</strong>',
        //     '#weight' => 10,
        // );
        // Add a custom submit handler to the form.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_register_form_submit';
    }
}

//ANCHOR -  Custom submit handler for user_register_form 
/* ---- //! When User Register form is submitted this function is trigger --- */
function ncbs_user_register_form_submit(&$form, &$form_state)
{
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
        'title' => $userName,
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
}


//ANCHOR - User Edit Form
// /* ------------------- //! Hiding user fields when role is user ------------------ */
function ncbs_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id)
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
}

//ANCHOR - Custom submit handler for the user_form.
/* --------------------- //! Redirecting to User Profile -------------------- */
function ncbs_user_form_submit_handler($form, \Drupal\Core\Form\FormStateInterface $form_state)
{
    // Define the redirect URL.
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $redirect_url = 'http://172.16.218.190/ASM/user/' . $user_id;

    // Perform the redirect.
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri($redirect_url, [], ['absolute' => TRUE]));
}



//ANCHOR- Form Alter
//! Content type form alter 
function ncbs_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
    // Saving CT node id to user fields
    $form_field_mapping = [
        'node_basic_information_form' => 'field_basic_information_referenc',
        'node_academic_qualification_form' => 'field_academic_qualification_ref',
        'node_other_relevant_information_form' => 'field_other_relevant_info_ref',
        'node_update_publications_form' => 'field_update_publications_ref',
        'node_research_proposal_form' => 'field_research_proposal_ref',
        'node_list_of_referees__form' => 'field_list_of_referees_ref',

    ];

    // Checking which content type is selected
    if (
        $form_id == 'node_basic_information_form' ||
        $form_id == 'node_academic_qualification_form' ||
        $form_id == 'node_other_relevant_information_form' ||
        $form_id == 'node_update_publications_form' ||
        $form_id == 'node_research_proposal_form' ||
        $form_id == 'node_list_of_referees__form'
    ) {

        // Logic to be executed if the form is one of the specified types
        \Drupal::messenger()->addMessage("The form is one of the specified types.");

        // Example: Modify the form based on the mapping
        if (isset($form_field_mapping[$form_id])) {
            // Add a submission handler with an argument for the field name
            $form['actions']['submit']['#submit'][] = 'ncbs_handle_form_submission';
            $form['#field_name'] = $form_field_mapping[$form_id];
        }
    }
}

//ANCHOR - Universal submission handler for node forms.
//! retrieves the node ID and the field name from the form.
function ncbs_handle_form_submission(array &$form, FormStateInterface $form_state)
{
    $node = $form_state->getFormObject()->getEntity();
    $field_name = $form['#field_name'];
    ncbs_update_user_field($field_name, $node->id());
}

//ANCHOR - Helper function to update user fields
//! saving node ids to user forms 
function ncbs_update_user_field($field_name, $node_id)
{
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($user) {
        $user->set($field_name, ['target_id' => $node_id]);
        $user->save();
    }
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
function ncbs_entity_insert(Drupal\Core\Entity\EntityInterface $entity)
{
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

//ANCHOR - Submit Application Edit form
//! Adding Custom Validation before submitting
function ncbs_form_node_submit_application_edit_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
    if ($form_id == 'node_submit_application_edit_form') {
        // Attach custom validation to the form submission.
        $form['#validate'][] = 'ncbs_custom_validation'; // Custom Validation
        $form['revision']['#access'] = FALSE; // Hide revision option
        $form['body']['widget'][0]['value']['#default_value'] = 'Your Application is complete but not submitted';


          
        
        // Attach a custom access check to the form based on 'field_session_key'
        // $node = $form_state->getFormObject()->getEntity();
        // $form['#access'] = ncbs_create_access($node)->isAllowed();
    }
}

//ANCHOR - Custom validation for submit applicaiton

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Custom validation function.
 */
function ncbs_custom_validation(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($current_user->id());

    // Load the node object from the form state.
    $node = $form_state->getFormObject()->getEntity();

    $empty_fields = [];
    $fields = [
        'field_basic_information_referenc' => 'Basic Information',
        'field_academic_qualification_ref' => 'Academic Qualification',
        'field_list_of_referees_ref' => 'List of Referees',
        'field_other_relevant_info_ref' => 'Other Relevant Information',
        'field_research_proposal_ref' => 'Research Proposal',
        'field_update_publications_ref' => 'Update Publications'
    ];

    foreach ($fields as $field_name => $field_label) {
        $field_value = $user->get($field_name)->getValue();
        if (empty($field_value)) {
            $empty_fields[] = $field_name;
        }
    }

    // Additional check for field_list_of_referees_
    if (!empty($user->get('field_list_of_referees_ref')->getValue())) {
        $list_of_referees_nid = $user->get('field_list_of_referees_ref')->target_id;
        $list_of_referees_node = Node::load($list_of_referees_nid);
        if ($list_of_referees_node && !empty($list_of_referees_node->get('field_list_of_referees_')->getValue())) {
            $referees_paragraphs = $list_of_referees_node->get('field_list_of_referees_')->referencedEntities();
            $count_referees = count($referees_paragraphs);
            if ($count_referees < 8) {
                $remaining_count = 8 - $count_referees;
                $form_state->setErrorByName('field_list_of_referees_', t('Minimum 8 referees required. Count remaining: @count', ['@count' => $remaining_count]));
            }
        } else {
            $form_state->setErrorByName('field_list_of_referees_', t('Minimum 8 referees required. Count remaining: 8'));
        }
    } else {
        $form_state->setErrorByName('field_list_of_referees_ref', t('Minimum 8 referees required. Count remaining: 8'));
    }

    if (!empty($empty_fields)) {
        foreach ($empty_fields as $field_name) {
            $form_state->setErrorByName($field_name, t('@field_label has not been submitted.', ['@field_label' => $fields[$field_name]]));
        }
    } else {
        \Drupal::messenger()->addMessage(t('All fields successfully submitted.'));
        // Conditional field update upon successful form submission.
        if ($form_state->isSubmitted()) {
            // Generate an 8-digit random string
            $random_string = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(8 / strlen($x)))), 1, 8);

            // Save the generated string to the 'field_session_key' field
            $form_state->setValue(['field_session_key', 0, 'value'], $random_string);

            // Update the body field with custom text
            $form_state->setValue(['body', 0, 'value'], 'Application has been submitted successfully.');

            // Clear block caches
            \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_view']);

            \Drupal::messenger()->addMessage('Application submitted successfully.');
        } else {
            \Drupal::messenger()->addMessage('Submission failed.');
        }
    }
}









//ANCHOR - Custom check to verify user submit the Application
function check_submit_application(AccountInterface $account)
{
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'submit_application')
        ->condition('uid', $account->id())
        ->exists('field_session_key')
        ->accessCheck(FALSE); // Explicitly disable access checks for this query
    $nids = $query->execute();
    return !empty($nids);
}

//ANCHOR - Node access for edit and create
// function ncbs_node_access(NodeInterface $node, $op, AccountInterface $account)
// {
//     $restricted_types = [
//         'basic_information',
//         'academic_qualification',
//         'other_relevant_information',
//         'list_of_referees_',
//         'research_proposal',
//         'update_publications',
//         'submit_application'
//     ];

//     if (($op === 'update' || $op === 'create') && in_array($node->getType(), $restricted_types)) {
//         if (check_submit_application($account)) {
//             return AccessResult::forbidden()->addCacheableDependency($node);
//         }
//     }


//     // Default to neutral to allow other access checks to proceed
//     return AccessResult::neutral()->addCacheableDependency($node);
// }






function ncbs_cron()
{
    // Get the user role.
    $user_role = \Drupal::entityTypeManager()->getStorage('user_role')->load('user');

    // Check if the user role exists.
    if ($user_role) {
        // Get all users with the 'user' role.
        $query = \Drupal::entityQuery('user')
            ->condition('status', 1) // Only active users.
            ->condition('roles', $user_role->id());

        $user_ids = $query->execute();

        // Load user profiles.
        $users = User::loadMultiple($user_ids);

        // Print user profiles.
        foreach ($users as $user) {
            \Drupal::logger('ncbs')->notice('User ID: ' . $user->id() . ', Username: ' . $user->getAccountName());
        }
    } else {
        \Drupal::logger('ncbs')->notice('The "user" role does not exist.');
    }
}




/**
 * Implements hook_entity_update().
 */
function ncbs_entity_update(EntityInterface $entity) {
  // Check if the updated entity is of type 'node'.
  if ($entity->getEntityTypeId() === 'node') {
    // Check if the node type is 'submit_application'.
    if ($entity->bundle() === 'submit_application') {
      // Load the node to check the value of the field_session_key.
      $node = Node::load($entity->id());
      
      // Check if the field_session_key is not empty.
      if (!$node->get('field_session_key')->isEmpty()) {











        // Send a thank you mail to the current user.
        $user = \Drupal::currentUser();
        //$to = $user->getEmail();
        $to = 'rnandini@ncbs.res.in';
        $subject = 'Submission Confirmation';
        $message = 'Thank you for your submission.';
        $params = [
          'subject' => $subject,
          'message' => $message,
        ];
        
        // Send the email.
        $mailManager = \Drupal::service('plugin.manager.mail');
        $result = $mailManager->mail('ncbs', 'thank_you_mail', $to, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
        
        // Check if the email was sent successfully.
        if ($result['result'] !== true) {
          // Display error message if the email failed to send.
          \Drupal::messenger()->addError(t('Failed to send email. Please contact the site administrator.'));
        } else {
          // Display success message if the email was sent.
          \Drupal::messenger()->addMessage(t('Submission confirmation email sent successfully.'));
        }
      }
    }





}



}

/**
 * Implements hook_mail().
 */
function ncbs_mail($key, &$message, $params) {
  switch ($key) {
    case 'thank_you_mail':
      $message['subject'] = $params['subject'];
      $message['body'][] = $params['message'];
      break;
  }
}
