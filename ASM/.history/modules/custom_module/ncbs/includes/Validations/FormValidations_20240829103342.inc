<?php 

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Custom validation function.
 */
function ncbs_custom_validation(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($current_user->id());

    // Load the node object from the form state.
    $node = $form_state->getFormObject()->getEntity();

    $empty_fields = [];
    $fields = [
        'field_user_academic_qualifi_ref' => 'Academic Qualification',
        'field_user_other_rel_info_ref' => 'Other Relevant Information',
        'field_user_list_of_referees_ref' => 'List of Referees',
        'field_user_research_proposal_ref' => 'Research Proposal',
        'field_user_update_pub_ref' => 'Update Publications',
        'field_user_basic_info_ref' => 'Basic Information',
    ];

    foreach ($fields as $field_name => $field_label) {
        $field_value = $user->get($field_name)->getValue();
        if (empty($field_value)) {
            $empty_fields[] = $field_name;
        }
    }

    $referees_check_passed = false;

    // Additional check for field_user_list_of_referees_ref
    if (!empty($user->get('field_user_list_of_referees_ref')->getValue())) {
        $list_of_referees_nid = $user->get('field_user_list_of_referees_ref')->target_id;
        $list_of_referees_node = Node::load($list_of_referees_nid);
        if ($list_of_referees_node && !empty($list_of_referees_node->get('field_list_of_referees_')->getValue())) {
            $referees_paragraphs = $list_of_referees_node->get('field_list_of_referees_')->referencedEntities();
            $count_referees = count($referees_paragraphs);
            if ($count_referees >= 8) {
                $referees_check_passed = true;
            } else {
                $remaining_count = 8 - $count_referees;
                $form_state->setErrorByName('field_list_of_referees_', t('Minimum 8 referees required. Count remaining: @count', ['@count' => $remaining_count]));
            }
        } else {
            $form_state->setErrorByName('field_list_of_referees_', t('Minimum 8 referees required. Count remaining: 8'));
        }
    } else {
        $form_state->setErrorByName('field_user_list_of_referees_ref', t('Minimum 8 referees required. Count remaining: 8'));
    }

    if (!empty($empty_fields)) {
        foreach ($empty_fields as $field_name) {
            $form_state->setErrorByName($field_name, t('@field_label has not been submitted.', ['@field_label' => $fields[$field_name]]));
        }
    } else {
        if ($referees_check_passed && $form_state->isSubmitted()) {
            // Generate an 8-digit random string
            $random_string = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(8 / strlen($x)))), 1, 8);

            // Save the generated string to the 'field_session_key' field
            $form_state->setValue(['field_session_key', 0, 'value'], $random_string);

            // Save the generated string to the user field 'field_user_session_key'
            $user->set('field_user_session_key', $random_string)->save();

            // Check if the field_session_key value is set and then set the field_date_received
            if ($form_state->getValue(['field_session_key', 0, 'value']) === $random_string) {
                // Save the current date to the 'field_date_received' field
                $current_date = date('Y-m-d');
                $form_state->setValue(['field_date_received', 0, 'value'], $current_date);
            }

            // Update the body field with custom text
            $form_state->setValue(['body', 0, 'value'], 'Application has been submitted successfully.');

            // Clear block caches
            \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_view']);
            \Drupal::messenger()->addMessage(t('All fields successfully submitted.'));
            \Drupal::messenger()->addMessage('Application submitted successfully.');
        } else {
            \Drupal::messenger()->addMessage('Submission failed.');
        }
    }
}
