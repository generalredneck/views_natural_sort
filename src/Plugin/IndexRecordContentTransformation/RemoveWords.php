<?php

namespace Drupal\views_natural_sort\Plugin\IndexRecordContentTransformation;

use Drupal\views_natural_sort\Plugin\IndexRecordContentTransformationBase as TransformationBase;

/**
 * @IndexRecordContentTransformation (
 *   id = "remove_words",
 *   label = @Translation("Remove Words")
 * )
 */
class RemoveWords extends TransformationBase {

  public function transform($string) {
    $words = $this->configuration['settings'];
    if (empty($words)) {
      return $string;
    }

    array_walk($words, 'preg_quote');
    return preg_replace(
      [
        '/\s(' . implode('|', $words) . ')\s+/iu',
        '/^(' . implode('|', $words) . ')\s+/iu',
      ],
      [
        ' ',
        '',
      ],
      $string
    );
  }

}
