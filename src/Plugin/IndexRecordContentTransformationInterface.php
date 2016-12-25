<?php

namespace Drupal\views_natural_sort\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Views Natural Sort Index Record Content Transformation plugins.
 */
interface IndexRecordContentTransformationInterface extends PluginInspectionInterface {


  // Add get/set methods for your plugin type here.
  public function transform($string);

}
