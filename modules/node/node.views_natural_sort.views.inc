<?php
function node_views_natural_sort_views_data_alter(&$data) {
  $data['node']['title']['sort']['handler'] = 'node_views_natural_sort_handler_sort';
}
