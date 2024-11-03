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
class NcbsCustomBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  /* ----------------- //! To display CT links in custom block ---------------- */
  // public function build()
  // {
  //   $user = \Drupal::currentUser();
  //   $uid = $user->id();
  //   $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');

  //   // Content type definitions
  //   $content_types = [
  //     'basic_information' => 'Add Basic Information',
  //     'academic_qualification' => 'Add Academic Qualification',
  //     'other_relevant_information' => 'Add Other Relevant Information',
  //     'list_of_referees_' => 'Add List of Referees',
  //     'update_publications' => 'Add Update Publications'
  //   ];

  //   $build = [
  //     '#theme' => 'item_list',
  //     '#items' => [],
  //     '#cache' => [
  //       'tags' => [],
  //       'contexts' => ['user']
  //     ],
  //   ];

  //   // Loop through content types and build the appropriate links or messages
  //   foreach ($content_types as $type => $label) {
  //     $query = $entity_type_manager->getQuery()
  //       ->condition('type', $type)
  //       ->condition('uid', $uid)
  //       ->accessCheck(FALSE)
  //       ->range(0, 1);

  //     $exists = $query->execute();
  //     $link_path = "/node/add/{$type}";

  //     // Remove "Add " from the label using str_replace
  //     $submission_label = str_replace('Add ', '', $label);

  //     if (!empty($exists)) {
  //       $build['#items'][] = $this->t('Submitted ' . $submission_label);
  //     } else {
  //       $url = Url::fromRoute('node.add', ['node_type' => $type]);
  //       $link = Link::fromTextAndUrl($this->t($label), $url)->toString();
  //       $build['#items'][] = $link;
  //     }

  //     $build['#cache']['tags'][] = 'user:' . $uid . ':' . $type . '_node';
  //   }

  //   return $build;
  // }

  /* ----------------------------- //!TEST FUNCTION ---------------------------- */


  public function build()
  {
      $user = \Drupal::currentUser();
      $uid = $user->id();
      $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');
  
      // Content type definitions
      $content_types = [
          'basic_information' => 'Basic Information',
          'academic_qualification' => 'Academic Qualification',
          'other_relevant_information' => 'Other Relevant Information',
          'list_of_referees_' => 'List of Referees',
          'update_publications' => 'Update Publications',
          'research_proposal' => 'Research Proposal',
          'submit_application' => 'Submit Application'
      ];
  
      $build = [
          '#theme' => 'item_list',
          '#items' => [],
          '#cache' => [
              'tags' => [],
              'contexts' => ['user']
          ],
      ];
  
      // Check if the application has been submitted
      $submitted = false;
      $query = $entity_type_manager->getQuery()
          ->condition('type', 'submit_application')
          ->condition('uid', $uid)
          ->accessCheck(FALSE)
          ->range(0, 1);
      $nodes = $query->execute();
      $node_id = !empty($nodes) ? reset($nodes) : NULL;
  
      if ($node_id) {
          $node = $entity_type_manager->load($node_id);
          if ($node->get('field_session_key')->value) {
              $submitted = true;
              $build['#items'][] = ['#markup' => $this->t('APPLICATION SUBMITTED')];
          }
      }
  
      if (!$submitted) {
          foreach ($content_types as $type => $label) {
              $query = $entity_type_manager->getQuery()
                  ->condition('type', $type)
                  ->condition('uid', $uid)
                  ->accessCheck(FALSE)
                  ->range(0, 1);
              $nodes = $query->execute();
              $node_id = !empty($nodes) ? reset($nodes) : NULL;
  
              if ($node_id) {
                  $url_edit = Url::fromRoute('entity.node.edit_form', ['node' => $node_id]);
                  // Modify the label for submit application type
                  if ($type === 'submit_application') {
                      $edit_link = Link::fromTextAndUrl($label, $url_edit); // Remove 'Edit: ' prefix
                  } else {
                      $edit_link = Link::fromTextAndUrl($this->t('Edit: ' . $label), $url_edit);
                  }
                  $build['#items'][] = ['#markup' => $edit_link->toString()];
              } else {
                  $url_add = Url::fromRoute('node.add', ['node_type' => $type]);
                  $add_link = Link::fromTextAndUrl($this->t('Add: ' . $label), $url_add);
                  $build['#items'][] = ['#markup' => $add_link->toString()];
              }
  
              $build['#cache']['tags'][] = 'user:' . $uid . ':' . $type . '_node';
          }
      }
  
      return $build;
  }
  



  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account)
  {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }
}
