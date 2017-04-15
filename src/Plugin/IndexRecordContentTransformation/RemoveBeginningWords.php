<?php

namespace Drupal\views_natural_sort\Plugin\IndexRecordContentTransformation;

use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationBase as TransformationBase;

/**
 * @IndexRecordContentTransformation (
 *   id = "remove_beginning_words",
 *   label = @Translation("Remove Beginning Words")
 * )
 */
class RemoveBeginningWords extends TransformationBase {

  public function transform($string) {
    $beginning_words = $this->configuration['settings'];
    if (empty($beginning_words)) {
      return $string;
    }

    array_walk($beginning_words, 'preg_quote');
    return preg_replace(
      '/^(' . implode('|', $beginning_words) . ')\s+/iu',
      '',
      $string
    );
  }

}
