<?php

namespace Drupal\freezer_graph\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * @Action(
 *   id = "de_assign_users",
 *   label = @Translation("De Assign Users"),
 *   type = "",
 *   confirm = TRUE,
 *   api_version = "1",
 * )
 */
class DeAssignFaculty extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface
{
   

    public function execute($entity = NULL) {
        if ($entity instanceof User) {
            // Check if the user entity has a field named "field_freezer_name_ref".
            if ($entity->hasField('field_freezer_name_ref')) {
                // Get the referenced node ID(s).
                $referenced_node_ids = $entity->get('field_freezer_name_ref')->getValue();
                
                // Loop through each referenced node ID.
                foreach ($referenced_node_ids as $item) {
                    $node_id = $item['target_id'];
                    
                    // Load the referenced node.
                    $node = Node::load($node_id);
                    
                    if ($node) {
                        // Check if the referenced node has a field named "field_faculties".
                        if ($node->hasField('field_faculties')) {
                            // Clear the value of the "field_faculties" field.
                            $node->set('field_faculties', NULL)->save();
                            
                            // Optionally, you can display a message indicating that the field has been cleared.
                           // \Drupal::messenger()->addMessage("The 'field_faculties' field has been cleared for node with ID: $node_id");
                        }
                    }
                }
                $entity->set('field_freezer_name_ref', '');
                $entity->save();
            }
        }
    }
    
    
    
    
       



























    public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        $this->configuration['users_config_setting'] = $form_state->getValue('users_config_setting');
    }

    public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE)
    {
        return $object->access('update', $account, $return_as_object);
    }
}
