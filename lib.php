<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/local/domain_validator.php');

/**
 * Validate data supplied by a supported user form integration.
 *
 * Moodle 4.2-4.5 does not call this function automatically. An integration
 * that owns the user form must add its fields and call this function from the
 * form validation method before the user is created or updated.
 *
 * @param array|stdClass $data Form data.
 * @param array $files Uploaded files.
 * @param moodleform|null $form Form instance.
 * @return array Errors indexed by field name.
 */
function local_domainauthentication_validation($data, $files, $form) {
    return local_domainauthentication\local\domain_validator::validate((array)$data);
}

/**
 * Return the institutional domains for integrations and tests.
 *
 * @return array
 */
function local_domainauthentication_get_authorized_domains() {
    return local_domainauthentication\local\domain_validator::get_authorized_domains();
}
