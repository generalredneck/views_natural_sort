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
   *
   */
  protected $isNaturalSort;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->setNaturalSort(substr($this->options['order'],0,1) == 'N');
  }

  public function query() {
    // If this field isn't being used as a Natural Sort Field, move along
    // nothing to see here.
    if (!$this->isNaturalSort()) {
      parent::query();
      return;
    }
    // If someone has submitted the exposed form, lets grab it here
    if ($this->options['exposed'] && $this->view->exposed_data['sort_order']) {
      $temporder = $this->view->exposed_data['sort_order'];
    }
    // If we are using this like a normal sort, our info will be here.
    else {
      $temporder = &$this->options['order'];
    }

    // Add the Views Natural Sort table for this field.
    $vns_alias = 'vns_' . $this->tableAlias;
    if (empty($this->query->relationships[$vns_alias])) {
      $this->ensureMyTable();
      $vns_alias = $this->query->addRelationship('vns_' . $this->tableAlias, $this->naturalSortJoin(), $this->table, $this->relationship);
    }
    // Sometimes we get the appended N from the sort options. Filter it out here.
    $order = substr($temporder, 0, 1) == 'N' ? substr($temporder, 1) : $temporder;
    $this->query->addOrderBy($vns_alias, 'content', $order);
  }
  public function naturalSortJoin() {
    $table_data = Views::viewsData()->get($this->table);
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
      ]
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    return $join;
  }

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
    $label = parent::adminSummary();
    switch ($this->options['order']) {
      case 'NASC':
        return $this->t('natural asc');
        break;
      case 'NDESC':
        return $this->t('natural asc');
        break;
      default:
        return $label;
        break;
    }
  }

  public function isNaturalSort() {
    return $this->isNaturalSort;
  }

  protected function setNaturalSort($value) {
    $this->isNaturalSort = $value;
  }

}
