<?php 

namespace Drupal\custom_charts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides a 'Location Button' block.
 *
 * @Block(
 *   id = "location_button",
 *   admin_label = @Translation("Location Button"),
 * )
 */
class LocationButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];
    $location_options_array = ['' => $this->t('Select location')];

    try {
      $current_user = \Drupal::currentUser();

      if ($current_user->hasRole('faculty')) {
        $user = User::load($current_user->id());
        $freezer_name_refs = $user->get('field_freezer_name_ref')->referencedEntities();
        $nids = array_map(function($ref) {
          return $ref->id();
        }, $freezer_name_refs);

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          $location_options_array += $this->getUniqueLocations($nodes);
        }
      } else {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'freezer_names')
          ->condition('status', 1)
          ->accessCheck(FALSE);
        $nids = $query->execute();

        if (!empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          $location_options_array += $this->getUniqueLocations($nodes);
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('custom_charts')->error('Failed to load Freezer_names nodes: @message', ['@message' => $e->getMessage()]);
    }

    $form['#attached']['library'][] = 'custom_charts/dependent_dropdowns';

    // Wrap dropdowns in a Bootstrap row for alignment.
    $form['dropdown_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
    ];

    // Add each dropdown to the container.
    $form['dropdown_container']['location'] = $this->createDropdown('location', $this->t('Locations'), $location_options_array);
    $form['dropdown_container']['dropdown1'] = $this->createDropdown('dropdown1', $this->t('Floors'), ['' => $this->t('Select Floor')]);
    $form['dropdown_container']['freezer_dropdown'] = $this->createDropdown('freezer-select', $this->t('Freezers'), ['' => $this->t('Select Freezer')]);

    // Attach custom JavaScript.
    $form['#attached']['library'][] = 'custom_charts/custom_charts_js';
    return $form;
  }

  /**
   * Helper function to get unique locations from nodes.
   */
  private function getUniqueLocations($nodes) {
    $unique_locations = [];
    $location_options_array = [];

    foreach ($nodes as $node) {
      $locations = $node->get('field_location')->referencedEntities();
      foreach ($locations as $location) {
        $location_name = $location->getName();
        if (!in_array($location_name, $unique_locations)) {
          $location_options_array[$location_name] = $location_name;
          $unique_locations[] = $location_name;
        }
      }
    }

    return $location_options_array;
  }

  /**
   * Helper function to create a dropdown with Bootstrap styling.
   */
  private function createDropdown($id, $title, $options) {
    return [
      '#type' => 'select',
      '#title' => $title,
      '#options' => $options,
      '#default_value' => '',
      '#attributes' => [
        'id' => $id,
        'class' => ['form-control'], // Use Bootstrap's form-control class
      ],
      '#wrapper_attributes' => [
        'class' => ['form-group', 'col-md-4'], // Ensure equal size using Bootstrap grid classes
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
