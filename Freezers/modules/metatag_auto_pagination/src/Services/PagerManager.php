<?php

namespace Drupal\metatag_auto_pagination\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\metatag_auto_pagination\Plugin\metatag\Tag\PagerLinks;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manager of pagination.
 */
class PagerManager {

  /**
   * Service ID.
   *
   * @const string
   */
  public const SERVICE_ID = 'metatag_auto_pagination.pager_manager';

  /**
   * Content data : Content EMPTY.
   *
   * @const string
   */
  public const CONTENT_DATA_EMPTY_CONTENT = 'empty_content';

  /**
   * Plugin ID.
   *
   * @const string
   */
  public const PLUGIN_ID = 'metatag_auto_pager_link';

  /**
   * Pager data.
   *
   * @var array|null
   */
  protected ?array $pager = NULL;

  /**
   * Content data.
   *
   * @var array
   */
  protected array $contentData = [];

  /**
   * Attachment tools.
   *
   * @var \Drupal\metatag_auto_pagination\Services\AttachmentTools
   */
  protected AttachmentTools $attachmentTools;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Constructor.
   *
   * @param \Drupal\metatag_auto_pagination\Services\AttachmentTools $attachment_tools
   *   The Attachment tools.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    AttachmentTools $attachment_tools,
    RequestStack $request_stack,
  ) {
    $this->attachmentTools = $attachment_tools;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Singleton quick access.
   *
   * @return static
   *   Singleton.
   */
  public static function instance(): static {
    return \Drupal::service(static::SERVICE_ID);
  }

  /**
   * Stock the pager for later use.
   *
   * @param array $variables
   *   Add a pager in storage.
   */
  public function addCurrentPager(array &$variables) {
    if (array_key_exists('items', $variables)) {

      // Redefine the first item to not add page=0.
      $this->initFirstPageLink($variables);

      $this->pager = $variables['items'];
    }
  }

  /**
   * Return true of there is a pager in current page.
   *
   * @return bool
   *   True if page has pager.
   */
  public function hasPager(): bool {
    return isset($this->pager);
  }

  /**
   * Init the pager headers attachments.
   *
   * @param array $tags
   *   List of tags.
   */
  public function initHeaderAttachment(array &$tags) {
    if ($this->hasPager() && $pager_link = $this->attachmentTools->getElementsInAttachment(static::PLUGIN_ID, $tags)) {
      $this->initPrevNextLinks($pager_link, $tags);
    }
    else {
      $tags = array_filter($tags, function ($tag) {
        return !in_array(static::PLUGIN_ID, $tag);
      });
    }
  }

  /**
   * Init pager links.
   *
   * @param mixed $pager_link
   *   The pager link.
   * @param array $tags
   *   List of tags.
   */
  public function initPrevNextLinks($pager_link, array &$tags) {
    try {
      $data = Json::decode(reset($pager_link)[0]['#attributes']['href']);
      $data[PagerLinks::FIELD_ALLOWED_QUERY_PARAMETERS] = explode(PHP_EOL, $data[PagerLinks::FIELD_ALLOWED_QUERY_PARAMETERS]);
    }
    catch (\Exception $e) {
      $data = NULL;
    }

    // Delete the pager link..
    $page_link_id = array_keys($pager_link);
    unset($tags[reset($page_link_id)]);
    $this->initRelation($tags, 'next', 'next', $data);
    $this->initRelation($tags, 'previous', 'prev', $data);
    $this->addCanonical($tags, $this->request->getSchemeAndHttpHost() . $this->request->getRequestUri());
  }

  /**
   * Add a rel at the output.
   *
   * @param array $output
   *   The output.
   * @param string $rel
   *   The rel.
   * @param string $href
   *   The href value.
   */
  public function addLinkToOutput(array &$output, string $rel, string $href) {
    $output[] = [
      [
        '#tag' => 'link',
        '#attributes' => [
          'rel' => $rel,
          'href' => $href,
        ],
      ],
      'pager_link_' . $rel,
    ];
  }

  /**
   * Adds a canonical.
   *
   * @param array $output
   *   The output.
   * @param string $href
   *   The href value.
   */
  public function addCanonical(array &$output, string $href) {
    $output[] = [
      [
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'canonical',
          'href' => $href,
        ],
      ],
      'canonical_url',
    ];
  }

  /**
   * Add the next item if needed.
   *
   * @param array $output
   *   The output.
   * @param mixed $pager_type
   *   The pager type.
   * @param mixed $type
   *   The type.
   * @param array $data
   *   The data.
   */
  protected function initRelation(array &$output, $pager_type, $type, array $data = []) {
    // If there is a previous page.
    if (array_key_exists($pager_type, $this->pager)) {
      $query = $this->pager[$pager_type]['href'];
      $query = isset($data[PagerLinks::FIELD_FILTER]) ? $this->filterQueryParams($query, $data[PagerLinks::FIELD_ALLOWED_QUERY_PARAMETERS]) : $query;
      // Force absolute.
      if (str_starts_with($this->pager[$pager_type]['href'], '?')) {
        $this->addLinkToOutput($output, $type, $this->getBaseUrl() . $query);
      }
      else {
        $this->addLinkToOutput($output, $type, $query);
      }
    }
  }

  /**
   * Return the full base url.
   *
   * @return string
   *   The base url.
   */
  protected function getBaseUrl() {
    return $this->request->getSchemeAndHttpHost() . $this->request->getPathInfo();
  }

  /**
   * Init the first page link to avoid page=0.
   *
   * @param array $variables
   *   The variables.
   */
  protected function initFirstPageLink(array &$variables) {
    $elems_to_check = [
      ['items', 'previous'],
      ['items', 'first'],
      ['items', 'pages', '1'],
    ];

    foreach ($elems_to_check as $path) {
      if ($item = &NestedArray::getValue($variables, $path)) {
        $href = $item['href'];
        $url = parse_url($href);
        if (array_key_exists('query', $url)) {
          parse_str($url['query'], $query);
          if (array_key_exists('page', $query) && $query['page'] === '0') {
            unset($query['page']);
          }
          $item['href'] = $this->getBaseUrl() . (empty($query) ? '' : '?' . http_build_query($query));
        }
      }
    }
  }

  /**
   * Filter query parameters.
   *
   * @param string $query
   *   The query.
   * @param array $allowed_query_parameters
   *   The allowed query parameters.
   */
  protected function filterQueryParams(string $query, array $allowed_query_parameters) {
    if (!in_array('page', $allowed_query_parameters)) {
      $allowed_query_parameters[] = 'page';
    }

    $query_data = parse_url($query);
    $params = [];
    parse_str($query_data['query'], $params);
    $params = array_filter($params);
    $params = array_intersect_key($params, array_flip($allowed_query_parameters));
    $query_data['query'] = '';

    return implode('', $query_data) . (count($params) ? '?' . http_build_query($params) : '');
  }

  /**
   * Store content data values.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   */
  public function setContentData($key, $value = TRUE) {
    $this->contentData[$key] = $value;
    return $this;
  }

}
