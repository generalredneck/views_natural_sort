<?php

namespace Drupal\views_natural_sort;

use Drupal\Core\Database\Connection;

class IndexRecord {
  protected $eid;
  protected $entityType;
  protected $field;
  protected $delta;
  protected $content;
  protected $transformations = [];
  private $database;

  public function __construct(Connection $database, array $values = []) {
    $this->database = $database;
    $this->setEntityId($values['eid']);
    $this->setEntityType($values['entity_type']);
    $this->setField($values['field']);
    $this->setDelta($values['delta']);
    $this->setContent($values['content']);
  }
  public function setEntityId($eid) {
    $this->eid = $eid;
  }
  public function getEntityId() {
    return $this->eid;
  }
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;
    $this->generateType();
  }
  public function getEntityType() {
    return $this->entityType;
  }
  public function setField($field) {
    $this->field = $field;
    $this->generateType();
  }
  public function getField() {
    return $this->field;
  }
  public function setDelta($delta) {
    $this->delta = $delta;
  }
  public function getDelta() {
    return $this->delta;
  }
  public function setContent($string) {
    $this->content = $string;
  }
  public function getContent() {
    return $this->content;
  }
  public function setTransformations(array $transformations) {
    $this->transformations = $transformations;
  }
  public function getTransformations() {
    return $this->transformations;
  }
  public function getTransformedContent() {
    $transformed_content = $this->content;
    foreach ($this->transformations as $transformation) {
      $transformed_content = $transformation->transform($transformed_content);
    }
    return mb_substr($transformed_content, 0, 255);
  }
  private function generateType() {
    $this->type = new IndexRecordType($this->entityType, $this->field);
  }
  public function getType() {
    return $this->type;
  }

  public function save() {
    $this->database->merge('views_natural_sort')
      ->key([
        'eid' => $this->eid,
        'entity_type' => $this->entityType,
        'field' => $this->field,
        'delta' => $this->delta,
      ])
      ->fields([
        'eid' => $this->eid,
        'entity_type' => $this->entityType,
        'field' => $this->field,
        'delta' => $this->delta,
        'content' => $this->getTransformedContent(),
      ])
      ->execute();
  }

  public function delete() {
    $this->database->delete('views_natural_sort')
      ->condition('eid', $this->eid)
      ->condition('entity_type', $this->entityType)
      ->condition('field', $this->field)
      ->condition('delta', $this->delta)
      ->execute();
  }

}
