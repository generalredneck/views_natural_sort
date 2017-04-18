<?php

/**
 * @file
 * The Views Natural Sort module include file.
 */

/**
 * Remove all the configured words from the beginning of the string only.
 *
 * @param string $string
 *   The string we wish to transform.
 *
 * @return string
 *   The transformed string.
 */
function views_natural_sort_remove_beginning_words($string) {
  $beginning_words = [
    t('The'),
    t('A'),
    t('An'),
    t('La'),
    t('Le'),
    t('Il'),
  ];
  if (empty($beginning_words)) {
    return $string;
  }

  array_walk($beginning_words, 'preg_quote');
  return preg_replace(
    '/^(' . implode('|', $beginning_words) . ')\s+/i',
    '',
    $string
  );
}

/**
 * Remove all the configured words from the string.
 *
 * @param string $string
 *   The string we wish to transform.
 *
 * @return string
 *   The transformed string.
 */
function views_natural_sort_remove_words($string) {
  $words = [
    t('and'),
    t('or'),
    t('of'),
  ];
  if (empty($words)) {
    return $string;
  }

  array_walk($words, 'preg_quote');
  return preg_replace(
    [
      '/\s(' . implode('|', $words) . ')\s+/i',
      '/^(' . implode('|', $words) . ')\s+/i',
    ],
    [
      ' ',
      '',
    ],
    $string
  );
}

/**
 * Remove all the configured symbols from the string.
 *
 * @param string $string
 *   The string we wish to transform.
 *
 * @return string
 *   The transformed string.
 */
function views_natural_sort_remove_symbols($string) {
  $symbols = "#\"'\\()[]";
  if (strlen($symbols) == 0) {
    return $string;
  }
  return preg_replace(
    '/[' . preg_quote($symbols) . ']/',
    '',
    $string
  );
}

/**
 * Transform numbers in a string into a natural sortable string.
 *
 * Rules are as follows:
 *  - Embedded numbers will sort in numerical order. The following possibilities
 *    are supported
 *    - A leading dash indicates a negative number, unless it is preceded by a
 *      non-whitespace character, which case it is considered just a dash.
 *    - Leading zeros are properly ignored so as to not influence sort order
 *    - Decimal numbers are supported using a period as the decimal character
 *    - Thousands separates are ignored, using the comma as the thous. character
 *    - Numbers may be up to 99 digits before the decimal, up to the precision
 *      of the processor.
 *
 * @param string $string
 *   The string we wish to transform.
 */
function views_natural_sort_numbers($string) {
  // Find an optional leading dash (either preceded by whitespace or the first
  // character) followed by either:
  // - an optional series of digits (with optional embedded commas), then a
  //   period, then an optional series of digits
  // - a series of digits (with optional embedded commas)
  return preg_replace_callback(
    '/(\s-|^-)?(?:(\d[\d,]*)?\.(\d+)|(\d[\d,]*))/',
    '_views_natural_sort_number_transform_match_callback',
    $string
  );
}

/**
 * Transforms a string representing numbers into a special format.
 *
 * This special format can be sorted as if it was a number but in reality is
 *   being sorted alphanumerically.
 *
 * @param array $match
 *   Array of matches passed from preg_replace_callback
 *   $match[0] is the entire matching string
 *   $match[1] if present, is the optional dash, preceded by optional whitespace
 *   $match[2] if present, is whole number portion of the decimal number
 *   $match[3] if present, is the fractional portion of the decimal number
 *   $match[4] if present, is the integer (when no fraction is matched).
 *
 * @return string
 *   String representing a numerical value that will sort numerically in an
 *   alphanumeric search.
 */
function _views_natural_sort_number_transform_match_callback(array $match) {

  // Remove commas and leading zeros from whole number.
  $whole = (string) (int) str_replace(',', '', (isset($match[4]) && strlen($match[4]) > 0) ? $match[4] : $match[2]);
  // Remove traililng 0's from fraction, then add the decimal and one trailing
  // 0 and a space. The space serves as a way to always sort shorter decimal
  // numbers that match exactly as less than longer ones.
  // Ex: 3.05 and 3.05011.
  $fraction = trim('.' . $match[3], '0') . '0 ';
  $encode = sprintf('%02u', strlen($whole)) . $whole . $fraction;
  if (strlen($match[1])) {
    // Negative number. Make 10's complement. Put back any leading white space
    // and the dash requires intermediate to avoid double-replacing the same
    // digit. str_replace() seems to work by copying the source to the result,
    // then successively replacing within it, rather than replacing from the
    // source to the result.
    // In this case since rules are reverced we also have to use a character
    // that would be sorted higher than a space when a number is being compared
    // against a longer one that is identical in negative numbers. This is so
    // that longer numbers are always LESS than sorter numbers that have
    // identical beginnings. Ex: -3.05 and -3.05011.
    $digits       = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ' '];
    $intermediate = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'];
    $rev_digits   = ['9', '8', '7', '6', '5', '4', '3', '2', '1', '0', ':'];
    $encode       = $match[1] . str_replace($intermediate, $rev_digits, str_replace($digits, $intermediate, $encode));
  }
  return $encode;
}
