<?php

namespace Drupal\smssystem\Controller;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Draw the list of queued SMS messages.
 *
 * @ingroup smssystem
 */
class SmsQueueList extends ControllerBase {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The SmsQueueList constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   */
  final public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Callback used for getting the list of queued SMS Messages.
   */
  protected function getQueueData() {
    $rows = [];

    $query = $this->database->select('queue');
    $query->fields('queue');
    $query->condition('name', 'sms_send_processing');
    $query->orderBy('item_id', 'desc');

    /** @var \Drupal\Core\Database\Query\PagerSelectExtender $pager */
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $pager->limit(15);
    $queueData = $pager->execute()->fetchAll();

    foreach ($queueData as $item) {
      $data = unserialize($item->data, ['allowed_classes' => FALSE]);
      $data = clone $data;
      $rows[] = [
        'data' => [
          'id' => $item->item_id,
          'smsid' => $data->smsid,
          'to' => $data->to,
          'message' => $data->text ? urldecode($data->text) : '',
          'created' => DateTimePlus::createFromTimestamp($item->created)->format('Y-m-d H:i:s'),
        ],
      ];
    }

    $tableTheme = [
      'header' => [
        $this->t('ID'),
        $this->t('SMS ID'),
        $this->t('To'),
        $this->t('Message'),
        $this->t('Created'),
      ],
      'rows'   => $rows,
      'attributes' => [],
      'caption' => '',
      'colgroups' => [],
      'sticky' => TRUE,
      'empty' => $this->t('No queued items.'),
    ];
    return $tableTheme;
  }

  /**
   * Get the SMS Messages queued data.
   *
   * @return array
   *   Return the array with queue data.
   */
  public function buildList() {
    $data = $this->getQueueData();
    if (!$data) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('No queue data found'),
      ];
    }

    // Draw the table theme and pager.
    return [
      'header' => [
        '#type' => 'markup',
        '#markup' => $this->t('You can run the cron @here', [
          '@here' => Link::fromTextAndUrl('Here', Url::fromUserInput('/admin/reports/status/run-cron'))->toString(),
        ]),
      ],
      'table' => [
        '#type' => 'table',
        '#caption' => $data['caption'],
        '#header' => $data['header'],
        '#rows' => $data['rows'],
        '#attributes' => $data['attributes'],
        '#sticky' => $data['sticky'],
        '#empty' => $data['empty'],
      ],
      'pager' => ['#type' => 'pager'],
    ];
  }

}
