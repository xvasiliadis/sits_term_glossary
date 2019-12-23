<?php

/**
 * @file
 * Contains hooks.
 */

/**
 * Hook to alter the results of the search.
 *
 * See Drupal\term_glossary\Controller::apiSearchPerTerm 172.
 *
 * @param mixed $results
 *   Array of term key vals for js.
 * @param mixed $terms
 *   The array of loaded terms.
 * @param mixed $search_term
 *   The search term or letter.
 */
function hook_term_glossary_alter_results(&$results, $terms, $search_term) {
  // Here alter the results.
}

/**
 * Hook to alter the results term by id modal.
 *
 * See Drupal\term_glossary\Controller::apiGetTermById.
 *
 * See JS hook in assets/js/glossary-content-dialog.js line 50
 *
 * @param array|null $result
 *   An array the result.
 * @param mixed $term
 *   The loaded term.
 * @param string $term_id
 *   String of the search term id.
 */
function hook_term_glossary_alter_result(&$result, $term, $term_id) {
  // Here alter the results.
}
