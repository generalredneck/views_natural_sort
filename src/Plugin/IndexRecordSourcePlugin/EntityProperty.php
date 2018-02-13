<?php

namespace Drupal\views_natural_sort\Plugin\IndexRecordSourcePlugin;

use Drupal\views_natural_sort\Plugin\IndexRecordSourcePluginBase as EntrySourcePlugin;
use Drupal\views\ViewsData;
use Drupal\views_natural_sort\IndexRecordType as EntryType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @IndexRecordSourcePlugin (
 *   id = "entity_property",
 *   label = @Translation("Entity Property")
 * )
 */
class EntityProperty extends EntrySourcePlugin {

  protected $viewsData;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views.views_data')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsData $views_data) {
    $this->viewsData = $views_data;
  }

  public function getEntryTypes() {
    static $types = [];
    if (empty($types)) {
      foreach ($this->getViewsSupportedEntityProperties() as $entity_type => $properties) {
        foreach ($properties as $property => $schema_info) {
          $entry_types[] =  new EntryType($entity_type, $property, $this);
        }
      }
    }
    return $types;
  }

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
          if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]) &&
            !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
            !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id']) &&
            $views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] == 'natural') {
            $views_supported_properties[$entity][$property] = $schema_info;
          }
        }
      }
    }
    return $views_supported_properties;
  }


  /**
   * @see EntityViewsData::getViewsData()
   */
  public function getViewsBaseTable($fieldDefinition) {
    $entityType = $this->entityTypeManager->getDefinition($fieldDefinition->getTargetEntityTypeId());
    $base_table = $entityType->getBaseTable() ?: $entityType->id();
    $views_revision_base_table = NULL;
    $revisionable = $entityType->isRevisionable();
    $base_field = $entityType->getKey('id');

    $revision_table = '';
    if ($revisionable) {
      $revision_table = $entityType->getRevisionTable() ?: $entityType->id() . '_revision';
    }

    $translatable = $entityType->isTranslatable();
    $data_table = '';
    if ($translatable) {
      $data_table = $entityType->getDataTable() ?: $entityType->id() . '_field_data';
    }

    // Some entity types do not have a revision data table defined, but still
    // have a revision table name set in
    // \Drupal\Core\Entity\Sql\SqlContentEntityStorage::initTableLayout() so we
    // apply the same kind of logic.
    $revision_data_table = '';
    if ($revisionable && $translatable) {
      $revision_data_table = $entityType->getRevisionDataTable() ?: $entityType->id() . '_field_revision';
    }
    $revision_field = $entityType->getKey('revision');

    $views_base_table = $base_table;
    if ($data_table) {
      $views_base_table = $data_table;
    }
    //TODO Add support for finding Fields API Fields base tables. See views.views.inc.
    return $views_base_table;
  }
}
