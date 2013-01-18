<?php
class views_natural_sort_handler_sort_text_field extends views_natural_sort_handler_sort {

  function natural_sort_join() {
    $join = new views_join();
    $other_join = $this->get_join();
    $table_data = views_fetch_data($other_join->definition['left_table']);
    $join->definition = array(
      'table' => 'views_natural_sort',
      'field' => 'eid',
      'left_field' => 'entity_id',
      'left_table' => $this->table_alias,
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
