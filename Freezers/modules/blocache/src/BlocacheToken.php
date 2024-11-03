<?php

namespace Drupal\blocache;

use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\user\UserInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Class to handle tokens in cache tags.
 */
class BlocacheToken {

  /**
   * The 'token' service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The 'current_user' service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The 'current_route_match' service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The 'entity_type.manager' service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token types.
   *
   * @var string[]
   */
  protected $tokenTypes = [];

  /**
   * The types of tokens allowed.
   *
   * @var string[]
   */
  private static $allowedTokenTypes = [
    'current-user',
    'user',
    'profile',
    'node',
    'term',
    'vocabulary',
    'view',
  ];

  /**
   * Constructs a \Drupal\blocache\BlocacheToken object.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The 'token' service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The 'current_user' service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The 'current_route_match' service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The 'entity_type.manager' service.
   */
  public function __construct(
    Token $token_service,
    AccountProxy $current_user,
    CurrentRouteMatch $current_route_match,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->tokenService = $token_service;
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;

    // Sets the $tokenTypes property.
    $token_types = array_keys($this->tokenService->getInfo()['types']);
    $this->tokenTypes = array_intersect(self::$allowedTokenTypes, $token_types);
  }

  /**
   * Gets de $tokenTypes property.
   *
   * @return string[]
   *   Returns the list of token types.
   */
  public function getTokenTypes(): array {
    return $this->tokenTypes;
  }

  /**
   * Replaces tokens in a text.
   *
   * @param string $text
   *   The tokenized text to be replaced.
   *
   * @return string
   *   Returns the text with the replaced tokens.
   */
  public function replace(string $text): string {
    $replaced = $text;

    if ($data = $this->data($text)) {
      $replaced = $this->tokenService->replace($text, $data);

      // Ensure that there are no double-slash sequences due to empty token
      // values.
      $replaced = preg_replace('/(?<!:)(?<!)\/+\//', '/', $replaced);
    }
    // If you enter a token that cannot be replaced, then the text is removed.
    elseif ($this->tokenService->scan($text)) {
      $replaced = '';
    }

    return $replaced;
  }

  /**
   * Replaces tokens in a text list.
   *
   * @param string[] $texts
   *   The list of texts with tokens to be replaced.
   *
   * @return string[]
   *   Returns a list of texts with the replaced tokens.
   */
  public function replaceAll(array $texts): array {
    $replacements = [];

    foreach ($texts as $text) {
      $replaced = $this->replace($text);
      if (!empty($replaced)) {
        $replacements[] = $replaced;
      }
    }

    return $replacements;
  }

  /**
   * Gets the data to replace according to the context.
   *
   * @param string $text
   *   The tokenized text.
   *
   * @return object[]|null
   *   Returns a list of objects with data to replace the text tokens.
   */
  protected function data(string $text): ?array {
    $data = [];

    if ($tokens = $this->tokenService->scan($text)) {
      foreach ($tokens as $token_type => $token) {
        switch ($token_type) {
          case 'current-user':
            $current_user = $this->entityTypeManager
              ->getStorage('user')
              ->load($this->currentUser->id());
            if ($current_user instanceof UserInterface) {
              $data[$token_type] = $current_user;
            }
            break;

          case 'user':
            $user = $this->currentRouteMatch->getParameter('user');
            if ($user instanceof UserInterface) {
              $data[$token_type] = $user;
            }
            break;

          case 'profile':
            $user = $this->currentRouteMatch->getParameter('user');
            $profile_type = $this->currentRouteMatch->getParameter('profile_type');
            if (
              $user instanceof UserInterface &&
              $profile_type instanceof ProfileTypeInterface
            ) {
              $profile = $this->entityTypeManager
                ->getStorage('profile')
                ->loadByUser($user, $profile_type->id());

              if ($profile instanceof ProfileInterface) {
                $data[$token_type] = $profile;
              }
            }
            break;

          case 'node':
            $node = $this->currentRouteMatch->getParameter('node');
            if ($node instanceof NodeInterface) {
              $data[$token_type] = $node;
            }
            break;

          case 'term':
            $term = $this->currentRouteMatch->getParameter('taxonomy_term');
            if ($term instanceof TermInterface) {
              $data[$token_type] = $term;
            }
            break;

          case 'vocabulary':
            $term = $this->currentRouteMatch->getParameter('taxonomy_term');
            if ($term instanceof TermInterface) {
              $vocabulary = $this->entityTypeManager
                ->getStorage('taxonomy_vocabulary')
                ->load($term->bundle());
              if ($vocabulary instanceof VocabularyInterface) {
                $data[$token_type] = $vocabulary;
              }
            }
            break;

          case 'view':
            $view_id = $this->currentRouteMatch->getParameter('view_id');
            $display_id = $this->currentRouteMatch->getParameter('display_id');

            if ($view_id && $display_id) {
              $view = $this->entityTypeManager->getStorage('view')->load($view_id);

              if ($view instanceof ViewEntityInterface) {
                $executable_view = $view->getExecutable();

                if ($executable_view instanceof ViewExecutable) {
                  $executable_view->setDisplay($display_id);
                  $data[$token_type] = $executable_view;
                }
              }
            }
            break;
        }
      }
    }

    return $data;
  }

}
