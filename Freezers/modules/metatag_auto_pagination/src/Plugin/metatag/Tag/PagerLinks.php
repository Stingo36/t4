<?php

namespace Drupal\metatag_auto_pagination\Plugin\metatag\Tag;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The standard page title.
 *
 * @MetatagTag(
 *   id = "metatag_auto_pager_link",
 *   label = @Translation("Auto pager link"),
 *   description = @Translation("Add pager next and prev metatag."), name = "metatag_auto_pager_link", group =
 *   "basic", weight = 10, type = "label", secure = FALSE, multiple = FALSE
 * )
 */
class PagerLinks extends LinkRelBase {

  use StringTranslationTrait;

  /**
   * Status.
   *
   * @const string
   */
  public const FIELD_STATUS = 'status';

  /**
   * Filter.
   *
   * @const string
   */
  public const FIELD_FILTER = 'filter';

  /**
   * Allowed parameters.
   *
   * @const string
   */
  public const FIELD_ALLOWED_QUERY_PARAMETERS = 'allowed_query_parameters';

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $details = [
      '#type' => 'details',
      '#title' => $this->t('Auto pager links'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $values = $this->value();
    $details[static::FIELD_STATUS] = [
      '#type' => 'select',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      '#default_value' => $values[static::FIELD_STATUS] ?? FALSE,
      '#required' => $element['#required'] ?? FALSE,
    ];

    $details[static::FIELD_FILTER] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter query parameters'),
      '#default_value' => $values[static::FIELD_FILTER] ?? FALSE,
    ];
    $details[static::FIELD_ALLOWED_QUERY_PARAMETERS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed url parameters'),
      '#default_value' => $values[static::FIELD_ALLOWED_QUERY_PARAMETERS] ?? '',
      '#description' => $this->t('Add list of url parameters that can be kept. If none define, all parameters are allowed.'),
      '#states' => [
        'visible' => [
          ':input[name*="' . static::FIELD_FILTER . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $details;
  }

  /**
   * Redefine the link prev and next.
   *
   * @return array|string
   *   The output.
   */
  public function output(): array {
    $output = [];
    $enabled = $this->value()[static::FIELD_STATUS] ?? NULL;
    if ($enabled === 'enabled') {
      $output = [
        '#tag' => 'link',
        '#attributes' => [
          'rel' => $this->name(),
          'href' => Json::encode($this->value()),
        ],
      ];
    }
    return $output;
  }

}
