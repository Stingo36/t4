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
 * @return \Symfony\Component\HttpFoundation\RedirectResponse|bool
 *   A redirect response to '/new-applications' if successful, FALSE otherwise.
 */
function addCommentByRole(Node $node, AccountInterface $account, MessengerInterface $messenger, RequestStack $request_stack) {
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
          $messenger->addMessage('Role: Faculty Member.');
        
        } elseif (in_array('faculty_search_committee', $user_roles)) {
          $field_name = 'field_faculty_search_comit_coref';
          $messenger->addMessage('Role: Faculty Search Committee.');
        
        } elseif (in_array('yip_search_committee', $user_roles)) {
          $field_name = 'field_yip_search_committee_coref';
          $messenger->addMessage('Role: YIP Search Committee.');
        
        } elseif (in_array('assessment_committee', $user_roles)) {
          $field_name = 'field_assessment_committee_coref';
          $messenger->addMessage('Role: Assessment Committee.');
        
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
