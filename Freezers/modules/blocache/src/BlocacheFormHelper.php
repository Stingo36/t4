<?php

namespace Drupal\blocache;

use Drupal\Core\Form\FormStateInterface;
use Drupal\block\BlockInterface;

/**
 * Provides some methods for handling changes to the block form.
 */
class BlocacheFormHelper {

  /**
   * Ajax callback function for the block form's add tag button.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addTag(array &$form, FormStateInterface $form_state) {
    return $form['blocache']['tabs']['tags']['value'];
  }

  /**
   * Subrmit function for the block form's add tag button.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addTagSubmit(array &$form, FormStateInterface $form_state) {
    $count_tags = $form_state->get('count_tags');
    $form_state->set('count_tags', ++$count_tags);
    $form_state->setRebuild();
  }

  /**
   * Entity builder function for the block form.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\block\BlockInterface $block
   *   The block entity.
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function entityBuilder($entity_type, BlockInterface $block, array &$form, FormStateInterface $form_state) {
    $blocache = \Drupal::service('blocache');
    $blocache_metadata = $blocache->getMetadataService();
    $blocache_metadata->setBlock($block);

    $values = $form_state->getValue('blocache');
    if ($values['overridden'] === 1) {
      $tabs = $values['tabs'];
      $max_age = (int) $tabs['max-age']['value'];
      $contexts = $blocache->prepareContextsToStorage($tabs['contexts']['value']);
      $tags = $tabs['tags']['value'];
      $blocache_metadata->setOverrides($max_age, $contexts, $tags);
    }
    else {
      $blocache_metadata->unsetOverrides();
    }
  }

  /**
   * Returns the structure of a tag element.
   *
   * @param string|null $default_value
   *   The default value of the tag element.
   *
   * @return array
   *   Returns the tag element.
   */
  public static function tagElement(?string $default_value = ''): array {
    $element = [
      '#type' => 'textfield',
      '#default_value' => $default_value ?? '',
    ];

    if (\Drupal::service('blocache')->getTokenService()) {
      $element['#element_validate'] = ['token_element_validate'];
    }

    return $element;
  }

}
