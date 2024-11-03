<?php declare(strict_types = 1);

namespace Drupal\twilio\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use PDO;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Twilio routes.
 */
final class TwilioLogController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly Connection $connection,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
    );
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('If <a href=":config_link">Twilio is configured to capture messages</a>, they will be displayed here.', [
        ':config_link' => Url::fromRoute('twilio.admin_form')->toString(),
      ]),
    ];

    $header = [
      'id' => t('ID'),
      'from' => t('From'),
      'to' => t('To'),
      'body' => t('Body'),
      'mediaUrl' => t('Media URL'),
      'timestamp' => t('Time'),
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $this->logs(),
      '#empty' => t('No content has been found.'),
    ];

    return $build;
  }

  /**
   * @return array
   *   Array of log entries
   */
  public function logs() {
    $result = $this->connection->select('twilio_log', 'tl')
      ->fields('tl')
      ->execute()
      ->fetchAllAssoc('id', PDO::FETCH_ASSOC);
    $logs = [];
    foreach ($result as $record) {
      $timestampString = $record['timestamp'];
      $timestamp = (int) $timestampString;
      $logs[] = [
        'id' => $record['id'],
        'from' => $record['from'],
        'to' => $record['to'],
        'body' => $record['body'],
        'mediaUrl' => $record['mediaUrl'],
        'timestamp' => date("Y-m-d H:i:s", $timestamp),
      ];
    }
    return $logs;
  }

}
