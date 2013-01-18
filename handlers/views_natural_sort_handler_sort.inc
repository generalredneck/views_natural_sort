<?php
class views_natural_sort_handler_sort extends views_handler_sort {
  /**
   * Provide a list of options for the default sort form.
   * Should be overridden by classes that don't override sort_form
   */
  function sort_options() {
    return array(
      'ASC' => t('Sort ascending'),
      'DESC' => t('Sort descending'),
      'NASC' => t('Sort ascending naturally'),
      'NDESC' => t('Sort descending naturally'),
    );
  }
  function query() {
    switch ($this->options['order']) {
      case 'NASC':
      case 'NDESC':
        // TODO: figure out the values on the same row feature for this case.
        $this->ensure_my_table();
        $order = substr($this->options['order'], 1);
        $vns_alias = $this->query->add_relationship('vns_' . $this->table_alias, $this->natural_sort_join(), $this->table, $this->relationship);
        $this->query->add_orderby($vns_alias, 'content', $order);
        break;
      default:
      parent::query();
    }
  }
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
 function admin_summary() {
    if (!empty($this->options['exposed'])) {
      return t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return t('asc');
        break;
      case 'DESC':
      case 'desc':
        return t('desc');
        break;
      case 'NASC':
        return t('natural asc');
        break;
      case 'NDESC':
        return t('natural asc');
        break;
    }
  }
}
