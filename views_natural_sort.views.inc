<?php

/**
 * Implements hook_views_data_alter().
 */

function views_natural_sort_views_data_alter(array &$views_data) {
  $supported_entity_properties = \Drupal::service('views_natural_sort.service')->getSupportedEntityProperties();
  foreach ($supported_entity_properties as $entity => $properties) {
    foreach ($properties as $property => $schema_info) {
      if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']])) {
        if (isset($views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'])) {
          $schema_info['schema_field'] = $views_data[$schema_info['base_table']][$schema_info['schema_field']]['field']['real field'];
        }
        if (!empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']) &&
          !empty($views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] == 'standard')) {
        $views_data[$schema_info['base_table']][$schema_info['schema_field']]['sort']['id'] = 'natural';
            var_dump($schema_info);
        }
      }
      $schema_fields[$schema_info['base_table']][] = $schema_info['schema_field'];
    }
  }
}
