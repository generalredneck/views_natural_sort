<?php

namespace Drupal\views_natural_sort;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\views\ViewsData;
use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationManager as TransformationManager;

/**
 * Service that manages Views Natural Sort records.
 */
class ViewsNaturalSortService {

  /**
   * Constructor.
   */
  public function __construct(TransformationManager $transformationManager, ConfigFactory $configFactory, ModuleHandlerInterface $moduleHandler, LoggerChannelFactory $loggerFactory, Connection $database, ViewsData $viewsData, QueueFactory $queue, QueueWorkerManagerInterface $queueManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, FieldTypePluginManager $fieldTypeManager) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->loggerFactory = $loggerFactory->get('views_natural_sort');
    $this->transformationManager = $transformationManager;
    $this->database = $database;
    $this->viewsData = $viewsData;
    $this->queue = $queue;
    $this->queueManager = $queueManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldTypeManager = $fieldTypeManager;
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
  public function getSupportedEntityProperties() {
    static $supported_properties = [];
    if (empty($supported_properties)) {
      foreach ($this->entityFieldManager->getFieldMap() as $entity_type => $info) {
        foreach ($info as $field_name => $field_info) {
          if ($field_info['type'] == 'string' || $field_info['type'] == 'string_long') {
            $fieldConfigs = $this->entityFieldManager->getFieldDefinitions($entity_type, reset($field_info['bundles']));
            $fieldConfig = $fieldConfigs[$field_name];
            if (empty($supported_properties[$entity_type])) {
              $supported_properties[$entity_type] = [];
            }
            $base_table = $this->getViewsBaseTable($fieldConfig);
            if (empty($base_table)) {
              continue;
            }
            $supported_properties[$entity_type][$field_name] = [
              'base_table' => $base_table,
              // This may not be techincally correct. Research Further.
              'schema_field' => $field_name,
            ];
          }

        }
      }
    }
    return $supported_properties;
  }

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

  /**
   * @see EntityViewsData::getViewsData()
   * @see views_fielf_default_views_data()
   *
   * @todo make this work for revisions as well. Probably secondary function and added to supported properties format and taken care of somehow in hook_views_data_alter.
   */
  public function getViewsBaseTable($fieldDefinition) {
    $entityType = $this->entityTypeManager->getDefinition($fieldDefinition->getTargetEntityTypeId());
    if ($fieldDefinition instanceof \Drupal\field\Entity\FieldConfig) {
      $field_storage = $fieldDefinition->getFieldStorageDefinition();
      // Check the field type is available.
      if (!$this->fieldTypeManager->hasDefinition($field_storage->getType())) {
        return FALSE;
      }
      // Check the field storage has fields.
      if (!$field_storage->getBundles()) {
        return FALSE;
      }

      // Ignore custom storage too.
      if ($field_storage->hasCustomStorage()) {
        return FALSE;
      }

      // Check whether the entity type storage is supported.
      // Change this to a required service.
      include_once \Drupal::service('module_handler')->getModule('views')->getPath(). '/views.views.inc';
      $storage = _views_field_get_entity_type_storage($field_storage);
      if (!$storage) {
        return FALSE;
      }
      if (!$base_table = $entityType->getBaseTable()) {
        return FALSE;
      }

      $field_name = $field_storage->getName();
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      $views_base_table = "{$entity_type_id}__{$field_name}";
    }
    else {
      $entityType = $this->entityTypeManager->getDefinition($fieldDefinition->getTargetEntityTypeId());
      $base_table = $entityType->getBaseTable() ?: $entityType->id();
      $views_revision_base_table = NULL;
      $revisionable = $entityType->isRevisionable();
      $base_field = $entityType->getKey('id');

      $translatable = $entityType->isTranslatable();
      $data_table = '';
      if ($translatable) {
        $data_table = $entityType->getDataTable() ?: $entityType->id() . '_field_data';
      }

      $views_base_table = $base_table;
      if ($data_table) {
        $views_base_table = $data_table;
      }
      //TODO Add support for finding Fields API Fields base tables. See views.views.inc.
    }
    return $views_base_table;
  }

}


//SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '= vns_node__field_vns_sort_text.eid AND (vns_node__field_vns_sort_text.entity_ty' at line 3: SELECT vns_node_field_data.content AS vns_node_field_data_content, vns_node__field_vns_sort_text.content AS vns_node__field_vns_sort_text_content, node_field_data.nid AS nid FROM {node_field_data} node_field_data LEFT JOIN {views_natural_sort} vns_node_field_data ON node_field_data.nid = vns_node_field_data.eid AND (vns_node_field_data.entity_type = :views_join_condition_0 AND vns_node_field_data.field = :views_join_condition_1) LEFT JOIN {node__field_vns_sort_text} node__field_vns_sort_text ON node_field_data.nid = node__field_vns_sort_text.entity_id AND (node__field_vns_sort_text.deleted = :views_join_condition_2 AND node__field_vns_sort_text.langcode = node_field_data.langcode) LEFT JOIN {views_natural_sort} vns_node__field_vns_sort_text ON node__field_vns_sort_text. = vns_node__field_vns_sort_text.eid AND (vns_node__field_vns_sort_text.entity_type = :views_join_condition_4 AND vns_node__field_vns_sort_text.field = :views_join_condition_5) WHERE node_field_data.type IN (:db_condition_placeholder_6) ORDER BY vns_node_field_data_content ASC, vns_node__field_vns_sort_text_content ASC; Array ( [:db_condition_placeholder_6] => views_natural_sort_test_content [:views_join_condition_0] => node [:views_join_condition_1] => title [:views_join_condition_2] => 0 [:views_join_condition_4] => [:views_join_condition_5] => field_vns_sort_text_value )
