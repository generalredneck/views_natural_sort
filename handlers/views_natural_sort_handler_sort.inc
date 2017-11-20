<?php

/**
 * @file
 * Constains the views natural sort sorting handler.
 */

/**
 * Sort handler for sorting records naturally.
 */
class views_natural_sort_handler_sort extends views_handler_sort {

  /**
   * {@inheritdoc}
   */
  public function init(&$view, &$options) {
    parent::init($view, $options);
    $this->natural_sort = substr($this->options['order'], 0, 1) == 'N';
  }

  /**
   * {@inheritdoc}
   */
  public function sort_options() {
    return array(
      'ASC' => t('Sort ascending'),
      'DESC' => t('Sort descending'),
      'NASC' => t('Sort ascending naturally'),
      'NDESC' => t('Sort descending naturally'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If this field isn't being used as a Natural Sort Field, move along
    // nothing to see here.
    if (!$this->natural_sort) {
      parent::query();
      return;
    }

    // If someone has submitted the exposed form, lets grab it here.
    if ($this->options['exposed'] && isset($this->view->exposed_data['sort_order'])) {
      $temporder = $this->view->exposed_data['sort_order'];
    }
    // If we are using this like a normal sort, our info will be here.
    else {
      $temporder = &$this->options['order'];
    }

    // Add the Views Natural Sort table for this field.
    $vns_alias = 'vns_' . $this->table_alias;
    if (empty($this->query->relationships[$vns_alias])) {
      $this->ensure_my_table();
      $vns_alias = $this->query->add_relationship('vns_' . $this->table_alias, $this->natural_sort_join(), $this->table, $this->relationship);
    }
    // Sometimes we get the appended N from the sort options. Filter it out
    // here.
    $order = substr($temporder, 0, 1) == 'N' ? substr($temporder, 1) : $temporder;
    $this->query->add_orderby($vns_alias, 'content', $order);
  }

  /**
   * Helper function that creates a join to the views natural sort table.
   */
  public function natural_sort_join() {
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
      ),
    );
    $join->construct();
    $join->adjusted = TRUE;
    return $join;
  }

  /**
   * {@inheritdoc}
   */
  public function admin_summary() {
    if (!empty($this->options['exposed'])) {
      return t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return t('asc');

      case 'DESC':
      case 'desc':
        return t('desc');

      case 'NASC':
        return t('natural asc');

      case 'NDESC':
        return t('natural asc');
    }
  }

}
