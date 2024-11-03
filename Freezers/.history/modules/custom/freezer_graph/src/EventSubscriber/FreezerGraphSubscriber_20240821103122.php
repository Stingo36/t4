<?php

namespace Drupal\freezer_graph\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;

class FreezerGraphSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Logs request to the endpoint.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($path === '/api/value') {
      \Drupal::logger('freezer_graph')->debug('Processing request for /api/value');

      $config = \Drupal::service('config.factory')->getEditable('freezer_graph.settings');
      $hit_count = $config->get('hit_count') ?? 0;
      $hit_count++;
      $config->set('hit_count', $hit_count)->save();

      $request_data = $request->request->all();
      $current_time = \Drupal::service('date.formatter')->format(time(), 'custom', 'Y-m-d H:i:s');

      // Prepare the log entry
      $log_entry = sprintf("Endpoint hit count: %d, Time: %s, Data: %s\n", $hit_count, $current_time, json_encode($request_data));

      // Write the log entry to a file
      $log_file_path = 'private://freezer_graph_log.log';
      $file_system = \Drupal::service('file_system');
      $real_log_file_path = $file_system->realpath($log_file_path);
      $directory_path = dirname($real_log_file_path);

      \Drupal::logger('freezer_graph')->debug('Log file path: @path', ['@path' => $real_log_file_path]);
      \Drupal::logger('freezer_graph')->debug('Directory path: @path', ['@path' => $directory_path]);

      // Ensure the directory exists
      if ($file_system->prepareDirectory($directory_path, FileSystemInterface::CREATE_DIRECTORY)) {
        \Drupal::logger('freezer_graph')->debug('Directory prepared: @path', ['@path' => $directory_path]);
        if (file_put_contents($real_log_file_path, $log_entry, FILE_APPEND | LOCK_EX) === false) {
          \Drupal::logger('freezer_graph')->error('Failed to write to log file: @path', ['@path' => $real_log_file_path]);
        } else {
          \Drupal::logger('freezer_graph')->debug('Log entry written to file: @path', ['@path' => $real_log_file_path]);
        }
      } else {
        \Drupal::logger('freezer_graph')->error('Failed to prepare directory: @path', ['@path' => $directory_path]);
      }
    }
  }
}
