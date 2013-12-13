<?php
class node_views_natural_sort_handler_sort extends views_handler_natural_sort {
  function natural_sort_join() {
    $join = new views_join();
    $table_data = views_fetch_data($this->table);
    $join->definition = array(
      'table' => 'views_natural_sort',
      'field' => 'eid',
      'left_field' => $table_data['table']['base']['field'],
      'left_table' => $this->table,
      'extra' => array(
        array(
          'field' => 'entity_type',
          'value' => $table_data['table']['entity type'],
        ),
        array(
          'field' => 'field',
          'value' => $this->real_field,
        ),
      )
    );
    $join->construct();
    $join->adjusted = TRUE;
    return $join;
  }
}
