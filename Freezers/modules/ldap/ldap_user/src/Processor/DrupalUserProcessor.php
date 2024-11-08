<?php

declare(strict_types=1);

namespace Drupal\ldap_user\Processor;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\externalauth\AuthmapInterface;
use Drupal\ldap_servers\LdapUserAttributesInterface;
use Drupal\ldap_servers\LdapUserManager;
use Drupal\ldap_servers\Logger\LdapDetailLog;
use Drupal\ldap_servers\Processor\TokenProcessor;
use Drupal\ldap_servers\ServerInterface;
use Drupal\ldap_user\Event\LdapUserLoginEvent;
use Drupal\ldap_user\FieldProvider;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;

/**
 * Handles processing of a user from LDAP to Drupal.
 */
class DrupalUserProcessor implements LdapUserAttributesInterface {

  use StringTranslationTrait;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Authentication config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $configAuthentication;

  /**
   * Detail log.
   *
   * @var \Drupal\ldap_servers\Logger\LdapDetailLog
   */
  protected $detailLog;

  /**
   * Token Processor.
   *
   * @var \Drupal\ldap_servers\Processor\TokenProcessor
   */
  protected $tokenProcessor;

  /**
   * Externalauth.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $externalAuth;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Filesystem.
   *
   * @var \Drupal\Core\File\FileSystemInterface|null
   */
  protected $fileSystem;

  /**
   * Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Field provider.
   *
   * @var \Drupal\ldap_user\FieldProvider
   */
  protected $fieldProvider;

  /**
   * The Drupal user account.
   *
   * @var \Drupal\user\Entity\User
   */
  private $account;

  /**
   * LDAP entry.
   *
   * @var \Symfony\Component\Ldap\Entry
   */
  private $ldapEntry;

  /**
   * The server interacting with.
   *
   * @var \Drupal\ldap_servers\ServerInterface
   */
  private $server;

  /**
   * LDAP User Manager.
   *
   * @var \Drupal\ldap_servers\LdapUserManager
   */
  protected $ldapUserManager;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * File repository.
   *
   * @var \Drupal\file\FileRepositoryInterface|null
   */
  protected $fileRepository;

  /**
   * File validator.
   *
   * @var \Drupal\Core\File\FileSystemInterface|null
   */
  protected $fileValidator;

  /**
   * Constructor.
   *
   * @todo Make this service smaller.
   * (The number of dependencies alone makes this clear.)
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\ldap_servers\Logger\LdapDetailLog $detail_log
   *   Detail log.
   * @param \Drupal\ldap_servers\Processor\TokenProcessor $token_processor
   *   Token processor.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   AuthmapInterface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   * @param \Drupal\Core\Utility\Token $token
   *   Token.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\ldap_servers\LdapUserManager $ldap_user_manager
   *   LDAP user manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\ldap_user\FieldProvider $field_provider
   *   Field Provider.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $passwordGenerator
   *   Password Generator.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   */
  public function __construct(
    LoggerInterface $logger,
    ConfigFactory $config_factory,
    LdapDetailLog $detail_log,
    TokenProcessor $token_processor,
    AuthmapInterface $authmap,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    Token $token,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    LdapUserManager $ldap_user_manager,
    EventDispatcherInterface $event_dispatcher,
    FieldProvider $field_provider,
    MessengerInterface $messenger,
    PasswordGeneratorInterface $passwordGenerator,
    ContainerInterface $container) {

    $this->logger = $logger;
    $this->config = $config_factory->get('ldap_user.settings');
    $this->configAuthentication = $config_factory->get('ldap_authentication.settings');
    $this->detailLog = $detail_log;
    $this->tokenProcessor = $token_processor;
    $this->externalAuth = $authmap;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->token = $token;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->ldapUserManager = $ldap_user_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->fieldProvider = $field_provider;
    $this->messenger = $messenger;
    $this->passwordGenerator = $passwordGenerator;

    if ($this->moduleHandler->moduleExists('file')) {
      $this->fileRepository = $container->get('file.repository');
      if (version_compare(\Drupal::VERSION, '10.2.0', '>=')) {
        $this->fileValidator = $container->get('file.validator');
      }
    }

  }

