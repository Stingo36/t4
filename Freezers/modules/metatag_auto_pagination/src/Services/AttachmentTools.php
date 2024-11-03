<?php

namespace Drupal\metatag_auto_pagination\Services;

/**
 * Tools for html attachment.
 */
class AttachmentTools {

  /**
   * Service ID.
   *
   * @const string
   */
  public const SERVICE_ID = 'metatag_auto_pagination.attachment_tools';

  /**
   * Singleton quick access.
   *
   * @return static
   *   Singleton.
   */
  public static function instance() {
    return \Drupal::service(static::SERVICE_ID);
  }

  /**
   * Return all elements id in the list of tags.
   *
   * @param mixed $element_id
   *   The id of the element.
   * @param array $tags
   *   The list of tags..
   */
  public function getElementsInAttachment($element_id, array $tags) {
    return array_filter($tags, function ($item) use ($element_id) {
      return in_array($element_id, $item);
    });
  }

  /**
   * Return the list of keys of the tags.
   *
   * @param mixed $element_id
   *   The element id.
   * @param array $tags
   *   The list of tags.
   *
   * @return array
   *   The keys.
   */
  public function getKeysInAttachment($element_id, array $tags) {
    return array_keys($this->getElementsInAttachment($element_id, $tags));
  }

  /**
   * Delete an element in attachments.
   *
   * @param string $element_id
   *   The element id.
   * @param array $tags
   *   The list of tags.
   */
  public function removeElementInAttachment($element_id, array &$tags) {
    $keys = array_keys($tags);
    for ($i = count($tags) - 1; $i > -1; $i--) {
      if (in_array($element_id, $tags[$keys[$i]])) {
        unset($tags[$keys[$i]]);
      }
    }
  }

}
