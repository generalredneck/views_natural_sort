<?php

namespace Drupal\views_natural_sort;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\views\ViewsData;
use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationManager as TransformationManager;
use Drupal\views_natural_sort\Plugin\IndexRecordSourcePluginManager as EntrySourcePluginManager;

/**
 * Service that manages Views Natural Sort records.
 */
class ViewsNaturalSortService {

  /**
   * Constructor.
   */
  public function __construct(
    TransformationManager $transformationManager,
    EntrySourcePluginManager $entry_source_plugin_manager,
    ConfigFactory $configFactory,
    ModuleHandlerInterface $moduleHandler,
    LoggerChannelFactoryInterface $loggerFactory,
    Connection $database,
    ViewsData $viewsData,
    QueueFactory $queue,
    QueueWorkerManagerInterface $queueManager
    ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->loggerFactory = $loggerFactory->get('views_natural_sort');
    $this->transformationManager = $transformationManager;
    $this->database = $database;
    $this->viewsData = $viewsData;
    $this->queue = $queue;
    $this->queueManager = $queueManager;
    $this->entrySourcePluginManager = $entry_source_plugin_manager;
  }

  /**
   * Get the full list of transformations to run when saving an index record.
   *
   * @param \Drupal\views_natural_sort\IndexRecord $record
   *   The original entry to be written to the views_natural_sort table.
   *
   * @return array
   *   The final list of transformations.
   */
  public function getTransformations(IndexRecord $record) {
    $transformations = $this->getDefaultTransformations();
    $this->moduleHandler->alter('views_natural_sort_transformations', $transformations, $record);
    return $transformations;
  }

  public function getDefaultTransformations() {
    $default_transformations = [
      'remove_beginning_words',
      'remove_words',
      'remove_symbols',
      'numbers',
      'days_of_the_week',
    ];
    $config = $this->configFactory->get('views_natural_sort.settings');
    $transformations = [];
    foreach ($default_transformations as $plugin_id) {
      if ($config->get('transformation_settings.' . $plugin_id . '.enabled')) {
        $transformations[] = $this->transformationManager->createInstance($plugin_id, $config->get('transformation_settings.' . $plugin_id));
      }
    }
    return $transformations;
  }

  public function getEntryTypes() {
    static $entry_types = [];
    if (empty($entry_types)) {
      $entry_sources = $this->entrySourcePluginManager->getDefinitions();
      foreach ($entry_sources as $plugin_id => $definition) {
        $entry_source_plugin = $this->entrySourcePluginManager->createInstance($plugin_id);
        foreach ($entry_source_plugin->getEntryTypes() as $entry_type) {
          $entry_types[$entry_type->getEntityType() . '|' . $entry_type->getField()] = $entry_type;
        }
      }
      $this->moduleHandler->alter('views_natural_sort_get_entry_types', $entry_types);
    }
    return $entry_types;
  }

  /**
   * Retrieve the full list of entities and properties that can be supported.
   *
   * @return array
   *   An array of property information keyed by entity machine name. Example:
   *   [
   *     'node' => [
   *       'type' => [
   *         'base_table' => 'node',
   *         'schema_field' => 'type',
   *       ]
   *       'title' => [
   *         'base_table' => 'node',
   *         'schema_field' => 'title',
   *       ]
   *       'language' => [
   *         'base_table' => 'node',
   *         'schema_field' => 'language',
   *       ]
   *     ]
   *     'user' => [
   *       'name' => [
   *         'base_table' => 'users',
   *         'schema_field' => 'name',
   *       ]
   *       'mail' => [
   *         'base_table' => 'users',
   *         'schema_field' => 'mail',
   *       ]
   *       'theme' => [
   *         'base_table' => 'users',
   *         'schema_field' => 'theme',
   *       ]
   *     ]
   *     'file' => [
   *       'name' => [
   *         'base_table' => 'file_managed',
   *         'schema_field' => 'filename',
   *       ]
   *       'mime' => [
   *         'base_table' => 'file_managed',
   *         'schema_field' => 'filemime',
   *       ]
   *     ]
   *   )
   */

