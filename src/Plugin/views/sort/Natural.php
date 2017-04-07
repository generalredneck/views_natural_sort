<?php

namespace Drupal\views_natural_sort\Plugin\views\sort;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort plugin used to allow Natural Sorting.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("natural")
 */
class Natural extends SortPluginBase {

  /**
   * Flag defining this particular sort as Natural or not.
   *
   * @var bool
   */
  protected $isNaturalSort;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->setNaturalSort(substr($this->options['order'], 0, 1) == 'N');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If this field isn't being used as a Natural Sort Field, move along
    // nothing to see here.
    if (!$this->isNaturalSort()) {
      parent::query();
      return;
    }

    // Add the Views Natural Sort table for this field.
    $vns_alias = 'vns_' . $this->tableAlias;
    if (empty($this->query->relationships[$vns_alias])) {
      $this->ensureMyTable();
      $vns_alias = $this->query->addRelationship('vns_' . $this->tableAlias, $this->naturalSortJoin(), $this->table, $this->relationship);
    }
    $this->query->addOrderBy($vns_alias, 'content', substr($this->options['order'], 1));
  }

  /**
   * Adds the views_natural_sort table to the query.
   *
   * @return Drupal\views\Plugin\views\join\Standard
   *   Join object containing views_natural_sort table.
   */
  public function naturalSortJoin() {
    $storage = Views::viewsData()->getAll();
    $table_data = $storage[$this->table];
    $configuration = [
      'table' => 'views_natural_sort',
      'field' => 'eid',
      'left_field' => $table_data['table']['base']['field'],
      'left_table' => $this->table,
      'extra' => [
        [
          'field' => 'entity_type',
          'value' => $table_data['table']['entity type'],
        ],
        [
          'field' => 'field',
          'value' => $this->realField,
        ],
      ],
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    return $join;
  }

  /**
   * {@inheritdoc}
   */
  protected function sortOptions() {
    $options = parent::sortOptions();
    $options['NASC'] = $this->t('Sort ascending naturally');
    $options['NDESC'] = $this->t('Sort descending naturally');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('Exposed');
    }
    $label = parent::adminSummary();
    switch ($this->options['order']) {
      case 'NASC':
        return $this->t('natural asc');

      case 'NDESC':
        return $this->t('natural asc');

      default:
        return $label;
    }
  }

  /**
   * Determines if this query is natural sort.
   *
   * @return bool
   *   True if natural sort, False otherwise.
   */
  public function isNaturalSort() {
    return $this->isNaturalSort;
  }

  /**
   * Sets the natural sort flag.
   *
   * @param bool $value
   *   The value.
   */
  protected function setNaturalSort($value) {
    $this->isNaturalSort = $value;
  }

}
