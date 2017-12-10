<?php

namespace Drupal\Tests\views_natural_sort\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
* @group views_natural_sort
*/
class BasicTest extends ViewsKernelTestBase {

  public static $modules = ['comment', 'node', 'field', 'text', 'user', 'views_natural_sort', 'views_natural_sort_test'];

  public static $testViews = ['views_natural_sort_test'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('views_natural_sort', 'views_natural_sort');
    $this->installConfig(['node', 'user', 'comment', 'field', 'views_natural_sort']);

    ViewTestData::createTestViews(get_class($this), ['views_natural_sort_test']);
  }

  public function testNaturalSortDefaultBeginningWords() {
    $node1 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'A Stripped Zebra',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'Oklahoma',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'The King And I',
    ]);
    $node3->save();

    $view = Views::getView('views_natural_sort_test');
    $view->setDisplay();
    $view->preview('default');
    $this->assertIdenticalResultset(
      $view,
      [
        ['title' => 'The King And I'],
        ['title' => 'Oklahoma'],
        ['title' => 'A Stripped Zebra'],
      ],
      ['title' => 'title']
    );
  }

  public function testNaturalSortDefaultWords() {
    $node1 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'Red of Purple',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'Red or Green',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'Red and Blue',
    ]);
    $node3->save();

    $view = Views::getView('views_natural_sort_test');
    $view->setDisplay();
    $view->preview('default');
    $this->assertIdenticalResultset(
      $view,
      [
        ['title' => 'Red and Blue'],
        ['title' => 'Red or Green'],
        ['title' => 'Red of Purple'],
      ],
      ['title' => 'title']
    );
  }

  public function testNaturalSortDefaultSymbols() {
    $node1 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'A(Z',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'A[B',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => 'A\\C',
    ]);
    $node3->save();

    $view = Views::getView('views_natural_sort_test');
    $view->setDisplay();
    $view->preview('default');
    $this->assertIdenticalResultset(
      $view,
      [
        ['title' => 'A[B'],
        ['title' => 'A\\C'],
        ['title' => 'A(Z'],
      ],
      ['title' => 'title']
    );
  }

  /**
   * Test Unicode symbol removal in sorting.
   */
  public function testNaturalSortUnicodeSymbols() {
    $plugin = new \Drupal\views_natural_sort\Plugin\IndexRecordContentTransformation\RemoveSymbols([
      'settings' => "#…\",'\\()[]«?!»¡¿",
    ], '', '');
    $titles = [
      'Cuando… se abre, ¿dará algún tipo de señal?',
    ];
    $expected = [
      'Cuando se abre dará algún tipo de señal',
    ];
    foreach ($titles as $key => $title) {
      $this->assertEqual($plugin->transform($title), $expected[$key]);
    }
  }

  public function testNaturalSortNumbers() {
    $node1 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '1 apple',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '2 apples',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '10 apples',
    ]);
    $node3->save();
    $node4 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '-1 apples',
    ]);
    $node4->save();
    $node5 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '-10 apples',
    ]);
    $node5->save();
    $node6 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '-2 apples',
    ]);
    $node6->save();
    $node7 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '-3.550 apples',
    ]);
    $node7->save();
    $node8 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '-3.5501 apples',
    ]);
    $node8->save();
    $node9 = node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '3.5501 apples',
    ]);
    $node9->save();
    $node0 = Node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => '3.550 apples',
    ]);
    $node0->save();

    $view = Views::getView('views_natural_sort_test');
    $view->setDisplay();
    $view->preview('default');
    $this->assertIdenticalResultset(
      $view,
      [
        ['title' => '-10 apples'],
        ['title' => '-3.5501 apples'],
        ['title' => '-3.550 apples'],
        ['title' => '-2 apples'],
        ['title' => '-1 apples'],
        ['title' => '1 apple'],
        ['title' => '2 apples'],
        ['title' => '3.550 apples'],
        ['title' => '3.5501 apples'],
        ['title' => '10 apples'],
      ],
      ['title' => 'title']
    );
  }

  public function testSupportedPropertiesCoreTest() {
    $properties = \Drupal::service('views_natural_sort.service')->getViewsSupportedEntityProperties();
    $expected_result = [
      'user' =>
      [
        'name' =>
        [
          'base_table' => 'users_field_data',
          'schema_field' => 'name',
        ],
        'timezone' =>
        [
          'base_table' => 'users_field_data',
          'schema_field' => 'timezone',
        ],
      ],
      'comment' =>
      [
        'subject' =>
        [
          'base_table' => 'comment_field_data',
          'schema_field' => 'subject',
        ],
        'name' =>
        [
          'base_table' => 'comment_field_data',
          'schema_field' => 'name',
        ],
        'hostname' =>
        [
          'base_table' => 'comment_field_data',
          'schema_field' => 'hostname',
        ],
        'entity_type' =>
        [
          'base_table' => 'comment_field_data',
          'schema_field' => 'entity_type',
        ],
        'field_name' =>
        [
          'base_table' => 'comment_field_data',
          'schema_field' => 'field_name',
        ],
      ],
      'node' =>
      [
        'title' =>
        [
          'base_table' => 'node_field_data',
          'schema_field' => 'title',
        ],
      ],
    ];
    $this->assertEqual($properties, $expected_result);
  }

  public function testStoringLongUnicode() {
    $node = node::create([
      'type' => 'views_natural_sort_test_content',
      'title' => str_repeat('⌘', 255),
    ]);
    $node->save();
    $content = db_select('views_natural_sort', 'vns')
      ->fields('vns', ['content'])
      ->condition('vns.eid', $node->id())
      ->condition('vns.entity_type', 'node')
      ->execute()
      ->fetchField();
    $this->assertEqual($content, str_repeat('⌘', 255));
  }
}
