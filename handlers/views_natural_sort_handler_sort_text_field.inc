<?php

/**
 * @file
 * Constains the views natural sort text field sorting handler.
 */

/**
 * The text field sort handler for views.
 */
class views_natural_sort_handler_sort_text_field extends views_natural_sort_handler_sort {

  /**
   * {@inheritdoc}
   */
  public function natural_sort_join() {
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
          'field' => 'delta',
          'value' => $this->table_alias . '.delta',
        ),
        array(
          'field' => 'entity_type',
          'value' => $table_data['table']['entity type'],
        ),
        array(
          'field' => 'field',
          'value' => preg_replace('/_value$/', '', $this->field),
        ),
      ),
    );
    $join->construct();
    $join->adjusted = TRUE;
    return $join;
  }

}
