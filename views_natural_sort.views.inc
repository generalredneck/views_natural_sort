<?php

/**
 * Implements hook_views_data_alter().
 */

function views_natural_sort_views_data_alter(array &$views_data) {
  $supported_entity_properties = \Drupal::service('views_natural_sort.service')->getSupportedEntityProperties();
  foreach ($supported_entity_properties as $entity => $properties) {
    foreach ($properties as $property => $schema_info) {
      if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]) &&
          !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
          !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] == 'standard')) {
        $views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] = 'natural';
      }
      $schema_fields[$schema_info['base_table']][] = $schema_info['schema_field'];
    }
  }
}
