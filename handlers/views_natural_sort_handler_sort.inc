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
        $this->ensure_my_table();
        $order = substr($this->options['order'], 1);
        $vns_alias = $this->query->ensure_table('views_natural_sort');
        $this->query->add_orderby($vns_alias, 'content', $order);
        break;
      default:
      parent::query();
    }
  }
}
