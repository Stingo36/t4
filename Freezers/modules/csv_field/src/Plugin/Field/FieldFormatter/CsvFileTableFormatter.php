<?php

namespace Drupal\csv_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation of the 'csv_table' formatter.
 *
 * @FieldFormatter(
 *   id = "csv_file_table",
 *   label = @Translation("Render CSV file as table"),
 *   field_types = {
 *     "csv_file",
 *   }
 * )
 */
class CsvFileTableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['display_as_datatable'] = TRUE;
    $settings['use_description_as_link_text'] = TRUE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['display_as_datatable'] = [
      '#title' => $this->t('Display as DataTable'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_as_datatable'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('display_as_datatable')) {
      $summary[] = $this->t('Displayed as DataTable');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = [
        '#theme' => 'csv_table',
        '#file' => $file,
        '#description' => $this->getSetting('use_description_as_link_text') ? $item->description : NULL,
        '#attributes' => ['class' => 'csv-table hidden'],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      if ($this->getSetting('display_as_datatable')) {
        $elements[$delta]['#attributes']['class'] .= ' dataTable display';
      }

      $settings = $item->settings;

      if (isset($settings['urls'])) {
        foreach ($settings['urls'] as $key => $value) {
          $settings[$key] = $value;
        }
      }

      // Whether to open autolinked URLs in a new window.
      $settings['autolinkNewWindow'] = 0;
      $settings['lengthMenu'] = [5, 10, 25, 50, 75, 100, 200];
      $settings['stateSave'] = 1;
      // Set the default persistence to one day.
      $settings['stateDuration'] = 60 * 60 * 24;

      if ($settings['download'] === 0 && array_key_exists('downloadText', $settings)) {
        unset($settings['downloadText']);
      }
      // Turn off initial column sorting.
      $settings['order'] = [];

      if ($settings['responsive'] === 'horizontal_scroll') {
        $settings['scrollX'] = TRUE;
        unset($settings['responsive']);
      }

      $elements[$delta]['#settings'] = $settings;
      $elements[$delta]['#attributes']['data-settings'] = Json::encode($settings);
    }

    return $elements;
  }

}
