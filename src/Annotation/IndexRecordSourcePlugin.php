<?php

namespace Drupal\views_natural_sort\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Index record source plugin item annotation object.
 *
 * @see \Drupal\views_natural_sort\Plugin\IndexRecordSourcePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class IndexRecordSourcePlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
