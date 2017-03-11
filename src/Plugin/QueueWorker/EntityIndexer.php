<?php

namespace Drupal\views_natural_sort\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views_natural_sort\ViewsNaturalSortService;

/**
 * Provides base functionality for the VNS Entith Index Queue Workers.
 *
 * @QueueWorker(
 *   id = "views_natural_sort_entity_index",
 *   title = @Translation("Views Natural Sort Entity Index"),
 * )
 */
class EntityIndexer extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  public function __construct(EntityTypeManager $entityTypeManager, ViewsNaturalSortService $viewsNaturalSortService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->viewsNaturalSortService = $viewsNaturalSortService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('views_natural_sort.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $entity = $this->entityTypeManager
      ->getStorage($data['entity_type'])
      ->load($data['entity_id']);
    if ($entity) {
      $this->viewsNaturalSortService->storeIndexRecordsFromEntity($entity);
    }
  }
}
