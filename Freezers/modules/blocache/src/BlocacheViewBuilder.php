<?php

namespace Drupal\blocache;

use Drupal\block\BlockViewBuilder;
use Drupal\Core\Cache\Cache;

/**
 * Provides a Block view builder.
 */
class BlocacheViewBuilder extends BlockViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = parent::viewMultiple($entities, $view_mode, $langcode);
    $blocache = \Drupal::service('blocache');
    $blocache_metadata = $blocache->getMetadataService();
    $blocache_token = $blocache->getTokenService();

    foreach ($entities as $entity) {
      $blocache_metadata->setBlock($entity);

      if ($blocache_metadata->isOverridden()) {
        $metadata = $blocache_metadata->getMetadata();

        if ($blocache_token) {
          $metadata['tags'] = $blocache_token->replaceAll($metadata['tags']);
        }

        $entity_id = $entity->id();
        $build_cache =& $build[$entity_id]['#cache'];
        $build_cache['max-age'] = $metadata['max-age'];
        $build_cache['contexts'] = Cache::mergeContexts($metadata['contexts'], $build_cache['contexts']);
        $build_cache['tags'] = Cache::mergeTags($metadata['tags'], $build_cache['tags']);

        // This disable page caching wherever this block has been placed.
        if ($metadata['max-age'] === 0) {
          \Drupal::service('page_cache_kill_switch')->trigger();
        }
      }
    }

    return $build;
  }

}
