<?php
/**
 * @file
 *
 * Hook Definition file for Views Natural Sort
 */

/**
 * Information used by the index rebuilding engine.
 *
 * This information is passed to each module during re-index so that modules can
 * determine whether it needs to return items or not.
 *
 * @return array $entity_types
 *   Array of arrays defining fields and entities to reindex
 *     array(
 *       array(
 *         'entity_type' - string Ex. node
 *         'field ' - string Field name to indicate during re-index
 *       ),
 *     )
 */
function hook_views_natural_sort_get_entry_types() {
  return array(
    array(
      'entity_type' => 'user',
      'field' => 'book_favorites',
    )
  );
}

/**
 * Used for a custom module to define data that needs to be re-indexed when the
 * module is installed or settings are changed.
 *
 * @param array $entry_type
 *   Array representing an entry type with an entity_type field pair.
 *     $entity_type - The type of the entity we are getting
 *                    data that needs to be re-indexed from
 *     $field - The field that needs to be re-indexed.
 *
 * @return array $index_entries An array of index entries that need re-indexing.
 */
function hook_views_natural_sort_get_rebuild_data($entry_type){
  if($entry_type['entity_type'] != 'user' || $entry_type['field'] != 'book_favorites') {
    return array();
  }
  $result = db_select('user', 'u')
    ->fields('u', array('uid', 'book_favorites'))
    ->execute();
  $data = array();
  foreach ($result as $row ) {
    // Grab the data returned and queue it up for transformation.
    $data[] = array(
      'eid' => $row->uid,
      'entity_type' => 'user',
      'field' => 'book_favorites',
      'delta' => 0,
      'content' => $row->book_favorites,
    );
  }
  return $data;
}

/**
 * Used to define custom transformations or reorder transformations.
 *
 * @param array &$transformations
 *   An array of transformations already defined.
 *
 * @param array $index_entry
 *   A representation of the original entry that is would have been put in the
 *   database before the transformation
 *     $eid - Entity Id of the item referenced
 *     $entity_type - The Entity Type. Ex. node
 *     $field - reference to the property or field name
 *     $delta - the item number in that field or property
 *     $content - The original string before
 *                transformations
 */
function hook_views_natural_sort_transformations_alter(&$transformations, $index_entry) {
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
 * @param string $string The string to be transformed.
 *
 * @return string A transformed string used for sorting "Naturally".
 */
function _my_special_transformation_function($string) {
  return str_replace('a', '', $string);
}
