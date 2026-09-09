<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Validate user creation/edit form data.
 *
 * External email domains require justification and a future expiration date.
 *
 * @param array $data Submitted form data.
 * @param array $files Submitted files.
 * @param moodleform $form Form instance.
 * @return array Validation errors indexed by field name.
 */
function local_domainauthentication_validation($data, $files, $form) {
    global $DB;

    $errors = array();

    // Only apply the validation to Moodle user creation/edit forms.
    if (!($form instanceof user_edit_form) && !($form instanceof user_profile_form)) {
        return $errors;
    }

    // Email is required for this validation.
    $email = isset($data['email']) ? trim($data['email']) : '';
    if ($email === '' || !validate_email($email)) {
        return $errors;
    }

    // Extract and normalize the email domain.
    $emailparts = explode('@', core_text::strtolower($email));
    if (count($emailparts) !== 2) {
        return $errors;
    }

    $domain = trim($emailparts[1]);

    // Authorized institutional domains.
    $authorizeddomains = array(
        'uady.mx',
        'fmat.uady.mx',
        'alumnos.uady.mx',
        'correo.uady.mx',
    );

    // Institutional domain: no additional validation is required.
    if (in_array($domain, $authorizeddomains, true)) {
        return $errors;
    }

    /*
     * External domain:
     * Moodle custom profile fields are expected to have the shortnames
     * "justification" and "expiration_date".
     *
     * The corresponding submitted form keys are normally:
     * profile_field_justification
     * profile_field_expiration_date
     */
    $justificationkey = 'profile_field_justification';
    $expirationkey = 'profile_field_expiration_date';

    $justification = isset($data[$justificationkey])
        ? trim((string)$data[$justificationkey])
        : '';

    $expirationraw = isset($data[$expirationkey])
        ? trim((string)$data[$expirationkey])
        : '';

    // Missing or empty justification blocks the save.
    if ($justification === '') {
        $errors[$justificationkey] = get_string('externaldomainjustificationrequired', 'local_domainauthentication');
    }

    /*
     * Validate the expiration date.
     *
     * Moodle date fields can arrive as timestamps or date strings depending
     * on how the custom profile field was configured/versioned.
     */
    $expirationtimestamp = 0;

    if ($expirationraw !== '') {
        if (is_numeric($expirationraw)) {
            $expirationtimestamp = (int)$expirationraw;
        } else {
            $parsed = strtotime($expirationraw);
            if ($parsed !== false) {
                $expirationtimestamp = $parsed;
            }
        }
    }

    // Missing, invalid, or non-future expiration date blocks the save.
    if ($expirationtimestamp <= time()) {
        $errors[$expirationkey] = get_string('externaldomainexpirationrequired', 'local_domainauthentication');
    }

    return $errors;
}
