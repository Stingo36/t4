<?php

namespace Drupal\csv_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "csv_file",
 *   label = @Translation("CSV File"),
 *   description = @Translation("This field stores the ID of a CSV file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "csv_file_generic",
 *   default_formatter = "csv_file_table",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class CsvFileItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'csv',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $element['file_extensions']['#disabled'] = TRUE;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['settings'] = [
      'type' => 'text',
      'size' => 'big',
      'description' => 'settings to pass to the display.',
      'not null' => FALSE,
      'serialize' => TRUE,
    ];
    return $schema;
  }

}
