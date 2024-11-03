<?php 
namespace Drupal\ncbs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'User Content Status' Block.
 *
 * @Block(
 *   id = "user_content_status_block",
 *   admin_label = @Translation("User Content Status Block"),
 *   category = @Translation("Custom")
 * )
 */
class NcbsCustomBlock extends BlockBase {

  /**
   * Builds the block content with links to add/edit content types.
   * 
   * This method creates a list of content types and generates links
   * for adding or editing nodes, depending on the user's status.
   */
  public function build() {
    $user = \Drupal::currentUser();
    $uid = $user->id();
    $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');

    // Content type definitions with their respective labels.
    $content_types = [
      'basic_information' => 'Basic Information',
      'academic_qualification' => 'Academic Qualification',
      'other_relevant_information' => 'Other Relevant Information',
      'list_of_referees_' => 'List of Referees',
      'update_publications' => 'Update Publications',
      'research_proposal' => 'Research Proposal',
      'submit_application' => 'Submit Application'
    ];

    // Initialize the build array for displaying items.
    $build = [
      '#theme' => 'item_list',
      '#items' => [],
      '#cache' => [
        'tags' => [],
        'contexts' => ['user']
      ],
    ];

    // Loop through content types and display add/edit links based on existing nodes.
    foreach ($content_types as $type => $label) {
      // Query to check if the user has already created a node of this type.
      $query = $entity_type_manager->getQuery()
        ->condition('type', $type) // Match the content type.
        ->condition('uid', $uid)   // Match the user's ID.
        ->accessCheck(FALSE)       // Disable access check for this query.
        ->range(0, 1);             // Limit to one result.
      
      $nodes = $query->execute();
      $node_id = !empty($nodes) ? reset($nodes) : NULL;

      // If a node exists, create an edit link.
      if ($node_id) {
        $url_edit = Url::fromRoute('entity.node.edit_form', ['node' => $node_id]);
        if ($type === 'submit_application') {
          $edit_link = Link::fromTextAndUrl($label, $url_edit); // No 'Edit: ' prefix for submit_application
        } else {
          $edit_link = Link::fromTextAndUrl($this->t('Edit: ' . $label), $url_edit);
        }
        $build['#items'][] = ['#markup' => $edit_link->toString()];

      // If no node exists, create an add link.
      } else {
        $url_add = Url::fromRoute('node.add', ['node_type' => $type]);
        $add_link = Link::fromTextAndUrl($this->t('Add: ' . $label), $url_add);
        $build['#items'][] = ['#markup' => $add_link->toString()];
      }

      // Add cache tags based on the user and content type.
      $build['#cache']['tags'][] = 'user:' . $uid . ':' . $type . '_node';
    }

    return $build;
  }

  /**
   * Checks access to the block.
   * 
   * This method determines whether the user is allowed to see the block.
   * If the user has already submitted an application, they are denied access.
   */
  public function blockAccess(AccountInterface $account) {
    $uid = $account->id();
    $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');

    // Query to check if the user has submitted an application.
    $query = $entity_type_manager->getQuery()
      ->condition('type', 'submit_application') // Check for the 'submit_application' content type.
      ->condition('uid', $uid)                  // Match the user's ID.
      ->accessCheck(FALSE)                      // Disable access check for this query.
      ->range(0, 1);                            // Limit to one result.
    
    $nodes = $query->execute();
    $node_id = !empty($nodes) ? reset($nodes) : NULL;

    // If the user has submitted an application, deny access to the block.
    if ($node_id) {
      $node = $entity_type_manager->load($node_id);
      if ($node && $node->get('field_session_key')->value) {
        return AccessResult::forbidden();
      }
    }

    // Allow access if the application hasn't been submitted.
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
