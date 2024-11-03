<?php

namespace Drupal\custom_view_filters\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_range_picker_filter")
 */
class DateRangePickerFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function canBuildGroup() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $form['value'] = !empty($form['value']) ? $form['value'] : [];
    parent::buildExposedForm($form, $form_state);
    $filter_id = $this->getFilterId();
    // Field which really filters.
    $form[$filter_id] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    // Auxiliary fields.
    $form['exposed_from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Since'),
      '#default_value' => $this->options['exposed_from_date'] ?? NULL,
    ];

    $form['exposed_to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Until'),
      '#default_value' => $this->options['exposed_to_date'] ?? NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    $input[$this->options['expose']['identifier']] = $input['exposed_from_date'] . '_' . $input['exposed_to_date'];

    $rc = parent::acceptExposedInput($input);

    return $rc;
  }

  /**
   * This method returns the ID of the fake field which contains this plugin.
   *
   * It is important to put this ID to the exposed field of this plugin for the
   * following reasons: a) To avoid problems with
   * FilterPluginBase::acceptExposedInput function b) To allow this filter to
   * be printed on twig templates with {{ form.date_range_picker_filter }}
   *
   * @return string
   *   ID of the field which contains this plugin.
   */
  private function getFilterId() {
    return $this->options['expose']['identifier'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    if (!$this->options['exposed']) {
      // Administrative value.
      $this->queryFilter($this->options['granular_field_name'], $this->options['node_from_date'] . '_' . $this->options['node_to_date']);
    }
    else {
      // Exposed value.
      if (empty($this->value) || empty($this->value[0])) {
        return;
      }

      $this->queryFilter($this->options['granular_field_name'], $this->value[0]);
    }
  }

  /**
   * Filters by given year and month.
   *
   * @param string $fieldName
   *   Machine name of the field.
   * @param string $dates
   *   Date from and date to.
   */
  private function queryFilter($fieldName, $dates) {

    $array_date = explode("_", $dates);
    $dateFrom = $array_date[0];
    $dateTo = $array_date[1];
    $dateFrom = $this->securityFilter($dateFrom);
    $dateTo = $this->securityFilter($dateTo);

    $hasDateFrom = TRUE;
    $hasDateTo = TRUE;

    if ($dateFrom == '') {
      $hasDateFrom = FALSE;
    }
    if ($dateTo == '') {
      $hasDateTo = FALSE;
    }

    if ($hasDateFrom && $hasDateTo) {
      if ($fieldName == 'created' || $fieldName == 'changed') {
        $firstTime = strtotime($dateFrom . ' 00:00:00');
        $lastTime = strtotime($dateTo . ' 23:59:59');
        $this->query->addTable("node__field_data");
        $this->query->addWhere("AND", "node_field_data.{$fieldName}", $firstTime, ">=");
        $this->query->addWhere("AND", "node_field_data.{$fieldName}", $lastTime, "<=");
      }
      else {

        $this->query->addTable("node__{$fieldName}");
        $this->query->addWhere("AND", "node__{$fieldName}.{$fieldName}_value", $dateFrom, ">=");
        $this->query->addWhere("AND", "node__{$fieldName}.{$fieldName}_value", $dateTo, "<=");
      }
    }
    elseif ($hasDateFrom && !$hasDateTo) {
      if ($fieldName == 'created' || $fieldName == 'changed') {

        $firstTime = strtotime($dateFrom . ' 00:00:00');
        $this->query->addTable("node__field_data");
        $this->query->addWhere("AND", "node_field_data.{$fieldName}", $firstTime, ">=");
      }
      else {
        $this->query->addTable("node__{$fieldName}");
        $this->query->addWhere("AND", "node__{$fieldName}.{$fieldName}_value", $dateFrom, ">=");
      }
    }
    elseif (!$hasDateFrom && $hasDateTo) {
      if ($fieldName == 'created' || $fieldName == 'changed') {
        $lastTime = strtotime($dateTo . ' 23:59:59');

        $this->query->addTable("node__field_data");
        $this->query->addWhere("AND", "node_field_data.{$fieldName}", $lastTime, "<=");
      }
      else {
        $this->query->addTable("node__{$fieldName}");
        $this->query->addWhere("AND", "node__{$fieldName}.{$fieldName}_value", $dateTo, "<=");
      }
    }

  }

  /**
   * Security filter.
   *
   * @param mixed $value
   *   Input.
   *
   * @return mixed
   *   Sanitized value of input.
   */
  private function securityFilter($value) {
    $value = Html::escape($value);
    $value = Xss::filter($value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (!$this->options['exposed']) {

      $form['node_from_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Since'),
        '#default_value' => $this->options['node_from_date'] ?? NULL,
      ];

      $form['node_to_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Until'),
        '#default_value' => $this->options['node_to_date'] ?? NULL,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['node_from_date'] = ['default' => ''];
    $options['node_to_date'] = ['default' => ''];
    $options['granular_field_name'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['granular_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use Date range picker filter with this field name (enter machine name)'),
      '#description' => $this->t('Machine field names appear on content types field list (e.g. field_build_date). You can also use "created" and "changed" properties.'),
      '#default_value' => $this->options['granular_field_name'] ?? NULL,
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    // Exposed filter.
    if ($this->options['exposed']) {
      $variables = [
        '@field' => $this->options['granular_field_name'],
      ];
      return $this->t('Exposed on field "@field"', $variables);
    }

    // Administrative filter.
    $variables = [
      '@since' => $this->options['node_from_date'],
      '@until' => $this->options['node_to_date'],
      '@field' => $this->options['granular_field_name'],
    ];
    return ($this->t('Filter on field "@field" [@since (since) - @until (until)]', $variables));
  }

}
