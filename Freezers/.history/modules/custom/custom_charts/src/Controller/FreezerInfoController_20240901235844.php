<?php 

namespace Drupal\custom_charts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

/**
 * Class FreezerInfoControlelr.
 *
 * Provides responses for freezer details.
 */
class FreezerInfoController extends ControllerBase {

  /**
   * Check if the selected freezer is published or unpublished.
   */
  public function checkFreezerStatus($freezer_name) {
    // Query for nodes of type 'freezer_names' with the title matching the freezer name.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'freezer_names')
      ->condition('title', $freezer_name)
      ->range(0, 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();

    // Check if any node was found.
    if (!empty($nids)) {
      $node = Node::load(reset($nids));

      // Get the 'field_current_value' field value.
      if ($node->hasField('field_current_value') && !$node->get('field_current_value')->isEmpty()) {
        $current_value = $node->get('field_current_value')->value;

        // Return a JSON response with the current value.
        return new JsonResponse(['current_value' => $current_value]);
      } else {
        // If the field is not available or empty, return an error message.
        return new JsonResponse(['error' => 'Field "current value" not found or empty.'], 404);
      }
    }

    // If no node found, return a JSON response with an error message.
    return new JsonResponse(['error' => 'Freezer not found'], 404);
  }
}