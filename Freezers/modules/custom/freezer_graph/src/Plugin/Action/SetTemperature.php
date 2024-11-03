<?php 
namespace Drupal\freezer_graph\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\user\Entity\User;

use Drupal\Core\Action\ActionBase;

/**
 * @Action(
 *   id = "set_temperature",
 *   label = @Translation("Set Threshold"),
 *   type = "",
 *   confirm = TRUE,
 *   api_version = "1",
 * )
 */
class SetTemperature extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

    public function execute($entity = NULL) {
        if ($entity === NULL || $entity->getEntityTypeId() !== 'node' || $entity->bundle() !== 'freezer_names') {
            \Drupal::messenger()->addError('The entity is not valid or not of the correct type.');
            return;
        }
    
        // Retrieve the value from the configuration.
        $set_temperature = $this->configuration['set_temperature'];
        $maximum_threshold = $this->configuration['maximum_threshold'];
    
        // Validate that set_temperature is less than maximum_threshold.
        if ($set_temperature >= $maximum_threshold) {
            \Drupal::messenger()->addError('The minimum threshold must be less than the maximum threshold.');
            return;
        }

        // Check if the field exists and set the value.
        if ($entity->hasField('field_set_temperature')) {
            $entity->set('field_set_temperature', $set_temperature);
        } else {
            \Drupal::messenger()->addError('The field_set_temperature does not exist on this entity.');
            return;
        }

        // Check if the field exists and set the value.
        if ($entity->hasField('field_maximum_threshold')) {
            $entity->set('field_maximum_threshold', $maximum_threshold);
        } else {
            \Drupal::messenger()->addError('The field_maximum_threshold does not exist on this entity.');
            return;
        }
    
        try {
            // Save the entity with the new field values.
            $entity->save();
            // \Drupal::messenger()->addMessage('Minimum threshold set for ' . $entity->label() . ': ' . $set_temperature);
            // \Drupal::messenger()->addMessage('Maximum threshold set for ' . $entity->label() . ': ' . $maximum_threshold);
        } catch (\Exception $e) {
            \Drupal::messenger()->addError('Failed to save the entity: ' . $e->getMessage());
        }
    }

    public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
        return $form;
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
        $form['set_temperature'] = [
            '#title' => $this->t('Minimum Threshold'),
            '#type' => 'textfield',
            '#default_value' => $form_state->getValue('set_temperature', ''),
            '#description' => $this->t('Enter Minimum Threshold'),
            '#required' => TRUE,
        ];

        $form['maximum_threshold'] = [
            '#title' => $this->t('Maximum Threshold'),
            '#type' => 'textfield',
            '#default_value' => $form_state->getValue('maximum_threshold', ''),
            '#description' => $this->t('Enter Maximum Threshold.'),
            '#required' => TRUE,
        ];
    
        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
        $this->configuration['set_temperature'] = $form_state->getValue('set_temperature');
        $this->configuration['maximum_threshold'] = $form_state->getValue('maximum_threshold');
    }

    public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
        return $object->access('update', $account, $return_as_object);
    }
}