  /**
   * Check if user is excluded.
   *
   * @param \Drupal\user\UserInterface $account
   *   A Drupal user object.
   *
   * @return bool
   *   TRUE if user should be excluded from LDAP provision/syncing
   */
  public function excludeUser(UserInterface $account): bool {

    if ($this->configAuthentication->get('skipAdministrators')) {
      $admin_roles = $this->entityTypeManager
        ->getStorage('user_role')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_admin', TRUE)
        ->execute();
      if (!empty(array_intersect($account->getRoles(), $admin_roles))) {
        return TRUE;
      }
    }
    // Exclude users who have been manually flagged as excluded, everyone else
    // is fine.
    return $account->get('ldap_user_ldap_exclude')->getString() === '1';
  }

  /**
   * Get the user account.
   *
   * @return \Drupal\user\Entity\User|null
   *   User account.
   */
  public function getUserAccount(): ?User {
    return $this->account;
  }

  /**
   * Set LDAP associations of a Drupal account by altering user fields.
   *
   * @param string $drupal_username
   *   The Drupal username.
   *
   * @return bool
   *   Returns FALSE on invalid user or LDAP accounts.
   */
  public function ldapAssociateDrupalAccount(string $drupal_username): bool {
    if (!$this->config->get('drupalAcctProvisionServer')) {
      return FALSE;
    }

    /** @var \Drupal\ldap_servers\ServerInterface $ldap_server */
    $ldap_server = $this->entityTypeManager
      ->getStorage('ldap_server')
      ->load($this->config->get('drupalAcctProvisionServer'));

    $load_by_name = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $drupal_username]);
    if (!$load_by_name) {
      $this->logger->error('Failed to LDAP associate Drupal account %drupal_username because account not found', ['%drupal_username' => $drupal_username]);
      return FALSE;
    }

    if (!$ldap_server instanceof ServerInterface) {
      $this->logger->error('Failed to load a valid LDAP server from configuration');
      return FALSE;
    }
    $this->ldapUserManager->setServer($ldap_server);

    $this->account = reset($load_by_name);
    $this->ldapEntry = $this->ldapUserManager->matchUsernameToExistingLdapEntry($drupal_username);
    if (!$this->ldapEntry) {
      $this->logger->error('Failed to LDAP associate Drupal account %drupal_username because corresponding LDAP entry not found', ['%drupal_username' => $drupal_username]);
      return FALSE;
    }

    $persistent_uid = $ldap_server->derivePuidFromLdapResponse($this->ldapEntry);
    if (!empty($persistent_uid)) {
      $this->account->set('ldap_user_puid', $persistent_uid);
    }
    $this->account->set('ldap_user_puid_property', $ldap_server->getUniquePersistentAttribute());
    $this->account->set('ldap_user_puid_sid', $ldap_server->id());
    $this->account->set('ldap_user_current_dn', $this->ldapEntry->getDn());
    $this->account->set('ldap_user_last_checked', time());
    $this->account->set('ldap_user_ldap_exclude', 0);
    $this->saveAccount();
    $this->externalAuth->save($this->account, 'ldap_user', $this->account->getAccountName());

    return TRUE;
  }

  /**
   * Provision a Drupal user account.
   *
   * Given user data, create a user and apply LDAP attributes or assign to
   * correct user if name has changed through PUID.
   *
   * @param array $user_data
   *   A keyed array normally containing 'name' and optionally more.
   *
   * @return bool
   *   Whether creation was a success.
   */
 /** public function createDrupalUserFromLdapEntry(array $user_data): bool {
    $this->account = $this->entityTypeManager
      ->getStorage('user')
      ->create($user_data);
    /** @var \Drupal\ldap_servers\ServerInterface $server */
    /**$server = $this->entityTypeManager
      ->getStorage('ldap_server')
      ->load($this->config->get('drupalAcctProvisionServer'));
    $this->server = $server;
    // Get an LDAP user from the LDAP server.
    if ($this->config->get('drupalAcctProvisionServer')) {
      $this->ldapUserManager->setServer($this->server);
      $this->ldapEntry = $this->ldapUserManager
        ->getUserDataByIdentifier($this->account->getAccountName());
    }

    if (!$this->ldapEntry) {
      $this->detailLog->log(
        '@username: Failed to find associated LDAP entry for username in provision.',
        ['@username' => $this->account->getAccountName()],
        'ldap-user'
      );
      return FALSE;
    }

    // Can we get details from an LDAP server?
    $params = [
      'account' => $this->account,
      'prov_event' => self::EVENT_CREATE_DRUPAL_USER,
      'module' => 'ldap_user',
      'function' => 'createDrupalUserFromLdapEntry',
      'direction' => self::PROVISION_TO_DRUPAL,
    ];

    $this->moduleHandler->alter('ldap_entry', $this->ldapEntry, $params);

    // Look for existing Drupal account with the same PUID. If found, update
    // that user instead of creating a new user.
    $persistentUid = $this->server->derivePuidFromLdapResponse($this->ldapEntry);
    $accountFromPuid = !empty($persistentUid) ? $this->ldapUserManager->getUserAccountFromPuid($persistentUid) : FALSE;
    if ($accountFromPuid) {
      $this->updateExistingAccountByPersistentUid($accountFromPuid);
    }
    else {
      $this->createDrupalUser();
    }

    return TRUE;
 }*/


