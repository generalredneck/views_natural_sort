<?php
function text_views_natural_sort_field_views_data_alter(&$data, $field, $module) {
  // TODO: check to see if field is "registered" to be sort naturally.
  if (!in_array($field['id'], array_keys(text_get_configured_fields()))) {
    return;
  }
  $field_table =  _field_sql_storage_tablename($field);
  $data[$field_table][$field['field_name'] . '_value']['sort']['handler'] = 'natural_sort_join';
}
