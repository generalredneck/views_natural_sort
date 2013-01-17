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
