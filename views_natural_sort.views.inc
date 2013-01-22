<?php
/**
 * @file
 * Views related hooks.
 *
 * Automatically included by views. Because of the nature of this module this
 * is most of the code.
 */

function views_natural_sort_views_data_alter(&$data) {
  $data['node']['title']['sort']['handler'] = 'views_natural_sort_handler_sort';
}