  public function getViewsSupportedEntityProperties() {
    static $views_supported_properties = [];
    if (empty($views_supported_properties)) {
      $supported_entity_properties = $this->getSupportedEntityProperties();
      $views_data = $this->viewsData->getAll();

      if (empty($views_data)) {
        return FALSE;
      }
      foreach ($supported_entity_properties as $entity => $properties) {
        foreach ($properties as $property => $schema_info) {
          if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']])) {
            if (isset($views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'])) {
              $schema_info['schema_field'] = $views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'];
            }
            if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
              !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id']) &&
              $views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] == 'natural') {
              $views_supported_properties[$entity][$property] = $schema_info;
            }
          }
        }
      }
    }
    return $views_supported_properties;
  }

  public function getSupportedEntityProperties() {
    // Load entrySourcePlugins here.
    $supported_entity_properties = [];
    foreach ($this->getEntryTypes() as $entry_type) {
      array_merge($supported_entity_properties, $entry_type->getSupportedEntityProperties());
    }
    return $supported_entity_properties;

    //$info = $this->entityTypeBundleInfo->getAllBundleInfo();
    //$this->entityFieldManager->getFieldDefinitions('node',)
  }
  public function storeIndexRecordsFromEntity(EntityInterface $entity) {
    // TODO: Consider abstracting this out. The creation and storage of records
    // should be handled by a converter class that interacts with specific
    // IndexRecordTypes and creates IndexRecords. Those would probably be called
    // directly and have nothign to do with this service.
    $entity_type = $entity->getEntityTypeId();
    $supported_entity_properties = $this->getViewsSupportedEntityProperties();
    foreach ($supported_entity_properties[$entity_type] as $field => $field_info) {
      if (!isset($entity->{$field})) {
        continue;
      }
      foreach ($entity->get($field)->getValue() as $delta => $value) {
        $record = $this->createIndexRecord([
          'eid' => $entity->id(),
          'entity_type' => $entity_type,
          'field' => $field,
          'delta' => $delta,
          // This may have to be passed in if it's not always ['value'].
          'content' => $value['value'],
        ]);
        $record->save();
      }
    }
  }

  public function queueDataForRebuild(array $entry_types = []) {
    if (empty($entry_types)) {
      $entry_types = $this->moduleHandler->invokeAll('views_natural_sort_get_entry_types');
    }
    $queues = [];
    foreach ($entry_types as $entry_type) {
      $queues = array_unique(array_merge($queues, array_filter($this->moduleHandler->invokeAll('views_natural_sort_queue_rebuild_data', $entry_type))));
    }
    $operations = [];
    foreach ($queues as $queue) {
      $operations[] = [
        [$this, 'rebuildIndex'],
        [$queue],
      ];
    }
    $batch = [
      'operations' => $operations,
      'title' => t('Rebuilding Views Natural Sort Indexing Entries'),
      'finished' => [$this, 'finishRebuild'],
    ];
    batch_set($batch);
  }

  public function finishRebuild($success, $results, $operations) {
    if ($success) {
      drupal_set_message($this->t('Index rebuild has completed.'));
      drupal_set_message($this->t('Indexed %count.', [
        '%count' => format_plural($results['entries'], '1 entry', '@count entries'),
      ]));
    }
  }

  public function createIndexRecord(array $values = []) {
    $record = new IndexRecord($this->database, $values);
    $transformations = $this->getTransformations($record);
    $record->setTransformations($transformations);
    return $record;
  }


}


//SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '= vns_node__field_vns_sort_text.eid AND (vns_node__field_vns_sort_text.entity_ty' at line 3: SELECT vns_node_field_data.content AS vns_node_field_data_content, vns_node__field_vns_sort_text.content AS vns_node__field_vns_sort_text_content, node_field_data.nid AS nid FROM {node_field_data} node_field_data LEFT JOIN {views_natural_sort} vns_node_field_data ON node_field_data.nid = vns_node_field_data.eid AND (vns_node_field_data.entity_type = :views_join_condition_0 AND vns_node_field_data.field = :views_join_condition_1) LEFT JOIN {node__field_vns_sort_text} node__field_vns_sort_text ON node_field_data.nid = node__field_vns_sort_text.entity_id AND (node__field_vns_sort_text.deleted = :views_join_condition_2 AND node__field_vns_sort_text.langcode = node_field_data.langcode) LEFT JOIN {views_natural_sort} vns_node__field_vns_sort_text ON node__field_vns_sort_text. = vns_node__field_vns_sort_text.eid AND (vns_node__field_vns_sort_text.entity_type = :views_join_condition_4 AND vns_node__field_vns_sort_text.field = :views_join_condition_5) WHERE node_field_data.type IN (:db_condition_placeholder_6) ORDER BY vns_node_field_data_content ASC, vns_node__field_vns_sort_text_content ASC; Array ( [:db_condition_placeholder_6] => views_natural_sort_test_content [:views_join_condition_0] => node [:views_join_condition_1] => title [:views_join_condition_2] => 0 [:views_join_condition_4] => [:views_join_condition_5] => field_vns_sort_text_value )
