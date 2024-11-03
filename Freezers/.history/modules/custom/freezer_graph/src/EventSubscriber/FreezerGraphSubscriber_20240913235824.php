<?php

namespace Drupal\freezer_graph\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    return [
      KernelEvents::REQUEST => ['onRequest', 100],
    ];
  }

  /**
   * Logs request to the endpoint.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($path === '/api/value') {
      $logger = $this->loggerFactory->get('freezer_graph');
      $logger->debug('Processing request for /api/value');

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

      $logger->debug('Log file path: @path', ['@path' => $real_log_file_path]);
      $logger->debug('Directory path: @path', ['@path' => $directory_path]);

      // Ensure the directory exists
      if ($this->fileSystem->prepareDirectory($directory_path, FileSystemInterface::CREATE_DIRECTORY)) {
        $logger->debug('Directory prepared: @path', ['@path' => $directory_path]);
        if (file_put_contents($real_log_file_path, $log_entry, FILE_APPEND | LOCK_EX) === false) {
          $logger->error('Failed to write to log file: @path', ['@path' => $real_log_file_path]);
        } else {
          $logger->debug('Log entry written to file: @path', ['@path' => $real_log_file_path]);
        }
      } else {
        $logger->error('Failed to prepare directory: @path', ['@path' => $directory_path]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Not necessary when using the services.yml file to inject dependencies
    // But if you prefer to keep it, ensure it's consistent with the service definition
    return new static(
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('date.formatter'),
      $container->get('file_system')
    );
  }
}
