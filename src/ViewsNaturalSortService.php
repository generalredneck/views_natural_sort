<?php

namespace Drupal\views_natural_sort;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Database\Connection;
use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationManager as TransformationManager;
use Drupal\views\ViewsData;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Service that manages Views Natural Sort records.
 */
class ViewsNaturalSortService {

  /**
   * Constructor.
   */
  public function __construct(TransformationManager $transformationManager, ConfigFactory $configFactory, ModuleHandlerInterface $moduleHandler, LoggerChannelFactory $loggerFactory, Connection $database, ViewsData $viewsData, QueueFactory $queue, QueueWorkerManagerInterface $queueManager) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->loggerFactory = $loggerFactory->get('views_natural_sort');
    $this->transformationManager = $transformationManager;
    $this->database = $database;
    $this->viewsData = $viewsData;
    $this->queue = $queue;
    $this->queueManager = $queueManager;
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
      $supported_properties = [
        'node' => [
          'title' => [
            'base_table' => 'node_field_data',
            'schema_field' => 'title',
          ],
        ],
      ];
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
          if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]) &&
            !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
            !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['handler']) &&
            in_array($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['handler'], array('views_natural_sort_handler_sort', 'views_handler_sort'))) {
            $views_supported_properties[$entity][$property] = $schema_info;
          }
        }
      }
    }
    return $views_supported_properties;
  }

  public function storeIndexRecordsFromEntity(EntityInterface $entity) {
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

  public function rebuildIndex($queue_name, &$context) {
    /** @var QueueInterface $queue */
    $queue = $this->queueFactory->get($queue_name);
    /** @var QueueWorkerInterface $queue_worker */
    $queue_worker = $this->queueManager->createInstance($queue_name);
    $config = $this->configFactory->get('views_natural_sort.settings');

    // Alias sandbox for easier referencing.
    $sandbox = &$context['sandbox'];
    // Alias results for easier referencing.
    $results = &$context['results'];
    if (empty($sandbox)) {
      $sandbox['current'] = 0;
      $sandbox['max'] = $queue->numberOfItems();
      $sandbox['items_per_batch'] = $config->get('rebuild_items_per_batch');
    }
    for ($i = 0; $i < $sandbox['items_per_batch'] && $sandbox['current'] < $sandbox['max']; $i++) {

      while($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          watchdog_exception('npq', $e);
        }
      }
      $item = $queue->claimItem(10);
      if ($item) {
        views_natural_sort_process_index_queue($item->data);
        $queue->deleteItem($item);
      }
      $sandbox['current']++;
    }
    $results['entries'] = $sandbox['current'];
    if ($sandbox['current'] != $sandbox['max']) {
      $context['finished'] = $sandbox['current'] / $sandbox['max'];
    }
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
