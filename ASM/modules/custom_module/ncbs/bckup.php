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
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

// Include necessary files
module_load_include('inc', 'ncbs', 'includes/Validations/FormValidations');
module_load_include('inc', 'ncbs', 'includes/FormsAlter/UserFormAlter');
module_load_include('inc', 'ncbs', 'includes/FormsAlter/FormAlter');
module_load_include('inc', 'ncbs', 'includes/Comments/AddComments');
















/**
 * Implements hook_menu_local_tasks_alter().
 * !Hides the view tab for users with the "user" role.
 */
function ncbs_menu_local_tasks_alter(&$data, $route_name, $route_parameters) {
    $user = \Drupal::currentUser();

    if ($user->hasRole('user')) {
        foreach ($data['tabs'] as &$tabs) {
            foreach ($tabs as $key => $tab) {
                if ($tab['#link']['title'] == 'View') {
                    unset($tabs[$key]);
                }
            }
        }
    }
}

/**
 * Implements hook_user_login().
 * !Invalidates cache tags when a user logs in.
 */
function ncbs_user_login($account) {
    $tags = [
        'user:' . $account->id() . ':content',
    ];
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
    \Drupal::cache('render')->invalidateAll();
}

/**
 * Implements hook_node_access().
 * !Restricts access to certain node types for users who have already submitted an application.
 */
function ncbs_node_access(NodeInterface $node, $op, AccountInterface $account)
{
    $restricted_types = [
        'basic_information',
        'academic_qualification',
        'other_relevant_information',
        'list_of_referees_',
        'research_proposal',
        'update_publications',
        'submit_application'
    ];

    if (($op === 'update' || $op === 'create') && in_array($node->getType(), $restricted_types)) {
        if (check_submit_application($account)) {
            return AccessResult::forbidden()->addCacheableDependency($node);
        }
    }


    // Default to neutral to allow other access checks to proceed
    return AccessResult::neutral()->addCacheableDependency($node);
}

/**
 * !Custom validation function to check if a user has already submitted an application.
 */
function check_submit_application(AccountInterface $account) {
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'submit_application')
        ->condition('uid', $account->id())
        ->exists('field_session_key')
        ->accessCheck(FALSE);
    $nids = $query->execute();
    return !empty($nids);
}

/**
 * Implements hook_cron().
 * !Example function to demonstrate cron job functionality.
 */
// function ncbs_cron() {
//     // Get the user role.
//     $user_role = \Drupal::entityTypeManager()->getStorage('user_role')->load('user');

//     if ($user_role) {
//         // Get all users with the 'user' role.
//         $query = \Drupal::entityQuery('user')
//             ->condition('status', 1) // Only active users.
//             ->condition('roles', $user_role->id());

//         $user_ids = $query->execute();

//         // Load user profiles.
//         $users = User::loadMultiple($user_ids);

//         // Print user profiles.
//         foreach ($users as $user) {
//             \Drupal::logger('ncbs')->notice('User ID: ' . $user->id() . ', Username: ' . $user->getAccountName());
//         }
//     } else {
//         \Drupal::logger('ncbs')->notice('The "user" role does not exist.');
//     }
// }

















/**
 * Implements hook_entity_insert().
 * !function to handle actions after a node/content is created.
 */