public function createDrupalUserFromLdapEntry(array $user_data): bool {
    $mydns = array(
        "extncbs" => "ou=People,dc=ext,dc=ncbs,dc=res,dc=in",
        "ncbs" => "ou=People,dc=ncbs,dc=res,dc=in"
    );

    // Create the Drupal account.
    $this->account = $this->entityTypeManager
        ->getStorage('user')
        ->create($user_data);

    // Get LDAP data for the account.
    $this->ldapEntry = $this->ldapUserManager
        ->getUserDataByIdentifier($this->account->getAccountName());
    
    // Determine the correct server based on the DN.
    $curdn = $this->ldapEntry->getDn();
    $curserver = "ncbs";
    foreach ($mydns as $key => $value) {
        if (str_ends_with($curdn, $value)) {
            $curserver = $key;
        }
    }

    // Load the appropriate LDAP server.
    $server = $this->entityTypeManager
        ->getStorage('ldap_server')
        ->load($curserver);

    $this->server = $server;

    // Proceed with LDAP user data retrieval.
    if ($this->config->get('drupalAcctProvisionServer')) {
        $this->ldapUserManager->setServer($this->server);
        $this->ldapEntry = $this->ldapUserManager
            ->getUserDataByIdentifier($this->account->getAccountName());
    }

    // Handle the case where no LDAP entry is found.
    if (!$this->ldapEntry) {
        $this->detailLog->log(
            '@username: Failed to find associated LDAP entry for username in provision.',
            ['@username' => $this->account->getAccountName()],
            'ldap-user'
        );
        return FALSE;
    }

    // Proceed with user creation or update.
    $params = [
        'account' => $this->account,
        'prov_event' => self::EVENT_CREATE_DRUPAL_USER,
        'module' => 'ldap_user',
        'function' => 'createDrupalUserFromLdapEntry',
        'direction' => self::PROVISION_TO_DRUPAL,
    ];

    $this->moduleHandler->alter('ldap_entry', $this->ldapEntry, $params);

    // Look for existing Drupal account by PUID.
    $persistentUid = $this->server->derivePuidFromLdapResponse($this->ldapEntry);
    $accountFromPuid = !empty($persistentUid) ? $this->ldapUserManager->getUserAccountFromPuid($persistentUid) : FALSE;

    // Update existing account if found.
    if ($accountFromPuid) {
        $this->updateExistingAccountByPersistentUid($accountFromPuid);
    } else {
        $this->createDrupalUser();
    }

    // Return TRUE to indicate success.
    return TRUE;
}


  /**
   * Set flag to exclude user from LDAP association.
   *
   * @param string $drupal_username
   *   The account username.
   *
   * @return bool
   *   TRUE on success, FALSE on error or failure because of invalid user.
   */
  public function ldapExcludeDrupalAccount(string $drupal_username): bool {
    /** @var \Drupal\user\Entity\User $accounts */
    $accounts = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $drupal_username]);
    if (!$accounts) {
      $this->logger->error('Failed to exclude user from LDAP association because Drupal account %username was not found', ['%username' => $drupal_username]);
      return FALSE;
    }
    $account = reset($accounts);
    $account->set('ldap_user_ldap_exclude', 1);
    return (bool) $account->save();
  }

  /**
   * Callback for hook_ENTITY_TYPE_update().
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user.
   */
  public function drupalUserUpdate(UserInterface $account): void {
    if ($this->account && $this->account->id() != $account->id()) {
      $this->reset();
    }
    $this->account = $account;
    if ($this->excludeUser($this->account)) {
      return;
    }
    $server = $this->config->get('drupalAcctProvisionServer');
    $triggers = $this->config->get('drupalAcctProvisionTriggers');
    if ($server && in_array(self::PROVISION_DRUPAL_USER_ON_USER_UPDATE_CREATE, $triggers, TRUE)) {
      $this->syncToDrupalAccount();
    }
  }

  /**
   * Handle Drupal user login.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user.
   */
  public function drupalUserLogsIn(UserInterface $account): void {
    $this->account = $account;
    if ($this->excludeUser($this->account)) {
      return;
    }
    $triggers = $this->config->get('drupalAcctProvisionTriggers');
    $server = $this->config->get('drupalAcctProvisionServer');

    if ($server && in_array(self::PROVISION_DRUPAL_USER_ON_USER_AUTHENTICATION, $triggers, TRUE)) {
      $this->syncToDrupalAccount();
    }

    $event = new LdapUserLoginEvent($account);
    $this->eventDispatcher->dispatch($event);

    $this->saveAccount();
  }

  /**
   * Create a Drupal user.
   */
  private function createDrupalUser(): void {
    $this->account->enforceIsNew();
    $this->applyAttributesToAccountOnCreate();
    $tokens = ['%drupal_username' => $this->account->getAccountName()];
    if (empty($this->account->getAccountName())) {
      $this->messenger->addError($this->t('User account creation failed because of invalid, empty derived Drupal username.'));
      $this->logger
        ->error('Failed to create Drupal account %drupal_username because Drupal username could not be derived.', $tokens);
      return;
    }
    if (!$mail = $this->account->getEmail()) {
      $this->messenger->addError($this->t('User account creation failed because of invalid, empty derived email address.'));
      $this->logger
        ->error('Failed to create Drupal account %drupal_username because email address could not be derived by LDAP User module', $tokens);
      return;
    }

    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $mail]);
    $account_with_same_email = $users ? reset($users) : FALSE;

    if ($account_with_same_email) {
      $this->logger
        ->error('LDAP user %drupal_username has email address (%email) conflict with a Drupal user %duplicate_name', [
          '%drupal_username' => $this->account->getAccountName(),
          '%email' => $mail,
          '%duplicate_name' => $account_with_same_email->getAccountName(),
        ]
      );
      $this->messenger->addError($this->t('Another user already exists in the system with the same email address. You should contact the system administrator in order to solve this conflict.'));
      return;
    }
    $this->saveAccount();
    $this->externalAuth->save($this->account, 'ldap_user', $this->account->getAccountName());
  }

  /**
   * Update Drupal user from PUID.
   *
   * @param \Drupal\user\UserInterface $accountFromPuid
   *   The account from the PUID.
   */
  private function updateExistingAccountByPersistentUid(UserInterface $accountFromPuid): void {
    $this->account = $accountFromPuid;
    $this->externalAuth->save($this->account, 'ldap_user', $this->account->getAccountName());
    $this->syncToDrupalAccount();
    $this->saveAccount();
  }

  /**
   * Process user picture from LDAP entry.
   *
   * @return array|null
   *   Drupal file object image user's thumbnail or NULL if none present or
   *   an error occurs.
   */
  private function userPictureFromLdapEntry(): ?array {
    $picture_attribute = $this->server->getPictureAttribute();
    if (!$this->ldapEntry || !$picture_attribute || !$this->ldapEntry->hasAttribute($picture_attribute, FALSE)) {
      return NULL;
    }

    $ldap_user_picture = $this->ldapEntry->getAttribute($picture_attribute, FALSE)[0];
    $currentUserPicture = $this->account->get('user_picture')->getValue();

    if (empty($currentUserPicture)) {
      return $this->saveUserPicture($this->account->get('user_picture'), $ldap_user_picture);
    }

    /** @var \Drupal\file\Entity\File $file */
    $file = $this->entityTypeManager
      ->getStorage('file')
      ->load($currentUserPicture[0]['target_id']);
    if ($file && file_exists($file->getFileUri())) {
      $file_data = file_get_contents($file->getFileUri());
      if (md5($file_data) === md5($ldap_user_picture)) {
        // Same image, do nothing.
        return NULL;
      }
    }

    return $this->saveUserPicture($this->account->get('user_picture'), $ldap_user_picture);
  }

  /**
   * Save the user's picture.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field attached to the user.
   * @param string $ldap_user_picture
   *   The picture itself.
   *
   * @return array|null
   *   Nullable array of form ['target_id' => 123].
   */
  private function saveUserPicture(FieldItemListInterface $field, string $ldap_user_picture): ?array {
    // Create tmp file to get image format and derive extension.
    $fileName = uniqid('', FALSE);
    $unmanagedFile = $this->fileSystem->getTempDirectory() . '/' . $fileName;
    $unmanaged_file_length = file_put_contents($unmanagedFile, $ldap_user_picture);
    if ($unmanaged_file_length === FALSE) {
      $this->detailLog
        ->log('Unable to save file @file',
          ['@file' => $unmanagedFile]
        );

      return NULL;
    }
    // @todo Declare dependency on exif or resolve it.
    $image_type = exif_imagetype($unmanagedFile);
    $extension = image_type_to_extension($image_type, FALSE);
    unlink($unmanagedFile);

    $field_settings = $field->getFieldDefinition()->getItemDefinition()->getSettings();
    $directory = $this->token->replace($field_settings['file_directory']);
    $directory_path = $field_settings['uri_scheme'] . '://' . $directory;
    $realpath = $this->fileSystem->realpath($directory_path);

    if ($realpath && !is_dir((string) $realpath)) {
      $this->fileSystem->mkdir($directory_path, NULL, TRUE);
    }

    $managed_file_path = $directory_path . '/' . $fileName . '.' . $extension;
    $managed_file = $this->fileRepository->writeData($ldap_user_picture, $managed_file_path);
    $errors = [];
    $has_error = FALSE;
    if (version_compare(\Drupal::VERSION, '10.2.0', '<')) {
      $validators = [
        'file_validate_is_image' => [],
        'file_validate_extensions' => [$field_settings['file_extensions']],
        'file_validate_image_resolution' => [$field_settings['max_resolution']],
        'file_validate_size' => [$field_settings['max_filesize']],
      ];
      // @phpstan-ignore-next-line
      $errors = file_validate($managed_file, $validators);
      $has_error = (bool) count($errors);
    }
    else {
      $validators = [
        'FileIsImage' => [],
        'FileExtension' => ['extensions' => $field_settings['file_extensions']],
        'FileImageDimensions' => ['maxDimensions' => $field_settings['max_resolution']],
        'FileSizeLimit' => ['fileLimit' => (int) $field_settings['max_filesize']],
      ];
      $errors = $this->fileValidator->validate($managed_file, $validators);
      $has_error = $errors->count() > 0;
    }

    // @todo Verify file garbage collection.
    foreach ($errors as $error) {
      $this->detailLog
        ->log('File upload error for user image with validation error @error',
          ['@error' => $error]
        );
    }

    if (!$managed_file || $has_error) {
      return NULL;
    }

    return ['target_id' => $managed_file->id()];
  }

  /**
   * Saves the account, separated to make this testable.
   */
  private function saveAccount(): void {
    $this->account->save();
  }

  /**
   * Apply field values to user account.
   *
   * One should not assume all attributes are present in the LDAP entry.
   */
  private function applyAttributesToAccount(): void {
    $this->fieldProvider->loadAttributes(self::PROVISION_TO_DRUPAL, $this->server);

    $this->setLdapBaseFields(self::EVENT_SYNC_TO_DRUPAL_USER);
    $this->setUserDefinedMappings(self::EVENT_SYNC_TO_DRUPAL_USER);

    $context = [
      'ldap_server' => $this->server,
      'prov_event' => self::EVENT_SYNC_TO_DRUPAL_USER,
    ];
    $this->moduleHandler
      ->alter('ldap_user_edit_user',
        $this->account,
        $this->ldapEntry,
        $context);

    // Set ldap_user_last_checked.
    $this->account->set('ldap_user_last_checked', time());
  }

  /**
   * Apply field values to user account.
   *
   * One should not assume all attributes are present in the LDAP entry.
   */
  private function applyAttributesToAccountOnCreate(): void {
    $this->fieldProvider->loadAttributes(self::PROVISION_TO_DRUPAL, $this->server);
    $this->setLdapBaseFields(self::EVENT_CREATE_DRUPAL_USER);
    $this->setFieldsOnDrupalUserCreation();
    $this->setUserDefinedMappings(self::EVENT_CREATE_DRUPAL_USER);

    $context = [
      'ldap_server' => $this->server,
      'prov_event' => self::EVENT_CREATE_DRUPAL_USER,
    ];
    $this->moduleHandler
      ->alter('ldap_user_edit_user',
        $this->account,
        $this->ldapEntry,
        $context);

    // Set ldap_user_last_checked.
    $this->account->set('ldap_user_last_checked', time());
  }

  /**
   * For a Drupal account, query LDAP, get all user fields and set them.
   *
   * @return bool
   *   Attempts to sync, reports failure if unsuccessful.
   */
  private function syncToDrupalAccount(): bool {
    if (!($this->account instanceof UserInterface)) {
      $this->logger
        ->notice('Invalid selection passed to syncToDrupalAccount.');
      return FALSE;
    }

    if (property_exists($this->account, 'ldap_synced')) {
      // We skip syncing if we already did add the fields on the user.
      return FALSE;
    }

    if (!$this->ldapEntry && $this->config->get('drupalAcctProvisionServer')) {
      $this->ldapUserManager->setServerById($this->config->get('drupalAcctProvisionServer'));
      $this->ldapEntry = $this->ldapUserManager->getUserDataByAccount($this->account);
    }

    if (!$this->ldapEntry) {
      return FALSE;
    }

    if ($this->config->get('drupalAcctProvisionServer')) {
      $this->server = $this->entityTypeManager
        ->getStorage('ldap_server')
        ->load($this->config->get('drupalAcctProvisionServer'));
      $this->applyAttributesToAccount();
      $this->account->ldap_synced = TRUE;
    }
    return TRUE;
  }

  /**
   * Sets the fields for initial users.
   */
  private function setFieldsOnDrupalUserCreation(): void {
    $derived_mail = $this->server->deriveEmailFromLdapResponse($this->ldapEntry);
    if (!$this->account->getEmail()) {
      $this->account->set('mail', $derived_mail);
    }
    if (!$this->account->getPassword()) {
      $this->account->set('pass', $this->passwordGenerator->generate(20));
    }
    if (!$this->account->getInitialEmail()) {
      $this->account->set('init', $derived_mail);
    }
    if (!$this->account->isBlocked()) {
      $this->account->set('status', 1);
    }
  }

  /**
   * Sets the fields required by LDAP.
   *
   * @param string $event
   *   Provisioning event.
   */
  private function setLdapBaseFields(string $event): void {
    // Basic $user LDAP fields.
    if ($this->fieldProvider->attributeIsSyncedOnEvent('[property.name]', $event)) {
      $this->account->set('name', $this->server->deriveUsernameFromLdapResponse($this->ldapEntry));
    }

    if ($this->fieldProvider->attributeIsSyncedOnEvent('[property.mail]', $event)) {
      $derived_mail = $this->server->deriveEmailFromLdapResponse($this->ldapEntry);
      if (!empty($derived_mail)) {
        $this->account->set('mail', $derived_mail);
      }
    }

    if ($this->fieldProvider->attributeIsSyncedOnEvent('[property.picture]', $event)) {
      $picture = $this->userPictureFromLdapEntry();
      if ($picture) {
        $this->account->set('user_picture', $picture);
      }
    }

    if ($this->fieldProvider->attributeIsSyncedOnEvent('[field.ldap_user_puid]', $event)) {
      $ldap_user_puid = $this->server->derivePuidFromLdapResponse($this->ldapEntry);
      if (!empty($ldap_user_puid)) {
        $this->account->set('ldap_user_puid', $ldap_user_puid);
      }
    }
    if ($this->fieldProvider->attributeIsSyncedOnEvent('[field.ldap_user_puid_property]', $event)) {
      $this->account->set('ldap_user_puid_property', $this->server->getUniquePersistentAttribute());
    }
    if ($this->fieldProvider->attributeIsSyncedOnEvent('[field.ldap_user_puid_sid]', $event)) {
      $this->account->set('ldap_user_puid_sid', $this->server->id());
    }
    if ($this->fieldProvider->attributeIsSyncedOnEvent('[field.ldap_user_current_dn]', $event)) {
      $this->account->set('ldap_user_current_dn', $this->ldapEntry->getDn());
    }
  }

  /**
   * Sets the additional, user-defined fields.
   *
   * The greyed out user mappings are not passed to this function.
   *
   * @param string $event
   *   Provisioning event.
   */
  private function setUserDefinedMappings(string $event): void {
    $mappings = $this->fieldProvider->getConfigurableAttributesSyncedOnEvent($event);

    foreach ($mappings as $key => $mapping) {
      // If "convert from binary is selected" and no particular method is in
      // token default to binaryConversionToString() function.
      if ($mapping->isBinary() && strpos($mapping->getLdapAttribute(), ';') === FALSE) {
        $mapping->setLdapAttribute(str_replace(']', ';binary]', $mapping->getLdapAttribute()));
      }
      $value = $this->tokenProcessor
        ->ldapEntryReplacementsForDrupalAccount(
          $this->ldapEntry,
          $mapping->getLdapAttribute()
        );
      // The ordinal $value_instance is not used and could probably be
      // removed.
      [$value_type, $value_name] = $this->parseUserAttributeNames($key);

      if ($value_type === 'field' || $value_type === 'property') {
        $this->account->set($value_name, $value === '' ? NULL : $value);
      }
    }
  }

  /**
   * Parse user attribute names.
   *
   * @param string $user_attribute_key
   *   A string in the form of <attr_type>.<attr_name>[:<instance>] such as
   *   field.lname, property.mail, field.aliases:2.
   *
   * @return array
   *   An array such as [field, 'field_user_lname'].
   */
  private function parseUserAttributeNames(string $user_attribute_key): array {
    $type = '';
    $name = '';
    // Make sure no [] are on attribute.
    $user_attribute_key = trim($user_attribute_key, '[]');
    $parts = explode('.', $user_attribute_key);
    if ($parts !== FALSE) {
      $type = $parts[0];
      $name = $parts[1] ?? '';

      if ($name) {
        // Remove everything after the colon (could be simplified).
        $name_parts = explode(':', $name);
        if ($name_parts !== FALSE && isset($name_parts[1])) {
          $name = $name_parts[0];
        }
      }
    }
    return [$type, $name];
  }

  /**
   * Resets the processor so that it can be used for additional queries.
   */
  public function reset(): void {
    $this->account = NULL;
    $this->ldapEntry = NULL;
  }

}
