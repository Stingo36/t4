<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'number_decimal' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "number_decimal",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class DecimalFormatter extends NumericFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $elements = parent::settingsForm($form, $form_state, $settings);
    $settings += static::defaultSettings();

    $visible = $form['#visibility_path'];
    $elements['decimal_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decimal marker'),
      '#options' => ['.' => $this->t('Decimal point'), ',' => $this->t('Comma')],
      '#default_value' => $settings['decimal_separator'],
      '#weight' => 5,
      '#states' => [
        'visible' => [
          'select[name="' . $visible . '[format_type]"]' => ['value' => 'number_decimal'],
        ],
      ],
    ];
    $elements['scale'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $settings['scale'],
      '#description' => $this->t('The number of digits to the right of the decimal.'),
      '#weight' => 6,
      '#states' => [
        'visible' => [
          'select[name="' . $visible . '[format_type]"]' => ['value' => 'number_decimal'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number, array $settings) {
    $settings += static::defaultSettings();
    return number_format($number, $settings['scale'], $settings['decimal_separator'], $settings['thousand_separator']);
  }

}