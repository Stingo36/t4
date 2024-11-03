<?php

namespace Drupal\freezer_graph\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\user\Entity\User;

/**
 * @Action(
 *   id = "assign_users_to_students",
 *   label = @Translation("Assign Users to Freezers"),
 *   type = "",
 *   confirm = TRUE,
 *   api_version = "1",
 * )
 */
class AssignFaculty extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {
  private $currentIndexStateKey = 'current_faculty_pair_index';

  public function execute($entity = NULL) {
    if ($entity instanceof User) {
      $selectedPanelId = $this->configuration['users_config_setting'];

      // Check if the field already has the selected ID
      $currentValues = $entity->get('field_freezer_name_ref')->getValue();
      $panelIds = array_column($currentValues, 'target_id');

      // If the selected ID is not already present, add it to the field
      if (!in_array($selectedPanelId, $panelIds)) {
        // Append the new ID to the current values
        $currentValues[] = ['target_id' => $selectedPanelId];
        $entity->set('field_freezer_name_ref', $currentValues);
        $entity->save();
        //\Drupal::messenger()->addMessage("Assigned user to panel: $selectedPanelId");
      } else {
        \Drupal::messenger()->addMessage("User is already assigned to panel: $selectedPanelId");
        return $this->t('User is already assigned to %panel', ['%panel' => $selectedPanelId]);
      }

      // Load the node and check if it has the 'field_faculties' field
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($selectedPanelId);
      if ($node) {
        if ($node->hasField('field_faculties')) {
          // Check if the 'field_faculties' field has existing values
          $currentFaculties = $node->get('field_faculties')->getValue();
          $userIds = array_column($currentFaculties, 'target_id');

          // If the user ID is not already present, add it to the 'field_faculties' field
          if (!in_array($entity->id(), $userIds)) {
            // Append the new user ID to the current values
            $currentFaculties[] = ['target_id' => $entity->id()];
            $node->set('field_faculties', $currentFaculties);
            $node->save();
           // \Drupal::messenger()->addMessage("User ID added to 'field_faculties'.");
          } else {
            \Drupal::messenger()->addMessage("User ID already exists in 'field_faculties'.");
          }
        } else {
          \Drupal::messenger()->addMessage("The node does not have the field 'field_faculties'.");
        }
      }
    }

    return $this->t('Assigned %user to %panel', ['%user' => $entity->label(), '%panel' => $selectedPanelId]);
  }

  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    return $form;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $obj = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'freezer_names']);
    $options = [];

    foreach ($obj as $node) {
      $options[$node->id()] = $node->getTitle();
    }

    $form['users_config_setting'] = [
      '#title' => t('Select Freezer Names'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['users_config_setting'] = $form_state->getValue('users_config_setting');
  }

  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
