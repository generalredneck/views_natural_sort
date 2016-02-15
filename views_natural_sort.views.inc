<?php
/**
 * @file
 * Views related hooks.
 *
 * Automatically included by views. Because of the nature of this module this
 * is most of the code.
 */

function views_natural_sort_views_data_alter(&$views_data) {
  $supported_entity_properties = views_natural_sort_get_supported_entity_properties();
  $schema_fields = array();
  foreach($supported_entity_properties as $entity => $properties) {
    foreach($properties as $property => $schema_info) {
      if (empty($schema_fields[$schema_info['base_table']])) {
        $schema_fields[$schema_info['base_table']] = array();
      }
      $schema_fields[$schema_info['base_table']][] = $schema_info['schema_field'];
    }
  }
  foreach ($views_data as $table => &$data) {
    if (empty($schema_fields[$table])) {
      continue;
    }
    foreach ($data as $view_field => &$view_field_data) {
      if (isset($view_field_data['field']) &&
        isset($view_field_data['sort']) &&
        $view_field_data['sort']['handler'] == 'views_handler_sort' &&
        ((isset($view_field_data['field']['field']) && in_array($view_field_data['field']['field'], $schema_fields[$table])) ||
          in_array($view_field, $schema_fields[$table]))) {
          // We've verified that this is a perfect candidate to add our sort
          // handler to.
          $view_field_data['sort']['handler'] = 'views_natural_sort_handler_sort';
      }
    }
  }
}
