<?php 

namespace Drupal\custom_charts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'ChartBlock' block.
 *
 * @Block(
 *   id = "chart_block",
 *   admin_label = @Translation("Chart Block"),
 * )
 */
class ChartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $options = [];

    // Check if the current user has the 'faculty' role
    if ($current_user->hasRole('faculty')) {
      $user = \Drupal\user\Entity\User::load($current_user->id());
      $field_name = 'field_freezer_name_ref';

      if ($user->hasField($field_name)) {
        $referenced_entities = $user->get($field_name)->referencedEntities();

        foreach ($referenced_entities as $entity) {
          $options[$entity->label()] = $entity->label();
        }
      } else {
        \Drupal::messenger()->addError("Field '$field_name' not found on user entity.");
      }
    }

    if (empty($options)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'freezer_names')
        ->condition('status', 1)
        ->accessCheck(FALSE);
      $nids = $query->execute();

      if (!empty($nids)) {
        $nodes = Node::loadMultiple($nids);
        foreach ($nodes as $node) {
          $options[$node->getTitle()] = $node->getTitle();
        }
      } else {
        $options['none'] = 'None';
      }
    }

    // Build filter buttons with custom styles and centered alignment.
    $filter_buttons = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filter-buttons', 'mt-3', 'd-flex', 'justify-content-center', 'd-none']], // Center buttons and hide initially
      '1hr' => [
        '#type' => 'button',
        '#value' => $this->t('1hr'),
        '#attributes' => [
          'id' => 'filter-button-1hr',
          'data-duration' => '1hr',
          'class' => ['btn', 'custom-btn'], // Custom styling class
        ],
      ],
      '3hr' => [
        '#type' => 'button',
        '#value' => $this->t('3hr'),
        '#attributes' => [
          'id' => 'filter-button-3hr',
          'data-duration' => '3hr',
          'class' => ['btn', 'custom-btn'],
        ],
      ],
      '6hr' => [
        '#type' => 'button',
        '#value' => $this->t('6hr'),
        '#attributes' => [
          'id' => 'filter-button-6hr',
          'data-duration' => '6hr',
          'class' => ['btn', 'custom-btn'],
        ],
      ],
      '12hr' => [
        '#type' => 'button',
        '#value' => $this->t('12hr'),
        '#attributes' => [
          'id' => 'filter-button-12hr',
          'data-duration' => '12hr',
          'class' => ['btn', 'custom-btn'],
        ],
      ],
      '24hr' => [
        '#type' => 'button',
        '#value' => $this->t('24hr'),
        '#attributes' => [
          'id' => 'filter-button-24hr',
          'data-duration' => '24hr',
          'class' => ['btn', 'custom-btn', 'selected-btn'], // Add 'selected-btn' class by default
        ],
      ],
      'All' => [
        '#type' => 'button',
        '#value' => $this->t('All'),
        '#attributes' => [
          'id' => 'filter-button-All',
          'data-duration' => 'All',
          'class' => ['btn', 'custom-btn'],
        ],
      ],
    ];

    // Render the block content with a Bootstrap card layout
    return [
      'chart_card' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['card', 'w-100']],
        'header' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['card-header']],
          'status' => [
            '#markup' => '<div id="Status" class="statusStyle"><h2 class="text-center"> Please select Location and Floor Dropdown Options </h2></div>',
          ],
          'title_and_trigger' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['d-flex', 'justify-content-between', 'align-items-center']],
            'title' => [
              '#markup' => '<h2 id="freezer-title" class="mb-0" style="display: none;"></h2>',
            ],
            'trigger' => [
              '#markup' => '<div id="buttonTrigger"></div>',
            ],
          ],
        ],
        'body' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['card-body']],
          'chart_container' => [
            '#markup' => '<div id="chart-container"><div id="chartdiv" style="width: 100%; height: 400px;"></div></div>',
          ],
          'freezer_data_container' => [
            '#markup' => '<div id="freezer-data-container" style="margin-top: 20px;"></div>',
          ],
          'filter_buttons' => $filter_buttons, // Add filter buttons inside the card body
        ],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
          'custom_charts/custom_charts',
        ],
        'drupalSettings' => [
          'custom_charts' => [
            'showButtons' => FALSE, // Default state
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}