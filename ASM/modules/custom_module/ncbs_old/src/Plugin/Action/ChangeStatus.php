<?php

namespace Drupal\ncbs\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\node\Entity\Node;

/**
 * @Action(
 *   id = "change_application_status",
 *   label = @Translation("Change Application Status"),
 *   type = "node",
 *   confirm = TRUE,
 *   api_version = "1",
 * )
 */
class ChangeStatus extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface
{
    private $currentIndexStateKey = 'current_faculty_pair_index';

    public function execute($entity = NULL) {
        // Check if the entity is a node and of the content type submit_application
        if ($entity instanceof Node && $entity->bundle() === 'submit_application') {
            // Get the selected status from the configuration
            $selected_status = $this->configuration['status'];
            // Update the field_status with the selected status
            $entity->set('field_status', $selected_status);
            // Save the entity
            $entity->save();

            return $this->t('Updated status for %user', ['%user' => $entity->label()]);
        }

        return $this->t('Entity is not of the correct type.');
    }

    public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        // Get the list of allowed values from the field_status list field.
        $field_values = [];
        $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'submit_application');
        
        if (isset($field_definitions['field_status'])) {
            $field_settings = $field_definitions['field_status']->getSettings();
            if (isset($field_settings['allowed_values'])) {
                $field_values = $field_settings['allowed_values'];
            }
        }

        // Create the dropdown list.
        $form['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Select Status'),
            '#options' => $field_values,
            '#required' => TRUE,
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        $this->configuration['status'] = $form_state->getValue('status');
    }

    public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE)
    {
        return $object->access('update', $account, $return_as_object);
    }
}
