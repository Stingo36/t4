<?php

use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds a reference to the node in the appropriate comment reference field.
 * Sets 'field_comment_date' to the current date and time, and 'field_comment_name' to the author's name.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node to be referenced.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The current user account.
 * @param \Drupal\Core\Messenger\MessengerInterface $messenger
 *   The messenger service.
 * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
 *   The request stack service.
 *
 * @return bool
 *   TRUE if the operation was successful, FALSE otherwise.
 */
function AddCommentByRole(Node $node, AccountInterface $account, MessengerInterface $messenger, RequestStack $request_stack) {
  // Check if the node's content type is 'add_comments'.
  if ($node->bundle() == 'add_comments') {
    $messenger->addMessage('Content type is "add_comments".');

    // Get the current request to extract the nid from the URL.
    $request = $request_stack->getCurrentRequest();
    $referenced_nid = $request->query->get('nid');

    if ($referenced_nid) {
      // Load the referenced node by nid.
      $referenced_node = Node::load($referenced_nid);

      if ($referenced_node) {
        // Get the user's roles and log them for debugging.
        $user_roles = $account->getRoles();
        $messenger->addMessage('User Roles: ' . implode(', ', $user_roles));

        // Determine the field based on the user's role.
        $field_name = '';
        if (in_array('board_member', $user_roles)) {
          $field_name = 'field_board_member_comment_refer';
          $messenger->addMessage('Role: Board Member.');

        } elseif (in_array('administrator', $user_roles)) {
          $field_name = 'field_admin_comment_reference';
          $messenger->addMessage('Role: Administrator.');
        
        } elseif (in_array('admin', $user_roles)) {
          $field_name = 'field_admin_comment_reference';
          $messenger->addMessage('Role: Admin.');
        
        } elseif (in_array('dean', $user_roles)) {
          $field_name = 'field_dean_comment_reference';
          $messenger->addMessage('Role: Dean.');
        
        } elseif (in_array('director', $user_roles)) {
          $field_name = 'field_director_comment_reference';
          $messenger->addMessage('Role: Director.');
        
        } elseif (in_array('faculty_member', $user_roles)) {
          $field_name = 'field_faculty_member_comment_ref';
          $messenger->addMessage('Role: faculty member.');
        
        } elseif (in_array('faculty_search_committee', $user_roles)) {
          $field_name = 'field_faculty_search_comit_coref';
          $messenger->addMessage('Role: faculty search committee.');
        
        } elseif (in_array('yip_search_committee', $user_roles)) {
          $field_name = 'field_yip_search_committee_coref';
          $messenger->addMessage('Role: yip search committee.');
        
        } elseif (in_array('assessment_committee', $user_roles)) {
          $field_name = 'field_assessment_committee_coref';
          $messenger->addMessage('Role: assessment committee.');
        
        } elseif (in_array('promotion_committee', $user_roles)) {
          $field_name = 'field_promotion_committee_coref';
          $messenger->addMessage('Role: Promotion Committee.');
        
        } else {
          $messenger->addMessage('Current user does not have a recognized role for commenting.');
        }

        if ($field_name) {
          // Add the current node as a reference in the appropriate field.
          $referenced_field = $referenced_node->get($field_name);
          $referenced_field->appendItem($node->id());
          $referenced_node->save();

          $node->save();

          $messenger->addMessage('Node reference saved in "' . $field_name . '".');
          $messenger->addMessage('Comment date and author name saved.');
          return TRUE;
        } else {
          // If no recognized role is found, return FALSE.
          return FALSE;
        }
      } else {
        $messenger->addMessage('Referenced node with nid ' . $referenced_nid . ' does not exist.');
        return FALSE;
      }
    } else {
      $messenger->addMessage('No node ID (nid) provided in the URL.');
      return FALSE;
    }
  } else {
    $messenger->addMessage('Content type is not "add_comments".');
    return FALSE;
  }
}



/**
 * Form alter hook for the 'add_comments' node edit form.
 * This alters the form to make previously filled comment fields read-only and hides the revision options.
 * Additionally, a custom submit handler is added to handle redirection after form submission.
 */
function ncbs_form_node_add_comments_edit_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Check if 'field_add_comments' exists in the form.
  if (isset($form['field_add_comments'])) {
      // Loop through each comment item in the 'field_add_comments' field.
      foreach ($form['field_add_comments']['widget'] as $key => &$comment_item) {
          // Ensure we are working with actual comment items (skip 'add more' buttons or other elements).
          if (is_numeric($key)) {
              // If the text area has a value, make it read-only.
              if (!empty($comment_item['value']['#default_value'])) {
                  // Set the text area to be read-only.
                  $comment_item['value']['#attributes']['readonly'] = 'readonly';

                  // Apply inline CSS to change the background color and text color.
                  $comment_item['value']['#attributes']['style'] = 'background-color: #f0f0f0; color: #888;';
              }
          }
      }
  }

  // Hide the revision options section on the edit form.
  if (isset($form['revision_information'])) {
      $form['revision_information']['#access'] = FALSE;
  }

  // Invalidate all render caches.
  \Drupal::cache('render')->invalidateAll();

  // Add a custom submit handler to handle redirection after form submission.
  $form['actions']['submit']['#submit'][] = 'ncbs_custom_redirect_after_comment_submit';
}

/**
* Form alter hook for the 'add_comments' node creation form.
* Adds a custom submit handler to handle redirection after form submission.
*/
function ncbs_form_node_add_comments_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Add a custom submit handler to handle redirection after form submission.
  $form['actions']['submit']['#submit'][] = 'ncbs_custom_redirect_after_comment_submit';
}

/**
* Custom submit handler to handle redirection after form submission.
* It updates the 'field_comment_date' field with the current date and invalidates the node cache.
*/
function ncbs_custom_redirect_after_comment_submit(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Load the node entity from the form state.
  $node = $form_state->getFormObject()->getEntity();

  // Check if the entity is of type 'add_comments'.
  if ($node->bundle() === 'add_comments') {
      // Invalidate the cache for this node.
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:' . $node->id()]);

      // Get the current date in 'Y-m-d H:i' format.
      $current_date = \Drupal::service('date.formatter')->format(time(), 'custom', 'Y-m-d H:i');

      // Check if the node has the 'field_comment_date' field.
      if ($node->hasField('field_comment_date')) {
          // Get the existing values of the 'field_comment_date' field.
          $current_date_field = $node->get('field_comment_date')->getValue();

          // Append the new current date value.
          $current_date_field[] = ['value' => $current_date];

          // Set the updated values back to the 'field_comment_date' field.
          $node->set('field_comment_date', $current_date_field);

          // Save the updated node entity.
          $node->save();

          // Display a success message to the user.
          \Drupal::messenger()->addStatus(t('The comment date has been successfully updated to @date.', ['@date' => $current_date]));
      } else {
          // Display an error message if the field does not exist.
          \Drupal::messenger()->addError(t('Field comment date does not exist on this entity.'));
      }
  }

  // Redirect the user to the desired path after form submission.
  $form_state->setRedirectUrl(\Drupal\Core\Url::fromUserInput('/new-applications'));
}
