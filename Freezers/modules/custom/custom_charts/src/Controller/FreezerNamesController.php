<?php 
namespace Drupal\custom_charts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Messenger\MessengerInterface;
use PhpParser\Node\Stmt\ElseIf_;

class FreezerNamesController extends ControllerBase {

  public function getFreezerNames() {
    $floor = \Drupal::request()->query->get('floor');
    $location = \Drupal::request()->query->get('location');
    $data = [];

    // \Drupal::messenger()->addMessage('getFreezerNames called.');
    // \Drupal::messenger()->addMessage('Floor: ' . $floor);
    // \Drupal::messenger()->addMessage('Location: ' . $location);

    $current_user = \Drupal::currentUser();
    if ($current_user->hasRole('faculty')) {
     // \Drupal::messenger()->addMessage('Current user has faculty role.');

      // Load the current user entity.
      $user = User::load($current_user->id());
      if ($user->hasField('field_freezer_name_ref') && !$user->get('field_freezer_name_ref')->isEmpty()) {
//        \Drupal::messenger()->addMessage('User has field_freezer_name_ref field.');

        // Load the field_freezer_name_ref field value (entity reference).
        $freezer_name_refs = $user->get('field_freezer_name_ref')->referencedEntities();

        // Collect the node IDs from the field_freezer_name_ref.
        $nids = [];
        foreach ($freezer_name_refs as $ref) {
          $nids[] = $ref->id();
        }
    //    \Drupal::messenger()->addMessage('Node IDs collected: ' . implode(', ', $nids));

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
    //      \Drupal::messenger()->addMessage('Nodes loaded: ' . implode(', ', array_keys($nodes)));

          foreach ($nodes as $node) {
            // Check if node has the specified field_location and field_floors values.
            $locations = $node->get('field_location')->referencedEntities();
            $floors = $node->get('field_floors')->referencedEntities();

            foreach ($locations as $node_location) {
              foreach ($floors as $node_floor) {
                if ($node_location->getName() == $location && $node_floor->getName() == $floor) {
                  $data[] = $node->getTitle();
            //      \Drupal::messenger()->addMessage('Node title added: ' . $node->getTitle());
                }
              }
            }
          }
        }
      }
    }
    else {
      if ($floor && $location) {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'freezer_names')
          ->condition('status', 1)
          ->condition('field_floors.entity.name', $floor)
          ->condition('field_location.entity.name', $location) // Filter by location
          ->accessCheck(FALSE);

        $nids = $query->execute();

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          foreach ($nodes as $node) {
            $data[] = $node->getTitle();
          }
        }
      }
    }

    return new JsonResponse($data);
  }

  public function getFloorNames() {
    $location = \Drupal::request()->query->get('location');
    $data1 = [];

    // \Drupal::messenger()->addMessage('getFloorNames called.');
    // \Drupal::messenger()->addMessage('Location: ' . $location);

    $current_user = \Drupal::currentUser();
    if ($current_user->hasRole('faculty')) {
//      \Drupal::messenger()->addMessage('Current user has faculty role.');

      // Load the current user entity.
      $user = User::load($current_user->id());
      if ($user->hasField('field_freezer_name_ref') && !$user->get('field_freezer_name_ref')->isEmpty()) {
    //    \Drupal::messenger()->addMessage('User has field_freezer_name_ref field.');

        // Load the field_freezer_name_ref field value (entity reference).
        $freezer_name_refs = $user->get('field_freezer_name_ref')->referencedEntities();

        // Collect the node IDs from the field_freezer_name_ref.
        $nids = [];
        foreach ($freezer_name_refs as $ref) {
          $nids[] = $ref->id();
        }
    //    \Drupal::messenger()->addMessage('Node IDs collected: ' . implode(', ', $nids));

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
       //   \Drupal::messenger()->addMessage('Nodes loaded: ' . implode(', ', array_keys($nodes)));

          foreach ($nodes as $node) {
            // Check if node has the specified field_location value.
            $locations = $node->get('field_location')->referencedEntities();
            foreach ($locations as $node_location) {
              if ($node_location->getName() == $location) {
                // Collect floor names.
                $floors = $node->get('field_floors')->referencedEntities();
                foreach ($floors as $floor) {
                  $data1[] = $floor->getName();
                  //\Drupal::messenger()->addMessage('Floor name added: ' . $floor->getName());
                }
              }
            }
          }
        }
      }
    } 
    
    else {
   //   \Drupal::messenger()->addMessage('TRTYTTTTTTTTT.');
      if ($location) {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'freezer_names')
          ->condition('status', 1)
          ->condition('field_location.entity.name', $location) // Filter by location
          ->accessCheck(FALSE);

        $nids = $query->execute();

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          foreach ($nodes as $node) {
            $floors = $node->get('field_floors')->referencedEntities();
            foreach ($floors as $floor) {
              $data1[] = $floor->getName();
            }
          }
        }
      }
    }

    return new JsonResponse($data1);
  }
}
