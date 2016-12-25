<?php

namespace Drupal\views_natural_sort\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Views Natural Sort Index Record Content Transformation plugin manager.
 */
class IndexRecordContentTransformationManager extends DefaultPluginManager {


  /**
   * Constructor for IndexRecordContentTransformationManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/IndexRecordContentTransformation', $namespaces, $module_handler, 'Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationInterface', 'Drupal\views_natural_sort\Annotation\IndexRecordContentTransformation');

    $this->alterInfo('views_natural_sort_vns_transformation_info');
    $this->setCacheBackend($cache_backend, 'views_natural_sort_vns_transformation_plugins');
  }

}
