<?php

/**
 * Implements hook_views_data_alter().
 */

function views_natural_sort_views_data_alter(array &$views_data) {
  $supported_entity_properties = \Drupal::service('views_natural_sort.service')->getSupportedEntityProperties();
  foreach ($supported_entity_properties as $entity => $properties) {
    foreach ($properties as $property => $schema_info) {
      $sort_plugin = 'natural';
      if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']])) {
        if (isset($views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'])) {
          //TODO: See error in this case. Switch to Natural Field sort handler or something.
          $schema_info['schema_field'] = $views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'];
          $sort_plugin = 'natural_field';
        }
        if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
          !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] == 'standard')) {
        $views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] = $sort_plugin;
        }
      }
      $schema_fields[$schema_info['base_table']][] = $schema_info['schema_field'];
    }
  }
}
