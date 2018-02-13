<?php

namespace Drupal\views_natural_sort;

use Drupal\views_natural_sort\Plugin\IndexRecordSourcePluginInterface as EntrySourcePluginInterface;

class IndexRecordType {

  protected $entityType;
  protected $field;
  protected $entrySourcePlugin;

  public function __construct($entity_type_id, $field_machine_name, $entry_source_plugin) {
    $this->setEntityType($entity_type_id)
      ->setField($field_machine_name)
      ->setEntrySourcePlugin($entry_source_plugin);
  }

  public function getEntityType() {
    return $this->entityType;
  }

  public function setEntityType($entity_type_id) {
    $this->entityType = $entity_type_id;
    return $this;
  }

  public function getField() {
    return $this->field;
  }

  public function setField($field_machine_name) {
    $this->field = $field_machine_name;
    return $this;
  }

  public function getEntrySourcePlugin() {
    return $this->entrySourcePlugin;
  }

  public function setEntrySourcePlugin(EntrySourcePluginInterface $entry_source_plugin) {
    $this->entrySourcePlugin = $entry_source_plugin;
    return $this;
  }


}
