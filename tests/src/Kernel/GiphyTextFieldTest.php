<?php

namespace Drupal\Tests\giphy\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\field\Functional\String\StringFieldTest;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the creation of text fields with giphy formatter.
 *
 * @group giphy
 */
class GiphyTextFieldTest extends FieldKernelTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'giphy',
    'focal_point',
    'contextual',
    'filter',
    'node',
    'file',
    'image',
  ];


  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['giphy']);

    // Install the default image styles.
    $this->installConfig(['image']);
  }


  /**
   * Test required plain text with image upload.
   */
  public function testRequiredTextWithImageUpload() {
    // Create a text field.
    $text_field_name = 'giphy_text';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $text_field_name,
      'entity_type' => 'entity_test',
      'type' => 'string',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_name' => $text_field_name,
      'field_type' => 'string',
      'label' => $this->randomMachineName() . '_label',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();


    // Create a image field.
    $image_field_name = 'image_field';
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $image_field_name,
      'type' => 'image',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $image_field_name,
      'field_type' => 'image',
      'label' => $this->randomMachineName() . '_label',
      'bundle' => 'entity_test',
    ])->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
	  ->setComponent($text_field_name, [
		'type' => 'string_textfield',
		'settings' => array(
		),
	  ])
	  ->setComponent($image_field_name, [
		'type' => 'image_focal_point',
	  ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'default')
	  ->setComponent($text_field_name, [
		'type' => 'giphy_text',
		'settings' => array(
		  'api_key' => '3eFQvabDx69SMoOemSPiYfh9FY0nzO9x',
		  'results_number' => 1,
		),
	  ])
      ->save();


    $files = $this->drupalGetTestFiles('image');
    $image = array_pop($files);
    $edit['files[image_field_0]'] = \Drupal::service('file_system')->realpath($image->uri);
    $this->drupalPostForm('entity_test/add', $edit, 'Upload');
    $this->assertResponse(200);

    $edit = [
      'giphy_text[0][value]' => 'example',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->drupalGet('entity_test/1');
    $this->assertText('example');
  }

}
