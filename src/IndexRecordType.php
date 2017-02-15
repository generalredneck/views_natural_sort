<?php

namespace Drupal\views_natural_sort;

class IndexRecordType {

  protected $entityType;
  protected $field;

  public function __construct($entity_type_id, $field_machine_name) {
    $this->setEntityType($entity_type_id);
    $this->setField($field_machine_name);
  }

  public function getEntityType() {
    return $this->entityType;
  }

  public function setEntityType($entity_type_id) {
    $this->entityType = $entity_type_id;
  }

  public function getField() {
    return $this->field;
  }

  public function setField($field_machine_name) {
    $this->field = $field_machine_name;
  }
}
