<?php

namespace Drupal\ncbs\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\node\Entity\Node;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("all_application_comments")
 */
class AllApplicationComments extends FieldPluginBase
{

    /**
     * {@inheritdoc}
     * This field does not require grouping.
     */
    public function usesGroupBy()
    {
        return FALSE;
    }

    /**
     * {@inheritdoc}
     * Override the parent query method to do nothing.
     */
    public function query()
    {
        // Do nothing -- to override the parent query.
    }

    /**
     * {@inheritdoc}
     * Define options for the field.
     */
    protected function defineOptions()
    {
        $options = parent::defineOptions();
        $options['hide_alter_empty'] = ['default' => FALSE];
        return $options;
    }

    /**
     * {@inheritdoc}
     * Build the options form for the field.
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state)
    {
        parent::buildOptionsForm($form, $form_state);
    }

    /**
     * Render the comments field.
     *
     * @param \Drupal\node\NodeInterface $node
     *   The node entity.
     * @param string $reference_field
     *   The name of the field containing the references to comments.
     * @param string $title
     *   The title to display.
     *
     * @return string
     *   The rendered output.
     */
    protected function renderComments($node, $reference_field, $title)
    {
        $output = '';
        $titleDisplayed = false;

        // Check if the node has the specified reference field and if it is not empty
        if ($node->hasField($reference_field) && !$node->get($reference_field)->isEmpty()) {
            $comment_references = $node->get($reference_field)->referencedEntities();

            // Loop through each referenced comment entity
            foreach ($comment_references as $comment_entity) {
                if ($comment_entity->getEntityTypeId() === 'node') {
                    // Get the comment details
                    $comment_value = $comment_entity->get('field_add_comments')->value; // Comment body
                    $comment_date_value = $comment_entity->get('field_comment_date')->value; // Comment date
                    $comment_by_value = $comment_entity->get('field_comment_name')->value; // Comment author

                    // Display the title only once
                    if (!$titleDisplayed) {
                        $output .= '<strong>' . $title . '</strong><br>';
                        $titleDisplayed = true;
                    }

                    // Build and add the formatted output string for each comment
                    $output .= '<u>Comments by: ' . $comment_by_value . '  Date: ' . $comment_date_value . '</u><br>' .
                        $comment_value . '<br><br>';
                }
            }
        } else {
            // Return a message indicating no comments found
            $output = '<b>' . ucwords(strtolower($title)) . ' not found</b><br><br>';
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     * Render the custom field.
     */
    public function render(ResultRow $values)
    {
        // Get the current user
        $current_user = \Drupal::currentUser();
        // Check if the user has the admin or administrator role
        if ($current_user->hasRole('admin') || $current_user->hasRole('administrator')) {
            $entity = $values->_entity;
            // Check if the entity is a node
            if ($entity && $entity->getEntityTypeId() === 'node') {
                $node = \Drupal::entityTypeManager()->getStorage('node')->load($entity->id());
                // Check if the node is of type 'submit_application'
                if ($node->bundle() === 'submit_application') {
                    $output = '';

                    // Render Dean's comments
                    $output .= $this->renderComments($node, '', 'DEAN COMMENTS');
                    // Render Director's comments
                    $output .= $this->renderComments($node, 'field_director_comments', 'DIRECTOR COMMENTS');
                    // Render Admin's comments
                    $output .= $this->renderComments($node, 'field_overall_comments', 'OVERALL COMMENTS');

                    // Return the rendered output
                    return [
                        '#markup' => $output,
                    ];
                }
            }
        }
        // Return empty markup if user is not admin or administrator
        return [
            '#markup' => '',
        ];
    }
}
