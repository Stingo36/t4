<?php 

namespace Drupal\custom_charts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File; // Make sure to import the File entity class

/**
 * Class FreezerInfoController.
 *
 * Provides responses for freezer details.
 */
class FreezerInfoController extends ControllerBase {

  /**
   * Check if the selected freezer is published or unpublished.
   */
  public function checkFreezerStatus($freezer_name) {
    // Log the start of the method
    \Drupal::logger('custom_charts')->debug('Starting checkFreezerStatus with freezer_name: @name', ['@name' => $freezer_name]);

    // Query for nodes of type 'freezer_names' with the title matching the freezer name.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'freezer_names')
      ->condition('title', $freezer_name)
      ->range(0, 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (empty($nids)) {
      // Log that no node was found
      \Drupal::logger('custom_charts')->error('No freezer found with the name: @name', ['@name' => $freezer_name]);
      return new JsonResponse(['error' => 'Freezer not found'], 404);
    }

    $node = Node::load(reset($nids));
    \Drupal::logger('custom_charts')->debug('Node loaded with ID: @id', ['@id' => $node->id()]);

    // Initialize an array to hold the response data.
    $response_data = [
      'status' => $node->isPublished() ? 'Active' : 'Inactive',
    ];
    \Drupal::logger('custom_charts')->debug('Node status is: @status', ['@status' => $response_data['status']]);

    // List of fields to retrieve
    $fields = [
      'field_current_value' => 'current_value',
      'field_current_time' => 'current_time',
      'faculties' => [],
      'field_maximum_threshold' => 'maximum_threshold',
      'field_set_temperature' => 'minimum_threshold',
      'field_all_time_high_time' =>'all_time_high_time',
      'field_all_time_low_time' =>'all_time_low_time',
      'field_all_time_highest_temperatu' => 'all_time_highest_temperatu',
      'field_all_time_lowest_temperatur' => 'all_time_lowest_temperatu',
    ];

    // Loop through each field to check and get its value if it exists
    foreach ($fields as $field_name => $response_key) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $response_data[$response_key] = $node->get($field_name)->value;
        \Drupal::logger('custom_charts')->debug('Field "@field" found: @value', ['@field' => $field_name, '@value' => $response_data[$response_key]]);
      }
    }



 // Check if the 'field_download_data' field exists and has a file entity
 if ($node->hasField('field_download_data') && !$node->get('field_download_data')->isEmpty()) {
  $file = $node->get('field_download_data')->entity;
  if ($file) {
    // Use the FileUrlGenerator service to create a file URL
    $file_url_generator = \Drupal::service('file_url_generator');
    $file_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
    
    if ($file_url) {
      \Drupal::logger('custom_charts')->debug('File URL generated: @url', ['@url' => $file_url]);
      $response_data['download_data'] = $file_url;
    } else {
      \Drupal::logger('custom_charts')->error('Failed to create file URL for file ID: @id', ['@id' => $file->id()]);
    }
  } else {
    \Drupal::logger('custom_charts')->error('File entity could not be loaded for field_download_data.');
  }
} else {
  \Drupal::logger('custom_charts')->error('field_download_data is empty or does not exist.');
}


// Handle the field_faculties reference field
if ($node->hasField('field_faculties') && !$node->get('field_faculties')->isEmpty()) {
  foreach ($node->get('field_faculties') as $reference) {
      $faculty = $reference->entity;
      if ($faculty) {
          $response_data['faculties'][] = [
              'id' => $faculty->id(),
              'name' => $faculty->label(),
          ];
      }
  }
}

    // Return the response data if any relevant fields were found
    if (count($response_data) > 1) { // 1 for the status field
      return new JsonResponse($response_data);
    }

    // Log if no relevant fields were found
    \Drupal::logger('custom_charts')->error('No relevant fields found for the freezer.');
    return new JsonResponse(['error' => 'No relevant fields found.'], 404);
  }
}
