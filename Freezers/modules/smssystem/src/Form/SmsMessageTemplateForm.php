<?php

namespace Drupal\smssystem\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for SMS Message Template edit forms.
 *
 * @ingroup smssystem
 */
class SmsMessageTemplateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\smssystem\Entity\SmsMessageTemplate $entity */
    $entity = $this->entity;

    $form = parent::buildForm($form, $form_state);
    // Hide original field and do the magic in context of save() callback.
    $form['template_name']['#access'] = FALSE;
    $weight = $form['template_name']['#weight'];
    $title = $form['template_name']['widget']['#title'];
    $template_name = $entity->get('template_name')->value ? $entity->get('template_name')->value : '';
    $form['template_name']['widget'][0]['value']['#default_value'] = $template_name;

    // Add tokens support for the SMS Message body.
    $form['template_text']['token_tree'] = [
      '#theme' => 'token_tree_link',
      // @todo Need some investigation because this config is not working.
      '#token_types' => ['user', 'site'],
      '#show_restricted' => TRUE,
      '#show_nested' => FALSE,
    ];
    $form['actions']['#weight'] = 101;

    // Add form.
    if ($entity->isNew()) {
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#weight' => $weight,
        '#default_value' => $template_name,
      ];
      $form['template_name_auto'] = [
        '#type' => 'machine_name',
        '#size' => 15,
        '#maxlength' => 128,
        '#machine_name' => ['source' => ['name'], 'exists' => [$this, 'exists']],
        '#required' => TRUE,
      ];
    }
    else {
      // Edit form.
      $form['name'] = [
        '#markup' => '<strong>' . $title . ': </strong>' . $template_name,
        '#weight' => $weight,
      ];
    }
    $form['#validate'][] = [$this, 'validate'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $template_text = $form_state->getValue('template_text')[0]['value'];
    // Validate the SMS message length.
    if (mb_strlen($template_text) > 140) {
      $form_state->setErrorByName('template_text', $this->t('Message can not be longer than 140 characters.'));
    }
  }

  /**
   * Determines if the action already exists.
   *
   * @param string $template_name
   *   The SMS Message template name.
   *
   * @return bool
   *   TRUE if the SMS Message template exists, FALSE otherwise.
   */
  public function exists($template_name) {
    return !empty(
      $this
        ->entityTypeManager
        ->getStorage('sms_message_template')
        ->loadByProperties(['template_name' => $template_name])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    if (!$entity->get('template_name')->value) {
      $entity->set('template_name', $form_state->getValue('template_name_auto'));
    }
    $entity->save();

    // Set redirect to listing page.
    $form_state->setRedirect('entity.sms_message_template.collection');
  }

}
