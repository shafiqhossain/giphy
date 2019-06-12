<?php

namespace Drupal\Tests\giphy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

use Drupal\node\Entity\Node;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\node\NodeInterface;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group giphy
 */
class GiphyNodeSaveTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'giphy',
    'filter',
    'node',
    'image',
  ];

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Checks whether custom node IDs are saved properly during an import operation.
   *
   * Workflow:
   *  - first create a piece of content
   *  - save the content
   *  - check if node exists
   */
  public function testCreateGiphyNode() {
    $png = File::create([
      'uri' => 'public://test-image.png',
    ]);
    $png->save();

    // We need to create an actual empty PNG, or the GD toolkit will not
    // consider the image valid.
    $png_resource = imagecreate(300, 300);
    imagefill($png_resource, 0, 0, imagecolorallocate($png_resource, 0, 0, 0));
    imagepng($png_resource, $png->getFileUri());

    // Node ID must be a number that is not in the database.
    $nids = \Drupal::entityManager()->getStorage('node')->getQuery()
      ->sort('nid', 'DESC')
      ->range(0, 1)
      ->execute();
    $max_nid = reset($nids);
    $test_nid = $max_nid + mt_rand(1000, 1000000);
    $title = $this->randomMachineName(8);
    $node = [
      'title' => $title,
      'body' => [['value' => $this->randomMachineName(32)]],
      'uid' => $this->webUser->id(),
      'type' => 'giphy',
      'nid' => $test_nid,
	  'field_keyword' => array('example'),
	  'field_giphy_image' => [$png],
    ];

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create($node);
    $node->enforceIsNew();

    $this->assertEqual($node->getOwnerId(), $this->webUser->id());

    $node->save();
    // Test the import.
    $node_by_nid = Node::load($test_nid);
    $this->assertTrue($node_by_nid, 'Giphy Node load by node ID.');

    $node_by_title = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($node_by_title, 'Giphy Node load by node title.');
  }

}
