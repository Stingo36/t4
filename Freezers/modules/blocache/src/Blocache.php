<?php

namespace Drupal\blocache;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service class 'blocache'.
 */
class Blocache {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a \Drupal\blocache\Blocache object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * Gets the 'blocache.metadata' service.
   *
   * @return \Drupal\blocache\BlocacheMetadata
   *   Returns the 'blocache.metadata' service.
   */
  public function getMetadataService(): BlocacheMetadata {
    return $this->container->get('blocache.metadata');
  }

  /**
   * Gets the 'blocache.token' service.
   *
   * @return \Drupal\blocache\BlocacheToken|null
   *   Returns the service 'blocache.token' or NULL, if the
   *   token module is not installed.
   */
  public function getTokenService(): ?BlocacheToken {
    if ($this->container->get('module_handler')->moduleExists('token')) {
      return $this->container->get('blocache.token');
    }

    return NULL;
  }

  /**
   * Gets the available cache contexts.
   *
   * @return array
   *   Returns the available cache contexts.
   */
  public function cacheContexts() {
    $contexts = [];

    $services = $this->container->getServiceIds();
    foreach ($services as $id) {
      if (strpos($id, 'cache_context.') !== 0) {
        continue;
      }

      $service = $this->container->get($id);
      $class_name = get_class($service);
      $class = new \ReflectionClass($class_name);
      $parameters = $class->getMethod('getContext')->getParameters();

      $params = [];
      foreach ($parameters as $param) {
        $params[] = $param->name;
      }

      $context = substr($id, 14);
      $contexts[$context] = [
        'id' => $context,
        'params' => $params,
      ];
    }

    return $contexts;
  }

  /**
   * Prepares the cache contexts for storage into third-party setting.
   *
   * @param array $values
   *   The context values from block configuration.
   *
   *   The array must have key => value pairs respecting the standard:
   *     - {cache_context_name} => 'cache context name'
   *     - {cache_context_name}_arg => 'cache context argument'.
   *
   * @return array
   *   Returns an array with values respecting the standard:
   *   {cache_context_name}:{cache_context_name_arg}
   */
  public function prepareContextsToStorage(array $values) {
    $contexts = [];

    $i = 0;
    foreach ($values as $key => $value) {
      $count = strlen('__arg');
      if ((substr($key, -$count) === '__arg')) {
        continue;
      }

      if ($value === 1) {
        $contexts[$i] = $key;

        if ($arg = $values[$key . '__arg']) {
          $contexts[$i] .= ':' . $arg;
        }
        $i++;
      }
    }

    return $contexts;
  }

  /**
   * Prepares cache contexts to display them on the block configuration form.
   *
   * @param array $values
   *   The contexts from third-party setting. An array with the following
   *   pattern of values: {cache_context_name}:{cache_context_name_arg}.
   *
   * @return array
   *   Returns an array with key => value pairs respecting the standard:
   *   {cache_context_name} => {cache_context_name_arg}.
   */
  public function prepareContextsFromStorage(array $values) {
    $contexts = [];

    foreach ($values as $value) {
      $value = explode(':', $value);
      $contexts[$value[0]] = $value[1] ?? '';
    }

    return $contexts;
  }

}
