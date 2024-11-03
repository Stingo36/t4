<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 *
 */
class LDAPFormBase extends FormBase {

  /**
   * The base URL of the Drupal installation.
   */
  protected string $base_url;

  /**
   * A config object fetching configuration in config table.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * A config object for storing, updating, and deleting stored configuration in config table.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config_factory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  protected Request $request;

  protected bool $disabled;

  /**
   * @var mixed
   */
  /**
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleList;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
  MessengerInterface $messenger,
  LoggerChannelFactoryInterface $logger_factory,
  EmailValidatorInterface $email_validator,
  ModuleExtensionList $module_list,
  Connection $database) {
    $this->base_url = \Drupal::request()->getSchemeAndHttpHost().\Drupal::request()->getBasePath();
    $this->config = $config_factory->getEditable('ldap_auth.settings');
    $this->config_factory = $config_factory->getEditable('ldap_auth.settings');
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('ldap_auth');
    $this->request = \Drupal::request();
    $this->emailValidator = $email_validator;
    $this->moduleList = $module_list;
    $this->database = $database;
    $this->disabled = FALSE;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('email.validator'),
      $container->get('extension.list.module'),
      $container->get('database'),
    );
  }

  /**
   *
   */
  public function getFormId() {
    return 'ldap_form_base';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

  public function getRouteUrl($route_name){
    $link = Url::fromRoute($route_name)->toString(TRUE)->getGeneratedUrl();
    return \Drupal::request()->getSchemeAndHttpHost().$link;
  }

}
