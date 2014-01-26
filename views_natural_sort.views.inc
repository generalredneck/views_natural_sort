<?php
/**
 * @file
 * Views related hooks.
 *
 * Automatically included by views. Because of the nature of this module this
 * is most of the code.
 */

function views_natural_sort_views_data_alter(&$data) {
  // Get all varchar fields from schema info
  $sortable_fields = views_natural_sort_get_sortable_fields();
  foreach ($sortable_fields as $table => $fields) {
    foreach($fields as $field) {
      $data[$table][$field]['sort']['handler'] = 'views_natural_sort_handler_sort';
    }
  }
}

