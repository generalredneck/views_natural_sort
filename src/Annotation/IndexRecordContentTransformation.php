<?php

namespace Drupal\views_natural_sort\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Views Natural Sort Index Record Content Transformation item annotation object.
 *
 * @see \Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationManager
 * @see plugin_api
 *
 * @Annotation
 */
class IndexRecordContentTransformation extends Plugin {


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
