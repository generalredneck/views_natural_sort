<?php
/**
 * @file
 * Callbacks for managing Views Natural Sort.
 */

/**
 * Views Natural Sort Admin Settings Page callback.
 */
function views_natural_sort_settings_page() {
  $content = array(
    'settings' => drupal_get_form('views_natural_sort_settings_form'),
    'rebuild' => drupal_get_form('views_natural_sort_rebuild_index_form'),
  );
  return $content;
}

/**
 * Form callback for Views Natural Sort Rebuild Index page.
 *
 * Allows rebuilding index but should also allow things like limiting what node
 * types are indexed and cck text field index options.
 */
function views_natural_sort_rebuild_index_form() {
  $form = array();

  $form['rebuild'] = array(
    '#type' => 'fieldset',
    '#title' => t('Incase of Emergency'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,

    'button' => array(
      '#type' => 'submit',
      '#description' => 'Incase of an emergency.',
      '#value' => t('Rebuild Index'),
      '#submit' => array('views_natural_sort_rebuild_index_submit'),
    ),
  );

  return $form;
}

/**
 * Form callback for the Views Natural Sort settings page
 *
 * Allows the removal of specific words and symbols from all your titles.
 */
function views_natural_sort_settings_form() {
  $form = array();

  $form['beginning_words'] = array(
    '#type' => 'textfield',
    '#title' => 'Words to filter from the beginning of a phrase',
    '#default_value' => implode(',', variable_get('views_natural_sort_beginning_words_remove', array())),
    '#description' => t('Commonly, the words "A", "The", and "An" are removed when sorting book titles if they appear at the beginning of the title. Those would be great candidates for this field. Separate words with a comma.'),
  );

  $form['words'] = array(
    '#type' => 'textfield',
    '#title' => 'Words to filter from anywhere in a phrase',
    '#default_value' => implode(',', variable_get('views_natural_sort_words_remove', array())),
    '#description' => t('Commonly used words like "of", "and", and "or" are removed when sorting book titles. Words you would like filtered go here. Separate words with a comma.'),
  );

  $form['symbols'] = array(
    '#type' => 'textfield',
    '#title' => 'Symbols to filter from anywhere in a phrase',
    '#default_value' => variable_get('views_natural_sort_symbols_remove', ''),
    '#description' => t('Most symbols are ignored when performing a sort naturally. Those symbols you want ignored go here. Do not use a separator. EX: &$".'),
  );
  $form['days_of_the_week_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => 'Sort days of the week and their abbreviations',
    '#description' => "Checking this setting will allow sorting of days of the week in their proper order starting with the day of the week that is configurable by you and for each language.",
    '#efault_value' => variable_get('views_natural_sort_days_of_the_week_enabled', FALSE),
  );
  $form['batch_items'] = array(
    '#type' => 'textfield',
    '#title' => 'Items per Batch',
    '#default_value' => variable_get('views_natural_sort_rebuild_items_per_batch', '500'),
    '#description' => t('The number of items a batch process will work through at a given time. Raising this number will make the batch go quicker, however, raising it too high can cause timeouts and/or memory limit errors.'),
    '#element_validate' => array('element_validate_integer_positive'),
  );
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save Settings'),
  );

  return $form;
}

/**
 * Submit handler that saves custom word handlers and other settings.
 */
function views_natural_sort_settings_form_submit($form, &$form_state) {
  $beginning_words = explode(',',$form_state['values']['beginning_words']);
  array_walk($beginning_words, create_function('&$val', '$val = trim($val);'));
  $words = explode(',',$form_state['values']['words']);
  array_walk($words, create_function('&$val', '$val = trim($val);'));
  $symbols = trim($form_state['values']['symbols']);

  variable_set('views_natural_sort_beginning_words_remove', $beginning_words);
  variable_set('views_natural_sort_words_remove', $words);
  variable_set('views_natural_sort_symbols_remove', $symbols);
  variable_set('views_natural_sort_days_of_the_week_enabled', $form_state['values']['days_of_the_week_enabled']);
  variable_set('views_natural_sort_rebuild_items_per_batch', $form_state['values']['batch_items']);
  views_natural_sort_rebuild_index_submit();
}

/**
 * Submit handler that triggers the rebuild_index batch.
 */
function views_natural_sort_rebuild_index_submit() {
  views_natural_sort_rebuild_index_batch_set();
}

function views_natural_sort_rebuild_index_batch_set(array $entry_types = array()) {
  if (empty($entry_types)) {
    $entry_types = module_invoke_all('views_natural_sort_get_entry_types');
  }
  $operations = array();

  foreach($entry_types as $entry_type) {
    $operations[] = array('views_natural_sort_rebuild_index', array($entry_type));
  }
  $batch = array(
    'operations' => $operations,
    'title' => t('Rebuilding Views Natural Sort Indexing Entries'),
    'finished' => 'views_natural_sort_rebuild_index_finished',
    'file' => drupal_get_path('module', 'views_natural_sort') . '/views_natural_sort.admin.inc',
  );

  batch_set($batch);
}

/**
 * Batch API callback for rebuild_index.
 */
function views_natural_sort_rebuild_index($entry_type, &$context) {
  // Alias sandbox for easier referencing.
  $sandbox = &$context['sandbox'];

  // Initialize our context.
  if (!isset($sandbox['max'])) {
    // Hook for modules to implement and return data that views_natural_sort can
    // store as an index for that module's entries.
    $sandbox['entries'] = module_invoke_all('views_natural_sort_get_rebuild_data', $entry_type);
    $sandbox['progress'] = 0;
    $sandbox['max'] = count($sandbox['entries']) - 1;
    $sandbox['total'] = count($sandbox['entries']);

    $sandbox['current'] = 0;

    if ($sandbox['total'] == 0) {
      $context['finished'] = 1;
      return;
    }
  }

  $results = array_slice($sandbox['entries'], $sandbox['current'], variable_get('views_natural_sort_rebuild_items_per_batch', '500'));

  $entity_type = '';
  $field = '';
  foreach ($results as $row) {
    views_natural_sort_store($row);
    ++$sandbox['progress'];
    $sandbox['current'] = $sandbox['progress'];
    $entity_type = $row['entity_type'];
    $field = $row['field'];
    ++$context['results']['entries'];
  }

  $context['message'] = t('Processing %entity_type %field', array('%entity_type' => $entity_type, '%field' => $field));

  $context['finished'] = $sandbox['progress'] / $sandbox['total'];
}

/**
 * Finished callback for rebuild_index batch.
 */
function views_natural_sort_rebuild_index_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Index update has completed.'));
    drupal_set_message(t('Indexed %count.', array(
      '%count' => format_plural($results['entries'], '1 entry', '@count entries'),
    )));
  }
}
