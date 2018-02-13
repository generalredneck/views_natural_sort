<?php

namespace Drupal\views_natural_sort\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Index record source plugin plugins.
 */
interface IndexRecordSourcePluginInterface extends PluginInspectionInterface {

  /**
   * Gets all the types that use this source plugin.
   *
   * @return array
   *   Array of IndexRecordType objects.
   */
  public function getEntryTypes();

}
