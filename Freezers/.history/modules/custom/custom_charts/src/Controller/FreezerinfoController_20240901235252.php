<?php 

namespace Drupal\ncbs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

/**
 * Class FreezerInfoControlelr.
 *
 * Provides responses for freezer details.
 */
class FreezerInfoControlelr extends ControllerBase {

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
      $status = $node->isPublished() ? 'Published' : 'Unpublished';

      // Return a JSON response with the status.
      return new JsonResponse(['status' => $status]);
    }

    // If no node found, return a JSON response with an error message.
    return new JsonResponse(['status' => 'Freezer not found'], 404);
  }
}
