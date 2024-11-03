<?php 


namespace Drupal\freezer_graph\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;

/**
 * @Action(
 *   id = "assign_freezers_location",
 *   label = @Translation("Assign Freezers to Location"),
 *   type = "",
 *   confirm = TRUE,
 *   api_version = "1",
 * )
 */
class AssignFreezersLocation extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Add a debug message.
    \Drupal::messenger()->addMessage("Executing AssignFreezersLocation action.");

    // Get the selected location from the configuration.
    $selected_location = $this->configuration['location'];
    $selected_floor = $this->configuration['floor'];

    if ($entity) {
      $entity->set('field_location', $selected_location);
      $entity->set('field_floors', $selected_floor);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Get all terms from the taxonomy 'locations'.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('locations');
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    // Add a select field to the form.
    $form['location'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Location'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('location', ''),
      '#required' => TRUE,
    ];

    // Get all terms from the taxonomy 'Floors'.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('floors');
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    // Add a select field to the form.
    $form['floor'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Floor'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('floor', ''),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Get the selected location from the form.
    $location = $form_state->getValue('location');
    // Store the selected location in the configuration.
    $this->configuration['location'] = $location;

    $floor = $form_state->getValue('floor');
    // Store the selected floor in the configuration.
    $this->configuration['floor'] = $floor;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}

