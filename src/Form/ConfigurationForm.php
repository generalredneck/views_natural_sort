<?php

namespace Drupal\views_natural_sort\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_natural_sort\ViewsNaturalSortService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Views Natural Sort's settings.
 */
class ConfigurationForm extends ConfigFormBase {

  protected $viewsNaturalSort;

  public function __construct(ViewsNaturalSortService $viewsNaturalSort) {
    $this->viewsNaturalSort = $viewsNaturalSort;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('views_natural_sort.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'view_natural_sort_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_natural_sort.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('views_natural_sort.settings');
    // TODO: Change this to be handled by the transformation plugins.
    $form['beginning_words_remove'] = array(
      '#type' => 'textfield',
      '#title' => 'Words to filter from the beginning of a phrase',
      '#default_value' => implode(',', $config->get('transformation_settings.remove_beginning_words.settings')),
      '#description' => $this->t('Commonly, the words "A", "The", and "An" are removed when sorting book titles if they appear at the beginning of the title. Those would be great candidates for this field. Separate words with a comma.'),
    );

    $form['words_remove'] = array(
      '#type' => 'textfield',
      '#title' => 'Words to filter from anywhere in a phrase',
      '#default_value' => implode(',', $config->get('transformation_settings.remove_words.settings')),
      '#description' => $this->t('Commonly used words like "of", "and", and "or" are removed when sorting book titles. Words you would like filtered go here. Separate words with a comma.'),
    );

    $form['symbols_remove'] = array(
      '#type' => 'textfield',
      '#title' => 'Symbols to filter from anywhere in a phrase',
      '#default_value' => $config->get('transformation_settings.remove_symbols.settings'),
      '#description' => $this->t('Most symbols are ignored when performing a sort naturally. Those symbols you want ignored go here. Do not use a separator. EX: &$".'),
    );
    $form['days_of_the_week_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => 'Sort days of the week and their abbreviations',
      '#description' => "Checking this setting will allow sorting of days of the week in their proper order starting with the day of the week that is configurable by you and for each language.",
      '#efault_value' => $config->get('transformation_settings.days_of_the_week.enabled'),
    );
    $form['rebuild_items_per_batch'] = array(
      '#type' => 'number',
      '#title' => 'Items per Batch',
      '#default_value' => $config->get('rebuild_items_per_batch'),
      '#min' => 0,
      '#description' => $this->t('The number of items a batch process will work through at a given time. Raising this number will make the batch go quicker, however, raising it too high can cause timeouts and/or memory limit errors.'),
    );
    $form['rebuild'] = [
      '#type' => 'details',
      '#title' => $this->t('Incase of Emergency'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,

      'button' => [
        '#type' => 'submit',
        '#description' => 'Incase of an emergency.',
        '#value' => $this->t('Rebuild Index'),
        '#submit' => [[$this, 'submitFormReindexOnly']],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // TODO: Change this to be handled by the transformation plugins.
    $beginning_words_remove = explode(',', $values['beginning_words_remove']);
    array_walk(
      $beginning_words_remove,
      function (&$val) {
        $val = trim($val);
      }
    );
    $words_remove = explode(',', $values['words_remove']);
    array_walk(
      $words_remove,
      function (&$val) {
        $val = trim($val);
      }
    );
    $symbols_remove = trim($values['symbols_remove']);
    $this->config('views_natural_sort.settings')
      ->set('transformation_settings.remove_beginning_words.settings', $beginning_words_remove)
      ->set('transformation_settings.remove_words.settings', $words_remove)
      ->set('transformation_settings.remove_symbols.settings', $symbols_remove)
      ->set('transformation_settings.days_of_the_week.enabled', $values['days_of_the_week_enabled'])
      ->set('rebuild_items_per_batch', $values['rebuild_items_per_batch'])
      ->save();
    drupal_set_message($this->t('The configuration options have been saved.'));
    $this->submitFormReindexOnly($form, $form_state);
  }

  /**
   * Submission action for the "Rebuild Index" button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitFormReindexOnly(array &$form, FormStateInterface $form_state) {
    views_natural_sort_queue_data_for_rebuild();
  }

}
