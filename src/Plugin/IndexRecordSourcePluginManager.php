<?php

namespace Drupal\views_natural_sort\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Index record source plugin plugin manager.
 */
class IndexRecordSourcePluginManager extends DefaultPluginManager {


  /**
   * Constructs a new IndexRecordSourcePluginManager object.
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
    parent::__construct('Plugin/IndexRecordSourcePlugin', $namespaces, $module_handler, 'Drupal\views_natural_sort\Plugin\IndexRecordSourcePluginInterface', 'Drupal\views_natural_sort\Annotation\IndexRecordSourcePlugin');

    $this->alterInfo('views_natural_sort_index_record_source_plugin_info');
    $this->setCacheBackend($cache_backend, 'views_natural_sort_index_record_source_plugin_plugins');
  }

}
