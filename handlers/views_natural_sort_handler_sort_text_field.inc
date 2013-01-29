<?php
class views_natural_sort_handler_sort_text_field extends views_natural_sort_handler_sort {

  function natural_sort_join() {
    $join = new views_join();
    $other_join = $this->get_join();
    $table_data = views_fetch_data($other_join->definition['left_table']);

    // If you look at where natural_sort_join is called, this alias will be used.
    $vns_alias = 'vns_' . $this->table_alias;

    // Make the join. Can't use extra as an array because I have to join on two
    // fields and not 1 field with other fields == to a hard coded value.
    // I don't like this because it's insecure. Look for better way.
    // @see http://drupal.org/node/1090432
    $join->definition = array(
      'table' => 'views_natural_sort',
      'field' => 'eid',
      'left_field' => 'entity_id',
      'left_table' => $this->table_alias,
      'extra' => $vns_alias . '.delta = ' . $this->table_alias . '.delta AND ' .
        $vns_alias . ".entity_type = '" . $table_data['table']['entity type'] . "' AND " .
        $vns_alias . ".field = '" . preg_replace('/_value$/', '', $this->field) . "'",
      /*'extra' => array(
        "views_natural_sort.delta = $this->table_alias.delta",
        array(
          'field' => 'entity_type',
          'value' => $table_data['table']['entity type'],
        ),
        array(
          'field' => 'field',
          'value' => preg_replace('/_value$/', '', $this->field),
        ),
      )*/
    );
    $join->construct();
    $join->adjusted = TRUE;
    return $join;
  }
}
