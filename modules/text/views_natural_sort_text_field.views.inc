<?php

function views_natural_sort_text_field_field_views_data_alter(&$data, $field, $module) {
  // TODO: check to see if field is "registered" to be sort naturally.
  if (!in_array($field['id'], array_keys(views_natural_sort_text_field_get_configured_fields()))) {
    return;
  }
  $field_table =  _field_sql_storage_tablename($field);
  $data[$field_table][$field['field_name'] . '_value']['sort']['handler'] = 'views_natural_sort_handler_sort_text_field';
}
