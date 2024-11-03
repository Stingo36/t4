<?php

namespace Drupal\Tests\blocache\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides setup and helper methods for block module tests.
 *
 * @coversDefaultClass \Drupal\Tests\blocache\Functional\BlocacheBrowserTestBase
 * @group blocache
 */
abstract class BlocacheBrowserTestBase extends BrowserTestBase {

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'blocache'];

  /**
   * A list of theme regions to test.
   *
   * @var array
   */
  protected $regions;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
      'administer block cache',
    ]);

    // Define the existing regions.
    $this->regions = [
      'header',
      'sidebar_first',
      'content',
      'sidebar_second',
      'footer',
    ];

    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');
    $blocks = $block_storage->loadByProperties(['theme' => $this->config('system.theme')->get('default')]);
    foreach ($blocks as $block) {
      $block->delete();
    }
  }

}
