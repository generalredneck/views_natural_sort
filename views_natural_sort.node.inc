<?php
/**
 * @file
 *
 * Node specific views_natural_sort code.
 */

/**
 * Helper function for writing node data to our sort index.
 *
 * @param $node
 *   A drupal node object containing at least a nid and title.
 */
function _views_natural_sort_store_node($node) {
  views_natural_sort_store(_views_natural_sort_node_to_vns($node));
}

/**
 * Helper function for getting an index_entry from a node object.
 *
 * @param stdClass $node
 *   Node Entity
 *
 * @return array
 *   index_entry array
 */
function _views_natural_sort_node_to_vns($node) {
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
 *   A drupal node object containing at least a nid.
 *
 * @return int
 *   The number of rows deleted.
 */
function _views_natural_sort_remove_node($node) {
  return views_natural_sort_remove(array(
    'eid' => $node->nid,
    'entity_type' => 'node',
    'field' => 'title',
    'delta' => 0,
  ));
}
