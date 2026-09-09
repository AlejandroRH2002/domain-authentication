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

    $justificationkey = 'profile_field_justification';
    $expirationkey = 'profile_field_expiration_date';

    $justification = isset($data[$justificationkey])
        ? trim((string)$data[$justificationkey])
        : '';

    // Missing or empty justification blocks the save.
    if ($justification === '') {
        $errors[$justificationkey] = get_string('externaldomainjustificationrequired', 'local_domainauthentication');
    }

    /*
     * En Moodle, los elementos de fecha personalizados con selector "Enable" 
     * envían un timestamp numérico si están habilitados. Si no se marca "Enable",
     * el campo suele omitirse o llegar vacío.
     */
    $expirationtimestamp = 0;
    $is_date_submitted = false;

    if (isset($data[$expirationkey])) {
        $rawval = $data[$expirationkey];
        if ($rawval !== '' && $rawval !== null) {
            $is_date_submitted = true;
            if (is_numeric($rawval)) {
                $expirationtimestamp = (int)$rawval;
            } else {
                $parsed = strtotime($rawval);
                if ($parsed !== false) {
                    $expirationtimestamp = $parsed;
                }
            }
        }
    }

    // Si es un dominio externo, la fecha DEBE estar habilitada y ser un timestamp futuro válido.
    if (!$is_date_submitted || $expirationtimestamp <= time()) {
        $errors[$expirationkey] = get_string('externaldomainexpirationrequired', 'local_domainauthentication');
    }

    return $errors;
}