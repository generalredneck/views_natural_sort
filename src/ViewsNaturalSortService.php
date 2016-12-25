<?php

namespace Drupal\views_natural_sort;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Database\Connection;
use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationManager as TransformationManager;

/**
 * Service that manages Views Natural Sort records.
 */
class ViewsNaturalSortService {

  public $config;

  /**
   * Constructor.
   */
  public function __construct(TransformationManager $transformationManager, ConfigFactory $configFactory, ModuleHandlerInterface $moduleHandler, LoggerChannelFactory $loggerFactory, Connection $database) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->loggerFactory = $loggerFactory->get('views_natural_sort');
    $this->transformationManager = $transformationManager;
    $this->database = $database;
  }

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

  public function storeIndexRecordsFromEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $supported_entity_properties = $this->getSupportedEntityProperties();
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

  public function createIndexRecord(array $values = []) {
    $record = new IndexRecord($this->database, $values);
    $transformations = $this->getTransformations($record);
    $record->setTransformations($transformations);
    return $record;
  }

}
