<?php

namespace Drupal\custom_charts\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Card Block' block.
 *
 * @Block(
 *   id = "card_block",
 *   admin_label = @Translation("Card Block"),
 * )
 */
class CardBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build the HTML content directly within the block.
    $content = '<div class="row">';

    $cards = $this->getCardData();
    foreach ($cards as $card) {
      $content .= '<div class="col-12 mb-4">'; // Full-width column for all screen sizes
      $content .= '<div class="card text-white ' . $card['color_class'] . ' w-100 mb-3">'; // Card with full width
      $content .= '<div class="card-body">';
      $content .= '<div class="d-flex justify-content-between">';
      $content .= '<div>';
      $content .= '<h5 class="card-title">' . $card['title'] . '</h5>';
      $content .= '<p class="card-text">' . $card['text'] . '</p>';
      $content .= '</div>';
      $content .= '<div class="icon">';
      $content .= '<i class="fa ' . $card['icon'] . '"></i>';
      $content .= '</div>';
      $content .= '</div>'; // Close d-flex justify-content-between
      $content .= '<div class="numbers">';
      $content .= '<h3>' . $card['number'] . '</h3>';
      $content .= '<small>' . $card['small_text'] . '</small>';
      $content .= '</div>'; // Close numbers
      $content .= '</div>'; // Close card-body
      $content .= '</div>'; // Close card
      $content .= '</div>'; // Close col-12
    }

    $content .= '</div>'; // Close row

    // Return the content as a render array.
    return [
      '#markup' => $content,
      '#attached' => [
        'library' => [
          'custom_charts/card_block_library',
        ],
      ],
    ];
  }

  /**
   * Provide mock data for the cards.
   */
  protected function getCardData() {
    return [
      [
        'title' => 'Orders Received',
        'text' => 'Completed Orders',
        'icon' => 'fa-shopping-cart',
        'number' => '486',
        'small_text' => '351',
        'color_class' => 'bg-primary',
      ],
      // Add more card data as needed.
    ];
  }

}
