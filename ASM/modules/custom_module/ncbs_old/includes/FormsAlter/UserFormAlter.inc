<?php
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements hook_form_FORM_ID_alter() to modify the user registration form.
 *
 * This function adds a custom submit handler to the user registration form.
 */
function ncbs_form_user_register_form_alter(&$form, &$form_state, $form_id) {
    // Check if the form is the user registration form and the user is anonymous.
    if ($form_id == "user_register_form" && \Drupal::currentUser()->isAnonymous()) {
        // Add a custom submit handler.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_register_form_submit';
    }
}

/**
 * Custom submit handler for the user registration form.
 *
 * This function activates the newly registered user, assigns them the "user" role,
 * and creates a node of type 'submit_application' referencing the user.
 */
function ncbs_user_register_form_submit(&$form, &$form_state) {
    // Get the newly created user entity from the form state.
    $user = $form_state->getFormObject()->getEntity();

    // Activate the user account.
    $user->set('status', 1); // Activate the user account.
    $user->addRole('user');  // Assign the "user" role to the new user.
    $user->notify = FALSE;   // Disable email notifications on registration.
    $user->save();

    // Get the username from the profile.
    $userName = $user->get('name')->value;

    // Create a new node of type 'submit_application' with the user's information.
    $node = \Drupal\node\Entity\Node::create([
        'type' => 'submit_application',
        'title' => $userName,
        'field_user_reference' => [
            'target_id' => $user->id(), // Reference the newly created user.
        ],
        'uid' => $user->id(), // Set the node author to the newly created user.
    ]);

    // Set the node author as the newly created user.
    $node->setOwner($user);

    // Save the new node.
    $node->save();

    // Save the node ID to the user's profile field 'field_user_submit_app_ref'.
    $user->set('field_user_submit_app_ref', $node->id());
    $user->save();
}

/**
 * Implements hook_form_FORM_ID_alter() to modify the user edit form.
 *
 * This function hides certain fields from users with the "user" role when they are editing their profile.
 */
function ncbs_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id) {
    // Get the current user.
    $user = \Drupal::currentUser();

    // Check if the form is the user edit form and the current user has the "user" role.
    if ($form_id == 'user_form' && $user->hasRole('user')) {
        // Hide specific profile fields from users with the "user" role.
        $form['field_gender']['#access'] = FALSE;
        $form['field_centres']['#access'] = FALSE;
        $form['field_date_of_birth']['#access'] = FALSE;
        $form['field_valid_indian_passport']['#access'] = FALSE;
        $form['field_program']['#access'] = FALSE;

        // Hide the email field in the user profile.
        $form['account']['mail']['#access'] = FALSE;

        // Add a custom submit handler for redirection after the form is submitted.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_form_submit_handler';
    }
}

/**
 * Custom submit handler for the user edit form.
 *
 * This function redirects users with the "user" role to their profile page after they update their profile.
 */
function ncbs_user_form_submit_handler($form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Get the current user's ID for redirection.
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();

    // Define the redirect URL, directing the user to their profile page.
    $redirect_url = 'http://172.16.218.190/ASM/user/' . $user_id;

    // Redirect the user to their profile page after form submission.
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri($redirect_url, [], ['absolute' => TRUE]));
}
