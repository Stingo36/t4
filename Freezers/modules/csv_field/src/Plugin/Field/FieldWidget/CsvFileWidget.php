<?php

namespace Drupal\csv_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "csv_file_generic",
 *   label = @Translation("Csv File"),
 *   field_types = {
 *     "csv_file"
 *   }
 * )
 */
class CsvFileWidget extends FileWidget implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['updateSettingsAccess'];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Note: this changes the #process and #value_callback class to this class.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

  /**
   * Form API callback: Processes a csv_file_generic field element.
   *
   * Expands the csv_file_generic type to include the settings field.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    // Make sure FileWidget processing runs.
    $element = parent::process($element, $form_state, $form);
    $item = $element['#value'];

    $parents = $element['#parents'];
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', $parents) . ']';
    }

    $element['settings'] = [
      '#type' => 'details',
      '#title' => t('Display Configuration'),
      '#open' => FALSE,
      '#weight' => 2,
    ];

    $element['settings']['pageLength'] = [
      '#type' => 'select',
      '#title' => t('Initial Page Length'),
      '#options' => [
        5 => 5,
        10 => 10,
        25 => 25,
        50 => 50,
        75 => 75,
        100 => 100,
        200 => 200,
      ],
      '#default_value' => !empty($item['settings']['pageLength']) ? $item['settings']['pageLength'] : 10,
    ];

    $element['settings']['lengthChange'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow end user to change number of rows to display'),
      '#default_value' => !empty($item['settings']['lengthChange']) ? 1 : 0,
    ];

    $element['settings']['searching'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable searching'),
      '#default_value' => !empty($item['settings']['searching']) ? 1 : 0,
    ];

    $element['settings']['hideSearchingData'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide data until search is submitted'),
      '#default_value' => !empty($item['settings']['hideSearchingData']) ? 1 : 0,
    ];

    $element['settings']['download'] = [
      '#type' => 'checkbox',
      '#title' => t('Display a download link'),
      '#default_value' => !empty($item['settings']['download']) ? 1 : 0,
    ];

    $element['settings']['centerContent'] = [
      '#type' => 'checkbox',
      '#title' => t('Center table content'),
      '#default_value' => !empty($item['settings']['centerContent']) ? 1 : 0,
    ];

    $element['settings']['searchLabel'] = [
      '#type' => 'textfield',
      '#title' => t('Search prompt'),
      '#description' => t('Tell the user what to enter to search. Example: “Enter last name”'),
      '#default_value' => !empty($item['settings']['searchLabel']) ? $item['settings']['searchLabel'] : '',
      '#maxlength' => 100,
      '#element_validate' => [
        ['\Drupal\csv_field\Plugin\Field\FieldWidget\CsvFileWidget', 'validateSearchLabelRequired'],
      ],
      '#states' => [
        'visible' => [
          ':input[name^="' . $selector . '[settings][hideSearchingData]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name^="' . $selector . '[settings][hideSearchingData]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['settings']['responsive'] = [
      '#type' => 'radios',
      '#title' => t('Responsive Option'),
      '#options' => [
        'childRow' => t("Show fields on the left side of the table that will fit on the screen horizontally and have an <strong>expansion button</strong> for visitors to see other fields that don't fit stacked vertically below the row"),
        'childRowImmediate' => t("Show fields on the left side of the table that will fit on the screen horizontally and <strong>automatically show</strong> other fields that don't fit stacked vertically below the row"),
      ],
      '#default_value' => !empty($item['settings']['responsive']) ? $item['settings']['responsive'] : 'childRow',
    ];

    // Do not display description field as `Download Link Text` field overrides
    // it.
    if (!empty($element['description'])) {
      $element['settings']['downloadText'] = [
        '#type' => 'textfield',
        '#title' => t('Download Link Text'),
        '#default_value' => !empty($item['settings']['downloadText']) ? $item['settings']['downloadText'] : '',
        '#states' => [
          'visible' => [
            ':input[name^="' . $selector . '[settings][download]"]' => ['checked' => TRUE],
          ],
        ],
        '#description' => t('Leave this field blank to use filename.'),
      ];

      $element['description']['#access'] = FALSE;
    }

    $element['settings']['urls'] = [
      '#type' => 'details',
      '#title' => t('URLs'),
      '#open' => TRUE,
    ];

    // Provides backport compatibility.
    if (!empty($item['settings']['autolink'])) {
      $item['settings']['urls']['autolink'] = 1;
    }

    $element['settings']['urls']['autolink'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically Convert URLs to Links.'),
      '#description' => t('Provide descriptive link text for each URL in the CSV – this could be the business or website name. Arrange your columns in the CSV so that the link text is in the column directly to the left of the URL column.'),
      '#description_display' => 'after',
      '#default_value' => !empty($item['settings']['urls']['autolink']) ? 1 : 0,
    ];

    $element['settings']['urls']['urlColumnNumber'] = [
      '#type' => 'number',
      '#title' => t('URL column number'),
      '#description' => t('Enter the column number that contains the URL. Count columns from left, starting from 1.
<br><br>
Example: Your URLs are in the third column, so you would enter "3" in the column number field. Your link text column should then be in column 2.
<br><br>
The URL column will not be shown on the screen. Instead, the link text in the column directly to the left will be a clickable link to the URL.'),
      '#default_value' => !empty($item['settings']['urls']['urlColumnNumber']) ? $item['settings']['urls']['urlColumnNumber'] : NULL,
      '#min' => 2,
      '#states' => [
        'visible' => [
          ':input[name^="' . $selector . '[settings][urls][autolink]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;

  }

  /**
   * Form API callback. Retrieves the value for the csv_file field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // If this is a newly uploaded file, each setting will each be NULL.
    // We only need to check the responsive option, which should be a string.
    if (empty($return['settings']) || !$return['settings']['responsive']) {
      $return['settings'] = [
        'searching' => 1,
        'pageLength' => 5,
        'lengthChange' => 1,
        'responsive' => 'childRow',
        'download' => 1,
        'autolink' => 0,
      ];
    }
    else {
      // Some Drupal form elements convert integers to strings.
      // Assure integer values are saves as integers, not strings.
      $int_settings = [
        'searching',
        'hideSearchingData',
        'pageLength',
        'lengthChange',
        'download',
        'autolink',
      ];
      foreach ($int_settings as $key) {
        if (array_key_exists($key, $return['settings'])) {
          $return['settings'][$key] = (int) $return['settings'][$key];
        }
      }
    }

    if (array_key_exists('downloadText', $return['settings'])) {
      $return['description'] = $return['settings']['downloadText'];
    }

    return $return;
  }

  /**
   * Element validation handler to check if searchLabel is required.
   */
  public static function validateSearchLabelRequired($element, FormStateInterface $form_state, &$complete_form) {
    // Get the full array of values
    $values = $form_state->getValues();
    $parents = $element['#parents'];

    // Remove the last item, since it is the element name itself.
    array_pop($parents);
    // Traverse the nested structure based on the parents
    foreach ($parents as $parent) {
      if (isset($values[$parent])) {
        $values = $values[$parent];
      }
      else {
        $values = NULL;
        break;
      }
    }
    if (!empty($values)) {
      if (!empty($values['hideSearchingData']) && empty($element['#value'])) {
        $form_state->setError($element, t('The "Search prompt" is required when "Hide data until search is submitted" is checked.'));
      }
    }
  }

}
