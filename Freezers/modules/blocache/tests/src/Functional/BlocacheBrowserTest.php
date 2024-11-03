<?php

namespace Drupal\Tests\blocache\Functional;

use Drupal\Core\Language\LanguageInterface;

/**
 * Blocache browser's tests.
 *
 * @coversDefaultClass \Drupal\Tests\blocache\Functional\BlocacheBrowserTest
 * @group blocache
 */
class BlocacheBrowserTest extends BlocacheBrowserTestBase {

  /**
   * Tests access to metadata configuration fields.
   */
  public function testBlocacheSettingsAccess() {
    $this->drupalLogin($this->adminUser);

    // Create and add a block.
    $block_name = 'system_powered_by_block';
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $this->defaultTheme);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $this->randomMachineName(8),
    ];
    $this->submitForm($edit, 'Save block');

    // Checks if the user has access to cache configs.
    $assert = $this->assertSession();
    $assert->pageTextContains('The block configuration has been saved.');
    $this->clickLink('Configure');
    $assert->fieldExists('blocache[overridden]');
  }

  /**
   * Tests storage of cache metadata.
   */
  public function testStorageMetadata() {
    $this->drupalLogin($this->adminUser);

    // Create and add a block.
    $block_name = 'system_powered_by_block';
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $this->defaultTheme);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $this->randomMachineName(8),
    ];
    $this->submitForm($edit, 'Save block');

    // Configures the created block.
    $assert = $this->assertSession();
    $assert->pageTextContains('The block configuration has been saved.');
    $this->clickLink('Configure');

    // Configures the cache metadata and saves the block.
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $this->randomMachineName(8),
      'blocache[overridden]' => 1,
      'blocache[tabs][max-age][value]' => 600,
      'blocache[tabs][contexts][value][user.roles]' => 1,
      'blocache[tabs][contexts][value][user.roles__arg]' => 'administrator',
      'blocache[tabs][contexts][value][languages]' => 1,
      'blocache[tabs][contexts][value][languages__arg]' => LanguageInterface::TYPE_URL,
    ];
    $this->submitForm($edit, 'Save block');

    // Access the block form again and check if the values have been saved.
    $assert = $this->assertSession();
    $assert->pageTextContains('The block configuration has been saved.');
    $this->clickLink('Configure');
    $assert->checkboxChecked('blocache[overridden]');
    $assert->fieldValueEquals('blocache[tabs][max-age][value]', 600);
    $assert->fieldValueEquals('blocache[tabs][contexts][value][user.roles]', 1);
    $assert->fieldValueEquals('blocache[tabs][contexts][value][user.roles__arg]', 'administrator');
    $assert->fieldValueEquals('blocache[tabs][contexts][value][languages]', 1);
    $assert->fieldValueEquals('blocache[tabs][contexts][value][languages__arg]', LanguageInterface::TYPE_URL);
  }

}
