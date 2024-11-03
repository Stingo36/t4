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
 * Implementing hook_form_FORM_ID_alter()
 */
//ANCHOR -   user Register form
function ncbs_form_user_register_form_alter(&$form, &$form_state, $form_id)
{
    if ($form_id == "user_register_form"  && \Drupal::currentUser()->isAnonymous()) {
        // Add a checkbox for agreeing to terms and conditions.
        // $form['instem_register'] = array(
        //     '#type' => 'checkbox',
        //     '#title' => '<strong>' . t('Select checkbox if you have registered at InStem for Faculty position') . '</strong>',
        //     '#weight' => 10,
        // );
        // Add a custom submit handler to the form.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_register_form_submit';
    }
}

//ANCHOR -  Custom submit handler for user_register_form 
/* ---- //! When User Register form is submitted this function is trigger --- */
function ncbs_user_register_form_submit(&$form, &$form_state)
{
    $user = $form_state->getFormObject()->getEntity();

    // Activate the user account.
    $user->set('status', 1);    //  to make the profile active
    $user->addRole('user');     //  add role
    $user->notify = FALSE;      //  Notify False
    $user->save();

    // Get the user's name from the profile.
    $userName = $user->get('name')->value;
    // Create a new node.
    $node = \Drupal\node\Entity\Node::create([
        'type' => 'submit_application',
        'title' => $userName,
        'field_user_reference' => [
            'target_id' => $user->id(), // Reference the newly created user
        ],
        'uid' => $user->id(), // Set the node author to the newly created user ID
        // Set other fields of the node as needed
    ]);

    // // Set the node author to the newly created user.
    $node->setOwner($user);

    // Save the node.
    $node->save();

    // Save the node ID to the user's profile field 'field_user_submit_app_ref'.
    $user->set('field_user_submit_app_ref', $node->id());
    $user->save();
}

//ANCHOR - User Edit Form
// /* ------------------- //! Hiding user fields when role is user ------------------ */
function ncbs_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
    // Get the current user.
    $user = \Drupal::currentUser();

    // Check if the form is the user edit form and the current user has the "user" role.
    if ($form_id == 'user_form' && $user->hasRole('user')) {
        // Hide the specified fields.
        $form['field_gender']['#access'] = FALSE;
        $form['field_centres']['#access'] = FALSE;
        $form['field_date_of_birth']['#access'] = FALSE;
        $form['field_valid_indian_passport']['#access'] = FALSE;
        $form['field_program']['#access'] = FALSE;

        // Hide the mail field in the user profile.
        $form['account']['mail']['#access'] = FALSE;

        // Add a custom submit handler redirecting it to user view.
        $form['actions']['submit']['#submit'][] = 'ncbs_user_form_submit_handler';
    } else {
        //\Drupal::messenger()->addMessage('Thasdasasd.');
    }
}

//ANCHOR - Custom submit handler for the user_form.
/* --------------------- //! Redirecting to User Profile -------------------- */
function ncbs_user_form_submit_handler($form, \Drupal\Core\Form\FormStateInterface $form_state)
{
    // Define the redirect URL.
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $redirect_url = 'http://172.16.218.190/ASM/user/' . $user_id;

    // Perform the redirect.
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri($redirect_url, [], ['absolute' => TRUE]));
}

