<?php

/**
 * Helper function for writing node data to our sort index.
 *
 * @param $node
 * A drupal node object containing at least a nid and title.
 *
 * @return int
 * MergeQuery::STATUS_UPDATE or MergeQuery::STATUS_INSERT
 */
function node_views_natural_sort_store($node) {
  views_natural_sort_store(node_views_natural_sort_to_vns($node));
}

function node_views_natural_sort_to_vns($node) {
  return array(
    'eid' => $node->nid,
    'entity_type' => 'node',
    'field' => 'title',
    'delta' => 0,
    'content' => $node->title,
  );
}

/**
 * Helper function for removing node data from our sort index.
 *
 * @param $node
 * A drupal node object containing at least a nid.
 *
 * @return int
 * The number of rows deleted.
 */
function _views_natural_sort_remove_node($node) {
  return views_natural_sort_remove(array(
    'eid' => $node->nid,
    'entity_type' => 'node',
    'field' => 'title',
    'delta' => 0,
  ));
}
