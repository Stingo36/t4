<?php

namespace Drupal\Tests\readonly_field_widget\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Readonly Field Widget basic behaviour.
 *
 * @group readonly_field_widget
 */
class ReadonlyFieldWidgetTest extends BrowserTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = ['readonly_field_widget_test'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * An admin user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->createContentType(['name' => 'page', 'type' => 'page']);
    $this->createContentType(['name' => 'article', 'type' => 'article']);

    $tags_vocab = Vocabulary::create(['vid' => 'tags', 'title' => 'Tags']);
    $tags_vocab->save();

    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Add an article reference field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'field_ui:entity_reference:node',
      'label' => 'article reference',
      'field_name' => 'article_reference',
    ], 'Save and continue');
    $this->submitForm([], 'Save field settings');
    $this->submitForm(['settings[handler_settings][target_bundles][article]' => 'article'], 'Save settings');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[field_article_reference][type]' => 'readonly_field_widget',
    ], 'Save');
    $this->submitForm([], 'field_article_reference_settings_edit');
    $this->submitForm([
      'fields[field_article_reference][settings_edit_form][settings][label]' => 'above',
      'fields[field_article_reference][settings_edit_form][settings][formatter_type]' => 'entity_reference_entity_view',
      'fields[field_article_reference][settings_edit_form][settings][show_description]' => TRUE,
      'fields[field_article_reference][settings_edit_form][settings][formatter_settings][entity_reference_entity_view][view_mode]' => 'default',
    ], 'Update');
    $this->submitForm([], 'Save');

    // Add a taxonomy term reference field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'field_ui:entity_reference:taxonomy_term',
      'label' => 'term reference',
      'field_name' => 'term_reference',
    ], 'Save and continue');
    $this->submitForm([], 'Save field settings');
    $this->submitForm(['settings[handler_settings][target_bundles][tags]' => 'tags'], 'Save settings');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[field_term_reference][type]' => 'readonly_field_widget',
    ], 'Save');

    // Add a simple text field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'string',
      'label' => 'some plain text',
      'field_name' => 'some_plain_text',
    ], 'Save and continue');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[field_some_plain_text][type]' => 'readonly_field_widget',
    ], 'Save');
    $this->submitForm([], 'field_some_plain_text_settings_edit');
    $this->submitForm([
      'fields[field_some_plain_text][settings_edit_form][settings][label]' => 'above',
      'fields[field_some_plain_text][settings_edit_form][settings][formatter_type]' => 'string',
      'fields[field_some_plain_text][settings_edit_form][settings][show_description]' => TRUE,
      'fields[field_some_plain_text][settings_edit_form][settings][formatter_settings][string][link_to_entity]' => TRUE,
    ], 'Update');
    $this->submitForm([], 'Save');

    // Add a second text field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'string',
      'label' => 'restricted text',
      'field_name' => 'restricted_text',
    ], 'Save and continue');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[field_restricted_text][type]' => 'readonly_field_widget',
      'fields[title][type]' => 'readonly_field_widget',
    ], 'Save');
    $this->submitForm([], 'field_restricted_text_settings_edit');
    $this->submitForm([
      'fields[field_restricted_text][settings_edit_form][settings][label]' => 'above',
      'fields[field_restricted_text][settings_edit_form][settings][formatter_type]' => 'string',
      'fields[field_restricted_text][settings_edit_form][settings][show_description]' => TRUE,
      'fields[field_restricted_text][settings_edit_form][settings][formatter_settings][string][link_to_entity]' => TRUE,
    ], 'Update');
    $this->submitForm([], 'Save');

    // Set the title to be read-only.
    $this->submitForm([
      'fields[title][type]' => 'readonly_field_widget',
    ], 'Save');
    $this->submitForm([], 'title_settings_edit');
    $this->submitForm([
      'fields[title][settings_edit_form][settings][label]' => 'inline',
      'fields[title][settings_edit_form][settings][formatter_type]' => 'string',
      'fields[title][settings_edit_form][settings][show_description]' => TRUE,
      'fields[title][settings_edit_form][settings][formatter_settings][string][link_to_entity]' => FALSE,
    ], 'Update');
    $this->submitForm([], 'Save');
  }

  /**
   * Test that the widget still works when default values are set up.
   */
  public function testDefaultValues() {

    // Make article field required.
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');
    $this->submitForm([
      'required' => TRUE,
    ], 'Save settings');
    $this->assertSession()->statusCodeEquals(200);

    // Set the article ref field to options select dropdown.
    // Set title to regular text field.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[title][type]' => 'string_textfield',
      'fields[field_article_reference][type]' => 'options_select',
    ], 'Save');

    // Set default value of article field to a test article node.
    $article = $this->createNode([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => 1,
    ]);

    $article->save();
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');
    $this->submitForm([
      'default_value_input[field_article_reference]' => $article->id(),
    ], 'Save settings');

    // Set widget back to readonly.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([
      'fields[field_article_reference][type]' => 'readonly_field_widget',
    ], 'Save');

    // Should see our test article in the default values widget.
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');
    $this->assertSession()->pageTextContains($article->label());
    $this->submitForm([], 'Save settings');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/node/add/page');
    $this->assertSession()->pageTextContains($article->label());
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($article->label());
  }

  /**
   * Test field access on readonly fields.
   */
  public function testFieldAccess() {

    $assert = $this->assertSession();

    $test_string = $this->randomMachineName();
    $restricted_test_string = $this->randomMachineName();

    $article = $this->createNode([
      'type' => 'article',
      'title' => 'test-article',
    ]);

    $tag_term = Term::create(['vid' => 'tags', 'name' => 'test-tag']);
    $tag_term->save();

    $page = $this->createNode([
      'type' => 'page',
      'field_some_plain_text' => [['value' => $test_string]],
      'field_restricted_text' => [['value' => $restricted_test_string]],
      'field_article_reference' => $article,
      'field_term_reference' => $tag_term,
    ]);

    // As an admin, verify the widgets are readonly.
    $this->drupalLogin($this->admin);
    $this->drupalGet('node/' . $page->id() . '/edit');

    // Test the title field shows with a label.
    $field_wrapper = $assert->elementExists('css', '#edit-title-wrapper');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'a', $field_wrapper);
    $this->assertFieldWrapperContainsString('Title', $field_wrapper);
    $this->assertFieldWrapperContainsString($page->label(), $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-some-plain-text-wrapper');
    $this->assertFieldWrapperContainsString($test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    // This shouldn't be editable by admin, but they can view it.
    $field_wrapper = $assert->elementExists('css', '#edit-field-restricted-text-wrapper');
    $this->assertFieldWrapperContainsString($restricted_test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-article-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-article', $field_wrapper);
    $title_element = $assert->elementExists('css', 'h2 a span', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-article');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-term-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-tag', $field_wrapper);
    $title_element = $assert->elementExists('css', '.field__item a', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-tag');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    // Create a regular who can update page nodes.
    $user = $this->createUser(['edit any page content']);
    $this->drupalLogin($user);
    $this->drupalGet('node/' . $page->id() . '/edit');
    $field_wrapper = $assert->elementExists('css', '#edit-field-some-plain-text-wrapper');
    $this->assertFieldWrapperContainsString($test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    // This field is restricted via hooks in readonly_field_widget_test.module.
    $assert->elementNotExists('css', '#edit-field-restricted-text-wrapper');
    $this->assertSession()->responseNotContains($restricted_test_string);

    $field_wrapper = $assert->elementExists('css', '#edit-field-article-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-article', $field_wrapper);
    $title_element = $assert->elementExists('css', 'h2 a span', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-article');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-term-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-tag', $field_wrapper);
    $title_element = $assert->elementExists('css', '.field__item a', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-tag');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);
  }

  /**
   * Check if the field widget wrapper contains the passed in string.
   */
  private function assertFieldWrapperContainsString($string, NodeElement $element) {
    $this->assertTrue((bool) preg_match('/' . $string . '/', $element->getHtml()), "field wrapper contains '" . $string . "'");
  }

}
