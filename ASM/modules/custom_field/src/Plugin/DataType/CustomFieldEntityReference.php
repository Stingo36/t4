<?php

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * The "custom_field_entity_reference" data type.
 *
 * The "custom_field_entity_reference" data type provides a way to process
 * entity as part of values.
 *
 * @DataType(
 *   id = "custom_field_entity_reference",
 *   label = @Translation("Entity reference"),
 *   definition_class = "\Drupal\custom_field\TypedData\CustomFieldDataDefinition"
 * )
 */
class CustomFieldEntityReference extends TypedData implements PrimitiveInterface {

  /**
   * The data value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The entity object or null.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $entity = $value['entity'] ?? NULL;
    if ($entity instanceof EntityInterface) {
      if ($entity->isNew()) {
        try {
          $entity->save();
        }
        catch (EntityStorageException $exception) {
          $entity = NULL;
        }
      }
      $this->entity = $entity;
      $value = $entity->id();
    }
    $this->value = $value['target_id'] ?? $value;
  }

  /**
   * Helper function to load an entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or null.
   */
  public function getEntity() {
    if (empty($this->entity) && !empty($this->value)) {
      $target_type = $this->getDataDefinition()->getSetting('target_type');
      $storage = \Drupal::entityTypeManager()->getStorage($target_type);
      $this->entity = $storage->load($this->getValue());
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    $data_type = $this->getDataDefinition()->getSetting('data_type');
    $value = $this->getValue();
    if ($data_type === 'integer') {
      return (int) $value;
    }
    return (string) $value;
  }

}