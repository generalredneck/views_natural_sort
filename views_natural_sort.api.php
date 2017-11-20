<?php

/**
 * @file
 * Hook Definition file for Views Natural Sort.
 */

/**
 * Information that tells VNS about entities and properties to index.
 *
 * @return array
 *   Array of arrays defining fields and entities to index
 *     array(
 *       array(
 *         'entity_type' - string Ex. node
 *         'field ' - string Field name. Lines up with property or field.
 *         'module' - string naming the module that manages indexing.
 *       ),
 *     )
 */
function hook_views_natural_sort_get_entry_types() {
  return array(
    array(
      'entity_type' => 'user',
      'field' => 'book_favorites',
      'module' => 'book_favorites_module',
    ),
  );
}

/**
 * Used to alter entry types added by other modules.
 *
 * @param array $entry_types
 *   Array of arrays defining fields and entities to index
 *     array(
 *       array(
 *         'entity_type' - string Ex. node
 *         'field ' - string Field name. Lines up with property or field.
 *         'module' - string naming the module that manages indexing.
 *       ),
 *     )
 */
function hook_views_natural_sort_get_entry_types_alter(array &$entry_types) {
  foreach ($entry_types as $key => $entry_type) {
    // We never naturally sort users, so lets nix em.
    if ($entry_type == 'user') {
      unset($entry_types[$key]);
    }
  }
}

/**
 * Used for a custom module to queue data that needs to be re-indexed.
 *
 * This is typically used when the module is installed or settings are changed.
 *
 * @param array $entry_type
 *   Array representing an entry type with an entity_type field pair.
 *     'entry_type' - The type of the entity we are getting
 *                    data that needs to be re-indexed from
 *     'field' - The field that needs to be re-indexed.
 *     'module' - string naming the module that manages indexing.
 * @param int $offset
 *   Integer representing the start of the subset we want to grab.
 * @param int $limit
 *   Integer representing the number of items in the subset we want to grab.
 */
function hook_views_natural_sort_queue_rebuild_data(array $entry_type, $offset = 0, $limit = NULL) {
  if ($entry_type['entity_type'] != 'user' || $entry_type['field'] != 'book_favorites') {
    return array();
  }
  $query = db_select('user', 'u')
    ->fields('u', array('uid', 'book_favorites'))
  if ($limit) {
    $query->range($offset, $limit);
  }
  $result = $query->execute();
  $queue = views_natural_sort_get_queue();
  foreach ($result as $row) {
    // Grab the data returned and queue it up for transformation.
    $queue->createItem = array(
      'eid' => $row->uid,
      'entity_type' => 'user',
      'field' => 'book_favorites',
      'delta' => 0,
      'content' => $row->book_favorites,
    );
  }
}

/**
 * Used for a custom module to return a count for the data being re-indexed.
 *
 * @param array $entry_type
 *   Array representing an entry type with an entity_type field pair.
 *     'entry_type' - The type of the entity we are getting
 *                    data that needs to be re-indexed from
 *     'field' - The field that needs to be re-indexed.
 *     'module' - string naming the module that manages indexing.
 *
 * @return int
 *   Integer representing the total number of items we are re-indexing.
 */
function hook_views_natural_sort_queue_rebuild_data_count(array $entry_type) {
  if ($entry_type['entity_type'] != 'user' || $entry_type['field'] != 'book_favorites') {
    return 0;
  }
  $result = db_select('user', 'u')
    ->fields('u', array('uid', 'book_favorites'))
    ->execute()
    ->rowCount();
  return $result;
}

/**
 * Used to define custom transformations or reorder transformations.
 *
 * @param array &$transformations
 *   An array of transformations already defined.
 * @param array $index_entry
 *   A representation of the original entry that is would have been put in the
 *   database before the transformation
 *     'eid' - Entity Id of the item referenced
 *     'entity_type' - The Entity Type. Ex. node
 *     'field' - reference to the property or field name
 *     'delta' - the item number in that field or property
 *     'content' - The original string before
 *                transformations.
 */
function hook_views_natural_sort_transformations_alter(array &$transformations, array $index_entry) {
  // This function will receive a single argument that is the string that needs
  // to be transformed. The transformation helps the database sort the entry
  // to be more like a human would expect it to.
  //
  // This function will return a single string as well. Note these
  // transformations happen serially, and the transformed string is passed on to
  // the next function in the list. In the example below,
  // `hook_my_special_transformation_function` will receive a string after all
  // other transformations have happened.
  $transformations[] = "_my_special_transformation_function";

  // It is worth noting that the $index_entry does have the original string in
  // it if you need to do some kind of magic. It is best to not clobber other
  // people's transformations if you can help it though.
}

/**
 * This is NOT A HOOK. Example transformation function.
 *
 * @param string $string
 *   The string to be transformed.
 *
 * @return string
 *   A transformed string used for sorting "Naturally".
 */
function _my_special_transformation_function($string) {
  return str_replace('a', '', $string);
}
