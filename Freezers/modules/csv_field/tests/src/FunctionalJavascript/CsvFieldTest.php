<?php

namespace Drupal\Tests\csv_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the csv field widget.
 *
 * @group file
 */
class CsvFieldTest extends WebDriverTestBase {

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'csv_field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Tests upload and display of CSV field.
   */
  public function testSingleValuedDatatable() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet("node/add/blog_post");

    $title = 'Testing Datatables';

    $page->findField('title[0][value]')->setValue($title);

    $test_file_path = $this->root . '/' . \Drupal::service('extension.list.module')->getPath('csv_field_test') . '/assets/addresses.csv';
    $page->attachFileToField('files[field_csv_file_0]', $test_file_path);
    $expected_element = ['id_or_name', 'field_csv_file_0_remove_button'];
    $this->assertSession()->waitForElement('named', $expected_element);


    $this->openConfigurationDetails();

    $this->assertTrue($this->assertSession()->optionExists('field_csv_file[0][settings][pageLength]', '5')->isSelected());
    $assert_session->checkboxChecked('field_csv_file[0][settings][lengthChange]');
    $assert_session->checkboxChecked('field_csv_file[0][settings][searching]');
    $assert_session->checkboxChecked('field_csv_file[0][settings][download]');
    $assert_session->fieldValueEquals('field_csv_file[0][settings][responsive]', 'childRow');
    $page->fillField('field_csv_file[0][settings][downloadText]', 'Download this CSV Now');

    $page->pressButton('Save');

    // Assert the wrapper div has the expected settings.
    $div_with_settings = $assert_session->elementExists('css', 'div.dataTable');
    $settings = $div_with_settings->getAttribute('data-settings');
    $this->assertStringContainsString('"pageLength":5', $settings);
    $this->assertStringContainsString('"lengthChange":1', $settings);
    $this->assertStringContainsString('"searching":1', $settings);
    $this->assertStringContainsString('"responsive":"childRow"', $settings);
    $this->assertStringContainsString('"download":1', $settings);
    $this->assertStringContainsString('"autolink":0', $settings);
    $this->assertSession()->responseNotContains('Autolinker.min.js');

    $this->assertSession()->waitForElement('css', '.dataTables_wrapper');

    // This element is added by the datatables JavaScript plugin, so if it's
    // present that means the table has been rendered by the plugin.
    $this->assertSession()->elementExists('css', '.dataTables_wrapper');

    // Assert the Search filter field is present.
    $assert_session->elementExists('css', '.dataTables_filter input[type=search]');
    // Assert the Row length change select is present.
    $assert_session->elementExists('css', '.dataTables_length select');
    // Assert the pagination div is present.
    $assert_session->elementExists('css', '.dataTables_paginate');
    // Assert download link is present.
    $this->assertSession()->linkExists('Download this CSV Now');

    $node = $this->drupalGetNodeByTitle($title);
    $edit_path = sprintf('node/%d/edit', $node->id());

    $this->drupalGet($edit_path);

    $this->openConfigurationDetails();

    $page->selectFieldOption('field_csv_file[0][settings][responsive]', 'childRowImmediate');
    $page->uncheckField('field_csv_file[0][settings][searching]');
    $page->uncheckField('field_csv_file[0][settings][lengthChange]');

    // Assert that unchecking download field hides the download text field.
    $download_text = $assert_session->fieldExists('field_csv_file[0][settings][downloadText]');
    $this->assertTrue($download_text->isVisible());
    $page->uncheckField('field_csv_file[0][settings][download]');
    $page->waitFor(10, function () use ($download_text) {
      return !$download_text->isVisible();
    });
    $this->assertFalse($download_text->isVisible());

    $length_change = $assert_session->fieldExists('field_csv_file[0][settings][lengthChange]');
    $this->assertTrue($length_change->isVisible());
    $page_length = $assert_session->fieldExists('field_csv_file[0][settings][pageLength]');
    $this->assertTrue($page_length->isVisible());
    $page->selectFieldOption('field_csv_file[0][settings][pageLength]', '10');
    $page->checkField('field_csv_file[0][settings][urls][autolink]');

    $page->pressButton('Save');

    // Assert the wrapper div has the expected settings.
    // Assert the wrapper div has the expected settings.
    $div_with_settings = $assert_session->elementExists('css', 'div.dataTable');
    $settings = $div_with_settings->getAttribute('data-settings');
    $this->assertStringContainsString('"lengthChange":0', $settings);
    $this->assertStringContainsString('"pageLength":10', $settings);
    $this->assertStringContainsString('"searching":0', $settings);
    $this->assertStringContainsString('"responsive":"childRowImmediate"', $settings);
    $this->assertStringContainsString('"download":0', $settings);
    $this->assertStringContainsString('"autolink":1', $settings);
    $this->assertSession()->responseContains('Autolinker.min.js');
    $this->assertStringNotContainsString('downloadText', $settings);

    $this->assertSession()->waitForElement('css', '.dataTables_wrapper');

    // Assert the Search filter field is not present.
    $assert_session->elementNotExists('css', '.dataTables_filter input[type=search]');
    // Assert the Row length change select is not present.
    $assert_session->elementNotExists('css', '.dataTables_length select');
    // Assert download link is not present.
    $this->assertSession()->linkNotExists('Download this CSV Now');
    // test that autolinking worked.
    $this->assertSession()->linkExists('https://www.example.com');
  }

  /**
   * Helper function to open the configuration details.
   */
  protected function openConfigurationDetails() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Assert Configuration details is closed.
    $responsive_field = $assert_session->fieldExists('field_csv_file[0][settings][responsive]');
    $this->assertFalse($responsive_field->isVisible());

    // Open Configuration details.
    $assert_session->elementExists('xpath', '//details/summary[text()="Display Configuration"]')->click();
    // Assert Configuration details is open.
    $page->waitFor(10, function () use ($responsive_field) {
      return $responsive_field->isVisible();
    });
    $this->assertTrue($responsive_field->isVisible());
  }

}
