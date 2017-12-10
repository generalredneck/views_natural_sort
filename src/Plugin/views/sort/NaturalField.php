<?php

namespace Drupal\views_natural_sort\Plugin\views\sort;

use Drupal\views\Views;

/**
 * Sort plugin used to allow Natural Sorting.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("natural_field")
 */
class NaturalField extends Natural {

  public function naturalSortJoin() {
    //TODO DEBUG the stupid query.
    $other_join = $this->getJoin();
    $storage = Views::viewsData()->getAll();
    $table_data = $storage[$other_join->leftTable];
    $configuration = [
      'table' => 'views_natural_sort',
      'field' => 'eid',
      'left_field' => 'entity_id',
      'left_table' => $this->tableAlias,
      'extra' => [
        [
          'field' => 'delta',
          'value' => $this->tableAlias . '.delta',
        ],
        [
          'field' => 'entity_type',
          'value' => $table_data['table']['entity type'],
        ],
        [
          'field' => 'field',
          'value' => preg_replace('/_value$/', '', $this->field),
        ],
      ],
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    return $join;
  }
}

/*
SELECT vns_node__field_vns_sort_text.content AS vns_node__field_vns_sort_text_content, node_field_data.nid AS nid
FROM
{node_field_data} node_field_data
LEFT JOIN {node__field_vns_sort_text} node__field_vns_sort_text ON node_field_data.nid = node__field_vns_sort_text.entity_id AND (node__field_vns_sort_text.deleted = '0' AND node__field_vns_sort_text.langcode = node_field_data.langcode)
LEFT JOIN {views_natural_sort} vns_node__field_vns_sort_text ON node__field_vns_sort_text.entity_id = vns_node__field_vns_sort_text.eid AND (vns_node__field_vns_sort_text.delta = 'node__field_vns_sort_text.delta' AND vns_node__field_vns_sort_text.entity_type = 'node' AND vns_node__field_vns_sort_text.field = 'field_vns_sort_text')
WHERE node_field_data.type IN ('views_natural_sort_test_content')
ORDER BY vns_node__field_vns_sort_text_content DESC
*/
