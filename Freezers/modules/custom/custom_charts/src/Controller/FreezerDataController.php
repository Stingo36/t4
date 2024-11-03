<?php 
// namespace Drupal\custom_charts\Controller;

// use Drupal\Core\Controller\ControllerBase;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Drupal\node\Entity\Node;

// /**
//  * Class FreezerDataController.
//  */
// class FreezerDataController extends ControllerBase {

//   /**
//    * Get data based on the selected freezer name and duration.
//    */
//   public function getFreezerData($freezer_name) {
//     $duration = \Drupal::request()->query->get('duration', 24); // Get duration from query parameter, default to 24 hours
//     $duration_in_seconds = $duration * 3600; // Convert hours to seconds

//     // Get the current time
//     $current_time = \Drupal::time()->getCurrentTime();
//     $current_time_formatted = date('Y-m-d H:i:s', $current_time);

//     // Get the timestamp for the start time based on the duration
//     $start_time = $current_time - $duration_in_seconds;
//     $start_time_formatted = date('Y-m-d H:i:s', $start_time);

//     // Query to get freezer data from the start time to the current time
//     $query = \Drupal::entityQuery('node')
//       ->condition('type', 'freezer_data')
//       ->condition('status', 1)
//       ->condition('field_f_names', $freezer_name)
//       ->condition('field_time', [$start_time_formatted, $current_time_formatted], 'BETWEEN')
//       ->sort('field_time', 'ASC')
//       ->accessCheck(FALSE);

//     $nids = $query->execute();
//     $nodes = Node::loadMultiple($nids);

//     $data = [];
//     foreach ($nodes as $node) {
//       $data[] = [
//         'date' => strtotime($node->get('field_time')->value) * 1000, // Convert to milliseconds
//         'value' => (float) $node->get('field_value')->value,
//         'title' => $node->getTitle(),
//         'time' => $node->get('field_time')->value,
//       ];
//     }

//     // Debugging: Log the number of data points retrieved
//     \Drupal::logger('custom_charts')->info('Number of data points: @count', ['@count' => count($data)]);

//     return new JsonResponse($data);
//   }
// }































namespace Drupal\custom_charts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class FreezerDataController.
 */
class FreezerDataController extends ControllerBase {

  /**
   * Get data based on the selected freezer name and duration.
   */
  public function getFreezerData($freezer_name) {
    $duration = \Drupal::request()->query->get('duration', '24hr'); // Get duration from query parameter, default to 24 hours
    $duration_in_seconds = $this->convertDurationToSeconds($duration);

    // Get the current time
    $current_time = \Drupal::time()->getCurrentTime();
    $current_time_formatted = date('Y-m-d H:i:s', $current_time);

    // Get the timestamp for the start time based on the duration
    $start_time = $current_time - $duration_in_seconds;
    $start_time_formatted = date('Y-m-d H:i:s', $start_time);

    $data = [];
    $file_name = 'private://' . $freezer_name . '.csv';

    if (file_exists($file_name)) {
      if (($handle = fopen($file_name, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
          $time = strtotime($row[1]);
          if ($duration === 'All' || ($time >= $start_time && $time <= $current_time)) {
            $data[] = [
              'date' => $time * 1000, // Convert to milliseconds
              'value' => (float) $row[0],
              'title' => $freezer_name . ' Data',
              'time' => $row[1],
            ];
          }
        }
        fclose($handle);
      }
    }

    // Debugging: Log the number of data points retrieved
    \Drupal::logger('custom_charts')->info('Number of data points: @count', ['@count' => count($data)]);

    return new JsonResponse($data);
  }

  /**
   * Convert duration string to seconds.
   */
  private function convertDurationToSeconds($duration) {
    switch ($duration) {
      case '1hr':
        return 3600;
      case '3hr':
        return 3 * 3600;
      case '6hr':
        return 6 * 3600;
      case '12hr':
        return 12 * 3600;
      case '24hr':
        return 24 * 3600;
      case 'All':
      default:
        return PHP_INT_MAX; // Effectively return all data by setting a very large duration
    }
  }
}