function ncbs_entity_insert(Drupal\Core\Entity\EntityInterface $entity)
{


    // Get the current user and messenger servi
    if ($entity->bundle() === 'add_comments') {
        $current_user = \Drupal::currentUser();
        $messenger = \Drupal::messenger();
        // Assuming $entity, $current_user, and $messenger are already defined.

        // Get the RequestStack service.
        $request_stack = \Drupal::service('request_stack');

        // Call the AddCommentByRole function with all 4 required arguments.
        AddCommentByRole($entity, $current_user, $messenger, $request_stack);

    }
    //!  TO SEND EMAIL
    elseif ($entity->bundle() === 'send_emails') {
        // Get the current request to extract the URL parameters
        $request = \Drupal::request();
        
        // Get 'nid' and 'session' from the URL
        $nid = $request->query->get('nid');
        $session = $request->query->get('session');
    
        // Get the field values from the entity
        $email_body = $entity->get('field_email_body')->value;
        $email_subject = $entity->get('field_subject')->value; // Assuming field_subject exists
    
        // Get the current user email
        $current_user = \Drupal::currentUser();
        $from_email = $current_user->getEmail();
    
        // Initialize a default updated email body
        $updated_email_body = $email_body;
    
        // Load the node by nid to get the title (for [[CANDIDATE_NAME]] replacement)
        if ($nid) {
            $node = \Drupal\node\Entity\Node::load($nid);
            
            if ($node instanceof \Drupal\node\NodeInterface) {
                // Get the title of the node (will replace [[CANDIDATE_NAME]])
                $title = $node->getTitle();
    
                // Replace [[CANDIDATE_NAME]] in email body with the node title
                $updated_email_body = str_replace('[[CANDIDATE_NAME]]', $title, $email_body);
            }
            else {
                \Drupal::messenger()->addError(t('No node found with nid: @nid', ['@nid' => $nid]));
            }
        }
        else {
            \Drupal::messenger()->addError(t('No nid parameter provided in the URL.'));
        }
    
        // Check if the selected value in field_send_email_ is 'Dean'
        if ($entity->get('field_send_email_')->value === 'Dean') {
            // Get user IDs from the field_to_dean (assuming it's a reference field)
            $user_ids = $entity->get('field_to_dean')->getValue();
            
            // Loop through each user ID and load the user entity to get the email and username
            foreach ($user_ids as $user_id) {
                $user = \Drupal\user\Entity\User::load($user_id['target_id']);
                if ($user) {
                    $email = $user->getEmail();  // Get the user email
                    $username = $user->getDisplayName(); // Get the user username
    
                    // Replace [[RECEIVER_KEY]] with the username in the email body
                    $personalized_email_body = str_replace('[[RECEIVER_KEY]]', $username, $updated_email_body);
    
                    // Save the updated email body with both [[CANDIDATE_NAME]] and [[RECEIVER_KEY]] replaced in the entity
                    $entity->set('field_email_body', $personalized_email_body);
                    $entity->save(); // Save the updated entity to reflect changes
    
                    // Define mail parameters for this specific user
                    $mailManager = \Drupal::service('plugin.manager.mail');
                    $module = 'ncbs'; // Replace with your module's machine name
                    $key = 'send_email'; // The key used to identify the mail (can be custom)
                    $params['subject'] = $email_subject;
                    $params['body'] = $personalized_email_body;
                    $langcode = $current_user->getPreferredLangcode();
    
                    // Send the mail
                    $result = $mailManager->mail($module, $key, $email, $langcode, $params, $from_email, TRUE);
    
                    // Check if the email was sent successfully for this recipient
                    if ($result['result'] === TRUE) {
                        \Drupal::messenger()->addMessage(t('The email has been sent to @to.', ['@to' => $email]));
                    }
                    else {
                        \Drupal::messenger()->addError(t('There was a problem sending the email to @to.', ['@to' => $email]));
                    }
                }
            }
        }
        else {
            // If the value is not Dean, send email to the default specified recipient
            $to = 'likithams@ncbs.res.in';
            $personalized_email_body = str_replace('[[RECEIVER_KEY]]', 'Recipient', $updated_email_body); // Use a generic replacement if no user-specific receiver
    
            // Save the updated email body with generic replacement in the entity
            $entity->set('field_email_body', $personalized_email_body);
            $entity->save(); // Save the updated entity to reflect changes
    
            // Define mail parameters
            $mailManager = \Drupal::service('plugin.manager.mail');
            $module = 'ncbs'; // Replace with your module's machine name
            $key = 'send_email'; // The key used to identify the mail (can be custom)
            $params['subject'] = $email_subject;
            $params['body'] = $personalized_email_body;
            $langcode = $current_user->getPreferredLangcode();
            
            // Send the mail
            $result = $mailManager->mail($module, $key, $to, $langcode, $params, $from_email, TRUE);
    
            // Check if the email was sent successfully
            if ($result['result'] === TRUE) {
                \Drupal::messenger()->addMessage(t('The email has been sent to @to.', ['@to' => $to]));
            }
            else {
                \Drupal::messenger()->addError(t('There was a problem sending the email.'));
            }
        }
    }

    
    
    
    



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

/**
 * Implements hook_entity_update().
 * !function to handle actions after a node/content is updated.
 */

function ncbs_entity_update(EntityInterface $entity)
{

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
 * !function to define email templates.
 */
function ncbs_mail($key, &$message, $params) {
    switch ($key) {
        case 'thank_you_mail':
            $message['subject'] = $params['subject'];
            $message['body'][] = $params['message'];
            break;
        case 'mail':
            $message['to'] = $params['to'];
            $message['subject'] = $params['subject'];
            $message['body'][] = $params['body'];
            $message['headers'] = [
                'Content-Type' => 'text/html; charset=UTF-8; format=flowed',
                'MIME-Version' => '1.0',
            ];
        break;
        case 'send_email':
            $message['subject'] = $params['subject'];
            $message['body'][] = $params['message'];
            // Add CC recipients if they exist
            if (!empty($params['cc'])) {
                $message['headers']['Cc'] = $params['cc'];
            }
            break;
    }
}



// function ncbs_cron() {
//     // Add a message to the Drupal messenger.
//     \Drupal::messenger()->addMessage("CRON T1");

//     // Query to get user IDs with the role 'user' and active status
//     $user_ids = \Drupal::entityQuery('user')
//         ->condition('status', 1) // Optionally, you can filter users by status (active)
//         ->accessCheck(FALSE)
//         ->condition('roles', 'user')
//         ->execute();

//     // Load each user entity and perform the required operations
//     foreach ($user_ids as $uid) {
//         $user = \Drupal\user\Entity\User::load($uid);

//         if ($user) {
//             // Add a message to the Drupal messenger.
//             \Drupal::messenger()->addMessage("CRON T2");
//             // Get the username
//             $username = $user->getAccountName();
                
//             // Add the username to the Drupal messenger for demonstration purposes
//             \Drupal::messenger()->addMessage("Username: " . $username);

//             // Check if the field_user_session_key field exists and get its value
//             $user_session_key_value = $user->hasField('field_user_session_key') ? $user->get('field_user_session_key')->value : NULL;

//             // Check if the field_user_submit_app_ref field exists
//             if ($user->hasField('field_user_submit_app_ref')) {
//                 $submit_app_ref = $user->get('field_user_submit_app_ref')->target_id;
                
//                 if ($submit_app_ref) {
//                     // Load the referenced node
//                     $node = \Drupal\node\Entity\Node::load($submit_app_ref);
                    
//                     if ($node && $node->hasField('field_session_key')) {
//                         $node_session_key_value = $node->get('field_session_key')->value;

//                         // Compare the session key values if both are not empty
//                         if (!empty($user_session_key_value) && !empty($node_session_key_value)) {
//                             if ($user_session_key_value === $node_session_key_value) {
//                                 \Drupal::messenger()->addMessage("User $username profile remains active.");
//                             } else {
//                                 \Drupal::messenger()->addMessage("User $username session key mismatch; no action taken.");
//                             }
//                         } else {
//                             if (empty($user_session_key_value) && empty($node_session_key_value)) {
//                                 // Block the user account
//                                 $user->block();
//                                 $user->save();
//                                 \Drupal::messenger()->addMessage("User $username account has been blocked due to empty session keys.");
//                             } else {
//                                 \Drupal::messenger()->addMessage("User $username has an empty session key; no action taken.");
//                             }
//                         }
//                     } else {
//                         \Drupal::messenger()->addMessage("field_session_key field not found in the referenced node for user: " . $username);
//                     }
//                 } else {
//                     \Drupal::messenger()->addMessage("No node reference found in field_user_submit_app_ref for user: " . $username);
//                 }
//             } else {
//                 \Drupal::messenger()->addMessage("field_user_submit_app_ref field not found for user: " . $username);
//             }
//         }
//     }
// }



function ncbs_cron() {
    // Add a message to the Drupal messenger.
    \Drupal::messenger()->addMessage("CRON T1");

    // Query to get user IDs with the role 'user' and active status
    $user_ids = \Drupal::entityQuery('user')
        ->condition('status', 1) // Optionally, you can filter users by status (active)
        ->accessCheck(FALSE)
        ->condition('roles', 'user')
        ->execute();

    // Load each user entity and perform the required operations
    foreach ($user_ids as $uid) {
        $user = \Drupal\user\Entity\User::load($uid);

        if ($user) {
            // Add a message to the Drupal messenger.
            \Drupal::messenger()->addMessage("CRON T2");
            // Get the username
            $username = $user->getAccountName();
            // Get the created timestamp
            $created = $user->getCreatedTime();
            // Convert timestamp to a readable date format
            $created_date = date('d-m-Y H:i:s', $created);
            // Add a message with the created date
            \Drupal::messenger()->addMessage("User $username was created on $created_date");

            // Calculate 15 days from the creation date
            $expire_date = strtotime("+15 days", $created);

            // Check if the field is filled within 15 days
            if (empty($user->get('field_user_session_key')->getValue()) && time() > $expire_date) {
                // Calculate remaining days
                $remaining_days = ceil(($expire_date - time()) / (60 * 60 * 24));
                // Block the user account
                $user->block();
                $user->save();
                \Drupal::messenger()->addMessage("User $username's account has been blocked due to inactivity. Reason: Application not submitted.");
            } elseif (empty($user->get('field_user_session_key')->getValue()) && time() < $expire_date) {
                $remaining_days = ceil(($expire_date - time()) / (60 * 60 * 24));
                \Drupal::messenger()->addMessage("User $username's account is active. Please submit the complete application within $remaining_days days.");
            } else {
                // Field is not empty, account remains active
                \Drupal::messenger()->addMessage("Thanks, $username, for submitting the complete application.");
            }
        }
    }
}


// ncbs.module
/**
 * Implements hook_block_info().
 */
function ncbs_block_info() {
    $blocks['ncbs_block'] = array(
      'info' => t('NCBS Block'),
    );
    return $blocks;
  }
  



  

  /**
   * Implements hook_ENTITY_TYPE_create_access() for nodes.
   */

  function ncbs_entity_create_access(AccountInterface $account, array $context, $entity_bundle) {
    // First check if the content type 'add_comments' exists.
    if (NodeType::load('add_comments')) {
      \Drupal::messenger()->addMessage('Content type "add_comments" exists.');
  
      // Get the current request.
      $request = \Drupal::request();
  
      // Extract the node ID (nid) and session from the URL.
      $nid = $request->query->get('nid');
      $session = $request->query->get('session');
  
      if ($nid) {
        // Load the node by nid.
        $node = Node::load($nid);
  
        if ($node) {
          // Get the node ID from the loaded node.
          $node_id = $node->id();
  
          // Check if the node's content type is 'submit_application'.
          if ($node->bundle() == 'submit_application') {
            // Content type is 'submit_application'.
            \Drupal::messenger()->addMessage('Node with nid ' . $nid . ' is of content type "submit_application".');
  
            // Check if the node has the field 'field_session_key' and if it is not empty.
            if ($node->hasField('field_session_key') && !$node->get('field_session_key')->isEmpty()) {
              $session_value = $node->get('field_session_key')->value;
              \Drupal::messenger()->addMessage('Session value: ' . $session_value);
  
              // Check if session matches session_value and node_id matches nid.
              if ($session == $session_value && $node_id == $nid) {
                \Drupal::messenger()->addMessage('Access allowed: Session and node ID match.');
  
                // Get and display user roles.
                $user_roles = $account->getRoles();
                \Drupal::messenger()->addMessage('User roles: ' . implode(', ', $user_roles));
  
                // Return access result based on your specific requirements.
                return AccessResult::allowed();
              } else {
                \Drupal::messenger()->addMessage('Access denied: Session or node ID does not match.');
                // Display user roles even if access is denied.
                $user_roles = $account->getRoles();
                \Drupal::messenger()->addMessage('User roles: ' . implode(', ', $user_roles));
  
                // Return access result based on your specific requirements.
                return AccessResult::forbidden();
              }
            }
          }
        }
      }
    }
  }
  

/**
 * Implements hook_form_alter().
 */
function ncbs_form_node_add_comments_edit_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
    // Check if 'field_add_comments' is present in the form.
    if (isset($form['field_add_comments'])) {
      \Drupal::messenger()->addMessage('field_add_comments is present in the form.');
  
      // Loop through each item in 'field_add_comments'.
      foreach ($form['field_add_comments']['widget'] as $key => &$comment_item) {
        // Check if this is a comment item and not the add more button or other elements.
        if (is_numeric($key)) {
          \Drupal::messenger()->addMessage("Processing item with key: $key");
  
          // Check if the text area has a value.
          if (!empty($comment_item['value']['#default_value'])) {
            \Drupal::messenger()->addMessage("Found value for item with key: $key, making it read-only.");
  
            // Make the text area read-only.
            $comment_item['value']['#attributes']['readonly'] = 'readonly';
  
            // Add inline CSS to change the color to grey.
            $comment_item['value']['#attributes']['style'] = 'background-color: #f0f0f0; color: #888;';
          } else {
            \Drupal::messenger()->addMessage("No value found for item with key: $key.");
          }
        }
      }
    } else {
      \Drupal::messenger()->addMessage('field_add_comments is NOT present in the form.');
    }
  
    // Hide the revision options on the edit form.
    if (isset($form['revision_information'])) {
      \Drupal::messenger()->addMessage('Hiding the revision information section.');
      $form['revision_information']['#access'] = FALSE;
    }
  
    // Add custom submit handler to redirect after form submission.
    $form['actions']['submit']['#submit'][] = 'ncbs_custom_redirect_after_comment_submit';
}
  
  /**
   * Custom submit handler to redirect after form submission.
   */
function ncbs_custom_redirect_after_comment_submit(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Redirect to the desired path after form submission.
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUserInput('/new-applications'));
}
  

