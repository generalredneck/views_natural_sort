<?php
/**
 * @file
 * Views related hooks.
 *
 * Automatically included by views. Because of the nature of this module this
 * is most of the code.
 */

/**
 * Implementation of hook_views_data().
 */
function views_natural_sort_views_data() {

  $data['views_natural_sort']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'eid',
      'extra' => array(
        array(
          'field' => 'entity_type',
          'value' => 'node',
        )
      )
    ),
  );
  return $data;
}

function views_natural_sort_views_data_alter(&$data) {
  // TODO: Add a field and argument handler to support grouping.
  $data['node']['title']['sort']['handler'] = 'views_natural_sort_handler_sort';
}

function views_natural_sort_field_views_data_alter(&$data, $field, $module) {
  // TODO: check to see if field is "registered" to be sort naturally.
  if ($module != 'text' || $field['storage']['type'] != 'field_sql_storage') {
    return;
  }
  foreach ($field['bundles'] as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);
    $field_table =  _field_sql_storage_tablename($field);
    $view_fields = array();
    foreach($field['columns'] as $column_name => $column_data) {

      // See field_views_field_default_views_data. it says only varchar is worth sorting.
      if ($column_data['type'] != 'varchar') {
        continue;
      }

      // Stash the field names so we can join on them later.
      $current_view_field_column = $field['field_name'] . '_' . $column_name;

      $view_fields[] = $current_view_field_column;
      $data[$field_table][$current_view_field_column]['sort']['handler'] = 'views_natural_sort_handler_sort';
    }
    $data['views_natural_sort']['table']['join'][$field_table] = array(
      'left_field' => 'entity_id',
      'field' => 'eid',
      'extra' => array(
        array(
          'field' => 'entity_type',
          'value' => $entity_type,
        ),
        array(
          'field' => 'field',
          'value' => $view_fields,
        ),
      ),
    );
  }
}
