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
use Drupal\Core\Messenger\MessengerInterface;

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
  public function onRequest(RequestEvent $event) {

    $logger = $this->loggerFactory->get('freezer_graph');

    $request = $event->getRequest();
    $path = $request->getPathInfo();
    
    if ($path === '/api/value') {
      $logger->debug('Processing request for /api/value');
  
      // Extract FreezerNames and their values from the request data
      $request_data = $request->request->all();
      $freezer_data = [];
      
      // Check if the freezer names and values are present in the request
      if (isset($request_data['data2']) && isset($request_data['data1'])) {
        $freezer_name = $request_data['data2']; // Freezer name from data2
        $freezer_value = $request_data['data1']; // Freezer value from data1
        $freezer_data[$freezer_name] = $freezer_value;
        $logger->debug('Freezer Name: @name, Value: @value', ['@name' => $freezer_name, '@value' => $freezer_value]);
      }
  
      if (isset($request_data['data4']) && isset($request_data['data3'])) {
        $freezer_name = $request_data['data4']; // Freezer name from data4
        $freezer_value = $request_data['data3']; // Freezer value from data3
        $freezer_data[$freezer_name] = $freezer_value;
        $logger->debug('Freezer Name: @name, Value: @value', ['@name' => $freezer_name, '@value' => $freezer_value]);
      }
        
      // Store the current timestamp and value for each FreezerName
      foreach ($freezer_data as $freezer_name => $freezer_value) {
        $state_key_time = 'freezer_graph.last_hit_time.' . $freezer_name;
        $this->state->set($state_key_time, time());
  
        $state_key_value = 'freezer_graph.last_value.' . $freezer_name;
        $this->state->set($state_key_value, $freezer_value); // Correctly set the freezer value
  
        // Log the state setting
        $logger->debug('Stored in state: Time Key: @time_key, Value Key: @value_key, Value: @value', [
          '@time_key' => $state_key_time,
          '@value_key' => $state_key_value,
          '@value' => $freezer_value
        ]);
      }
  
      // Optional: Store all FreezerNames in state (if needed elsewhere)
      $all_freezers = $this->state->get('freezer_graph.all_freezers', []);
      $all_freezers = array_unique(array_merge($all_freezers, array_keys($freezer_data)));
      $this->state->set('freezer_graph.all_freezers', $all_freezers);
  
      // Prepare the log entry
      $current_time = $this->dateFormatter->format(time(), 'custom', 'Y-m-d H:i:s');
      $hit_count = $this->state->get('freezer_graph.hit_count', 0) + 1;
      $this->state->set('freezer_graph.hit_count', $hit_count);
  
      // Define $freezer_names correctly
      $freezer_names = array_keys($freezer_data);
  
      $log_entry = sprintf(
        "Endpoint hit count: %d, Time: %s, Freezers: %s, Data: %s\n",
        $hit_count,
        $current_time,
        implode(', ', $freezer_names),
        json_encode($request_data)
      );
  
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
          $this->updateContentWithLogFile($log_file_path);
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
    return new static(
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('date.formatter'),
      $container->get('file_system')
    );
  }

  public function updateContentWithLogFile($log_file_path)
  {
      // Check if the content type "logs" exists
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $query = $node_storage->getQuery();
      $query->condition('type', 'logs') ->condition('title', 'Endpoint Hit logs')->accessCheck(FALSE);
      $query->range(0, 1);
      $nids = $query->execute();
  
      if (!empty($nids)) {
          // Load the existing node
          $node = $node_storage->load(reset($nids));
          \Drupal::logger('freezer_graph')->debug('Loaded existing "logs" node with ID: @nid', ['@nid' => reset($nids)]);
      } else {
          // Create a new node if it doesn't exist
          $node = $node_storage->create([
              'type' => 'logs',
              'title' => 'Endpoint Hit logs',
          ]);
          \Drupal::logger('freezer_graph')->debug('Created new "logs" node.');
      }
  
      // Debug: Check if field exists on the node
      if ($node->hasField('field_log_file')) {
          // Debug: Check if the file path is valid
          if (file_exists(\Drupal::service('file_system')->realpath($log_file_path))) {
              // Load the file data
              $file_data = file_get_contents(\Drupal::service('file_system')->realpath($log_file_path));
              if ($file_data !== FALSE) {
                  // Save the file as a managed file using file.repository service
                  $file = \Drupal::service('file.repository')->writeData($file_data, $log_file_path, FileSystemInterface::EXISTS_REPLACE);
  
                  if ($file) {
                      // Save the managed file to the field
                      $node->set('field_log_file', [
                          'target_id' => $file->id(), // Set the file ID (managed file)
                          'uri' => $file->getFileUri(),
                      ]);
                      $node->save();
                      \Drupal::messenger()->addMessage('Log file updated in "Response Logs".', MessengerInterface::TYPE_STATUS);
                      \Drupal::logger('freezer_graph')->debug('Log file path set and node saved with log file.');
                  } else {
                      \Drupal::messenger()->addMessage('Failed to save the managed file.', MessengerInterface::TYPE_ERROR);
                      \Drupal::logger('freezer_graph')->error('Failed to save the managed file from path: @path', ['@path' => $log_file_path]);
                  }
              } else {
                  \Drupal::messenger()->addMessage('Unable to read the log file content.', MessengerInterface::TYPE_ERROR);
                  \Drupal::logger('freezer_graph')->error('Unable to read the log file content from path: @path', ['@path' => $log_file_path]);
              }
          } else {
              \Drupal::messenger()->addMessage('Log file does not exist at path: ' . $log_file_path, MessengerInterface::TYPE_ERROR);
              \Drupal::logger('freezer_graph')->error('Log file does not exist at path: @path', ['@path' => $log_file_path]);
          }
      } else {
          \Drupal::messenger()->addMessage('Field "field_log_file" does not exist in "Response Logs" content type.', MessengerInterface::TYPE_ERROR);
          \Drupal::logger('freezer_graph')->error('Field "field_log_file" does not exist in "Response Logs" content type.');
      }
  }
}