//! TRYING EMAIL SEND 

// /**
//  * Implements hook_node_presave().
//  */
// function ncbs_node_presave(NodeInterface $node) {
//   // Check if the content type is 'send_emails'.
//   if ($node->getType() === 'send_emails') {
//     // Get the user reference field values.
//     $field_to_dean = $node->get('field_to_dean')->referencedEntities();
//     $email_ids = [];

//     // Loop through referenced users and gather email addresses.
//     foreach ($field_to_dean as $user) {
//       $email = $user->getEmail();
      
//       // Ensure the email is not already processed to avoid duplicates.
//       if (!in_array($email, $email_ids)) {
//         $email_ids[] = $email;
//       }
//     }

//     // Now, we handle multiple email addresses by creating separate nodes.
//     foreach ($email_ids as $email) {
//       // Query existing nodes to check for duplicates based on email.
//       $existing_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
//         'type' => 'send_emails',
//         'field_to_dean' => $email,
//       ]);

//       // If no duplicate exists, create a new node for each email.
//       if (empty($existing_nodes)) {
//         // $new_node = Node::create([
//         //   'type' => 'send_emails',
//         //   'title' => 'Email to ' . $email,
//         //   'field_to_dean' => [['target_id' => $user->id()]], // Only set this email's user reference
//         // ]);
//         // $new_node->save();

