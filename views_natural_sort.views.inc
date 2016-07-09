<?php

/**
 * Implements hook_views_data_alter().
 */

function views_natural_sort_views_data_alter(array &$data) {
  $data['node_field_data']['title']['sort']['id'] = 'natural';
}
