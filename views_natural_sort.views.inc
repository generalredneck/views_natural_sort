<?php
// $Id$
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
      'field' => 'nid',
      // We only support sorting on title so go ahead and build filter it into the join.
      'extra' => array(
        array(
          'field' => 'field',
          'value' => 'title',
          'operator' => '=',
        )
      )
    ),
  );

  $data['views_natural_sort']['content'] = array(
    'title' => 'Title - Natural',
    'group' => t('Node'),
    'help' => t('Sort title using a natural sort.'),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  return $data;
}
