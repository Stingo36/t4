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
use Drupal\Core\Url;

/**
 * Alters the user registration form to add a custom submit handler.
 */
function ncbs_form_user_register_form_alter(&$form, &$form_state, $form_id) {
    if ($form_id == "user_register_form" && \Drupal::currentUser()->isAnonymous()) {
        $form['actions']['submit']['#submit'][] = 'ncbs_user_register_form_submit';
    }
}

/**
 * Custom submit handler for the user registration form.
 * Activates the user, assigns the "user" role, and creates a 'submit_application' node.
 */
function ncbs_user_register_form_submit(&$form, &$form_state) {
    $user = $form_state->getFormObject()->getEntity();

    // Activate the user and assign the "user" role.
    $user->set('status', 1);
    $user->addRole('user');
    $user->notify = FALSE;
    $user->save();

    // Create a 'submit_application' node for the user.
    $node = Node::create([
        'type' => 'submit_application',
        'title' => $user->get('name')->value,
        'field_user_reference' => ['target_id' => $user->id()],
        'uid' => $user->id(),
    ]);
    $node->setOwner($user);
    $node->save();

    // Save the node ID to the user's profile field.
    $user->set('field_user_submit_app_ref', $node->id());
    $user->save();
}

/**
 * Alters the user edit form to hide fields for users with the "user" role.
 */
function ncbs_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id) {
    $user = \Drupal::currentUser();

    if ($form_id == 'user_form' && $user->hasRole('user')) {
        // Hide specific fields for users with the "user" role.
        $form['field_gender']['#access'] = FALSE;
        $form['field_centres']['#access'] = FALSE;
        $form['field_date_of_birth']['#access'] = FALSE;
        $form['field_valid_indian_passport']['#access'] = FALSE;
        $form['field_program']['#access'] = FALSE;
        $form['account']['mail']['#access'] = FALSE;

        // Add custom submit handler for redirection.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_form_submit_handler';
    }
}

/**
 * Custom submit handler for the user edit form.
 * Redirects users with the "user" role to their profile page after updating their profile.
 */
function ncbs_user_form_submit_handler($form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();

    // Use the base URL and host dynamically.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $redirect_url = Url::fromUri($base_url . '/ASM/user/' . $current_user->id(), [], ['absolute' => TRUE]);

    // Redirect the user to their profile page after form submission.
    $form_state->setRedirectUrl($redirect_url);
}
