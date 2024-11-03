<?php

namespace Drupal\freezer_graph\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;

class FreezerGraphSubscriber implements EventSubscriberInterface {

  protected $state;
  protected $loggerFactory;
  protected $dateFormatter;
  protected $fileSystem;

  public function __construct(StateInterface $state, LoggerChannelFactoryInterface $logger_factory, DateFormatterInterface $date_formatter, FileSystemInterface $file_system) {
    $this->state = $state;
    $this->loggerFactory = $logger_factory;
    $this->dateFormatter = $date_formatter;
    $this->fileSystem = $file_system;
  }

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
      $this->loggerFactory->get('freezer_graph')->debug('Processing request for /api/value');

      // Store the current timestamp in the state system
      $this->state->set('freezer_graph.last_hit_time', time());

      // Rest of your logging code
      $request_data = $request->request->all();
      $current_time = $this->dateFormatter->format(time(), 'custom', 'Y-m-d H:i:s');

      // Prepare the log entry
      $hit_count = $this->state->get('freezer_graph.hit_count', 0) + 1;
      $this->state->set('freezer_graph.hit_count', $hit_count);

      $log_entry = sprintf("Endpoint hit count: %d, Time: %s, Data: %s\n", $hit_count, $current_time, json_encode($request_data));

      // Write the log entry to a file
      $log_file_path = 'private://freezer_graph_log.log';
      $real_log_file_path = $this->fileSystem->realpath($log_file_path);
      $directory_path = dirname($real_log_file_path);

      $this->loggerFactory->get('freezer_graph')->debug('Log file path: @path', ['@path' => $real_log_file_path]);
      $this->loggerFactory->get('freezer_graph')->debug('Directory path: @path', ['@path' => $directory_path]);

      // Ensure the directory exists
      if ($this->fileSystem->prepareDirectory($directory_path, FileSystemInterface::CREATE_DIRECTORY)) {
        $this->loggerFactory->get('freezer_graph')->debug('Directory prepared: @path', ['@path' => $directory_path]);
        if (file_put_contents($real_log_file_path, $log_entry, FILE_APPEND | LOCK_EX) === false) {
          $this->loggerFactory->get('freezer_graph')->error('Failed to write to log file: @path', ['@path' => $real_log_file_path]);
        } else {
          $this->loggerFactory->get('freezer_graph')->debug('Log entry written to file: @path', ['@path' => $real_log_file_path]);
        }
      } else {
        $this->loggerFactory->get('freezer_graph')->error('Failed to prepare directory: @path', ['@path' => $directory_path]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('date.formatter'),
      $container->get('file_system')
    );
  }
}