//         // Send the email for this user.
//         $mail_manager = \Drupal::service('plugin.manager.mail');
//         $module = 'ncbs';
//         $key = 'send_email'; // Email template key (defined in hook_mail).
//         $to = $email;
//         $from = 'likithams@ncbs.res.in';
//         $params = [];
//         $params['message'] = 'This is a custom email message to ' . $email;
//         $langcode = \Drupal::currentUser()->getPreferredLangcode();
//         $send = true;

//         $result = $mail_manager->mail($module, $key, $to, $langcode, $params, $from, $send);

//         if ($result['result'] !== true) {
//           \Drupal::logger('ncbs')->error('There was a problem sending the email to @email', ['@email' => $email]);
//         } else {
//           \Drupal::logger('ncbs')->notice('Email sent to @email', ['@email' => $email]);
//         }
//       }
//     }

//     // Prevent the original node from being saved since we're creating new nodes.
//     //s$node->delete();  // Delete the original node as we're replacing it with new nodes.
//   }
// }




function ncbs_form_node_send_emails_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)
{

    // Check if the form is for the content type 'send_emails'.
    if ($form_id == 'node_send_emails_form') {
        // Remove the default submit handler.
        unset($form['actions']['submit']['#submit']);

        // Add our custom submit handler.
        $form['actions']['submit']['#submit'][] = 'ncbs_custom_send_emails_submit';
    }
}





