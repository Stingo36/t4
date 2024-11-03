<?php

namespace Drupal\Tests\authorization\Unit\Entity;

use Drupal\authorization\Entity\AuthorizationProfile;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the AuthorizationProfile entity.
 *
 * @coversDefaultClass \Drupal\authorization\Entity\AuthorizationProfile
 *
 * @group authorization
 */
class AuthorizationProfileTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $provider_plugin = $this->createMock('\Drupal\authorization\Provider\ProviderPluginManager');
    $consumer_plugin = $this->createMock('\Drupal\authorization\Consumer\ConsumerPluginManager');
    $entity_type_repository = $this->createMock('\Drupal\Core\Entity\EntityTypeRepositoryInterface');
    $entity_type_manager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $logger = $this->createMock('\Psr\Log\LoggerInterface');

    $container->set('plugin.manager.authorization.provider', $provider_plugin);
    $container->set('plugin.manager.authorization.consumer', $consumer_plugin);
    $container->set('entity_type.repository', $entity_type_repository);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('logger.channel.authorization', $logger);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the getName() and setName() methods.
   *
   * @covers ::getName
   * @covers ::setName
   */
  public function testGetNameAndSetName(): void {
    $label = 'Example Profile Name';
    $profile = new AuthorizationProfile([
      'label' => $label,
    ], 'authorization_profile');

    $this->assertSame($label, $profile->get('label'));
  }

  /**
   * Tests the getDescription() and setDescription() methods.
   *
   * @covers ::getDescription
   * @covers ::setDescription
   */
  public function testGetDescriptionAndSetDescription(): void {
    $description = 'Example Profile Description';
    $profile = new AuthorizationProfile([
      'label' => 'Example Profile Name',
      'description' => $description,
    ], 'authorization_profile');

    $this->assertSame($description, $profile->getDescription());
  }

  /**
   * Tests hasValidProvider().
   *
   * @covers ::hasValidProvider
   */
  public function testHasValidProvider(): void {
    $provider_id = 'example_provider';
    $profile = new AuthorizationProfile([
      'label' => 'Example Profile Name',
      'provider_id' => $provider_id,
    ], 'authorization_profile');
    $this->assertFalse($profile->hasValidProvider());
  }

}