use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Custom submit handler for the send_emails content type.
 */
function ncbs_custom_send_emails_submit(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Retrieve form values
    $values = $form_state->getValues();
    $title = $values['title'][0]['value'] ?? NULL;
    $subject = $values['field_subject'][0]['value'] ?? NULL;
    $email_body = $values['field_email_body'][0]['value'] ?? NULL;
    
    // Handle 'field_cc' as unlimited field and convert to a comma-separated string
    $cc_field_values = array_column($values['field_cc'] ?? [], 'value');
    $cc_field = !empty($cc_field_values) ? implode(',', $cc_field_values) : NULL;

    $field_send_email = array_column($values['field_send_email_'] ?? [], 'value');

    $current_user = \Drupal::currentUser();
    $current_node_id = \Drupal::request()->query->get('nid');

    // Load the current node to get the candidate information
    $current_node = \Drupal\node\Entity\Node::load($current_node_id);
    $candidate_name = $current_node ? $current_node->getOwner()->getAccountName() : 'Unknown';

    // Gather selected users from the relevant fields
    $selected_users = [
        'field_to_dean' => $values['field_to_dean'] ?? [],
        'field_to_director' => $values['field_to_director'] ?? [],
        'field_to_board' => $values['field_to_board'] ?? [],
    ];

    $created_node_ids = [];

    // Process each selected user for email and node creation
    foreach ($selected_users as $field => $users) {
        foreach ($users as $user) {
            if ($user_entity = \Drupal\user\Entity\User::load($user['target_id'])) {
                $recipient_email = $user_entity->getEmail();
                $receiver_username = $user_entity->getAccountName();

                // Replace placeholders in the email body
                $personalized_email_body = str_replace(
                    ['[[CANDIDATE_NAME]]', '[[RECEIVER_KEY]]'],
                    [$candidate_name, $receiver_username],
                    $email_body
                );

                // Create a new node for the selected user
                $new_node = \Drupal\node\Entity\Node::create([
                    'type' => 'send_emails',
                    'title' => $title,
                    'uid' => $user_entity->id(),
                    $field => [$user_entity->id()],
                    'field_subject' => $subject,
                    'field_email_body' => [
                        'value' => $personalized_email_body,
                        'format' => 'full_html',  // Ensure Full HTML is used for the email body
                    ],
                    'field_sender_email_id' => $recipient_email,
                    'field_cc' => $cc_field,  // Save the comma-separated cc values in the node
                    'field_send_email_' => $field_send_email,
                    'field_candidate_reference' => $current_node_id,
                ]);

                // Default status is "Failed"
                $status = 'Failed';

                // Send email
                if ($recipient_email) {
                    $mail_manager = \Drupal::service('plugin.manager.mail');
                
                    // Pass the cc_field as part of the params array
                    $params = [
                        'subject' => $subject,
                        'message' => $personalized_email_body,
                        'cc' => $cc_field  // Ensure cc is passed properly
                    ];
                
                    // Send the email
                    $result = $mail_manager->mail('ncbs', 'send_email', $recipient_email, $current_user->getPreferredLangcode(), $params, NULL, TRUE);
                
                    // Check if the email was sent successfully
                    if ($result['result']) {
                        $status = 'Sent'; // Update status if email sent successfully
                    } else {
                        $status = 'Failed';
                    }
                }
                

                // Update node status and current user login details
                $new_node->set('field_email_sta', $status)
                    ->set('field_current_user_login', $current_user->getAccountName())
                    ->set('field_email_sent_time', (new DrupalDateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
                    ->save();

                // Collect the created node ID
                $created_node_ids[] = $new_node->id();
            }
        }
    }

    // Update the reference field in the current node if new nodes were created
    if (!empty($created_node_ids) && $current_node) {
        $existing_references = $current_node->get('field_send_email_reference')->getValue();
        foreach ($created_node_ids as $nid) {
            $existing_references[] = ['target_id' => $nid];
        }
        $current_node->set('field_send_email_reference', $existing_references)->save();
    }


    // Redirect to '/new-applications' after processing
    $form_state->setRedirectUrl(Url::fromUserInput('/new-applications'));

}
