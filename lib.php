<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Validate user creation/edit form data.
 *
 * External email domains require a valid justification (not the default option)
 * and an explicitly enabled future expiration date.
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

    /*
     * Validación de la justificación para dominios externos:
     * Verificamos que no esté vacía y que no tenga el valor predeterminado inicial ("Case 1")
     * si ese es el valor por defecto que se desea prohibir para cuentas externas.
     */
    if ($justification === '' || strcasecmp($justification, 'Case 1') === 0) {
        $errors[$justificationkey] = get_string('externaldomainjustificationrequired', 'local_domainauthentication');
    }

    /*
     * Validación estricta para la fecha de expiración en dominios externos:
     * Comprobamos tanto el valor del timestamp como las banderas de habilitación de Moodle.
     */
    $is_enabled = false;
    $expirationtimestamp = 0;

    if (isset($data[$expirationkey]) && $data[$expirationkey] !== '' && $data[$expirationkey] !== null) {
        $rawval = $data[$expirationkey];
        if (is_numeric($rawval)) {
            $expirationtimestamp = (int)$rawval;
        } else {
            $parsed = strtotime($rawval);
            if ($parsed !== false) {
                $expirationtimestamp = $parsed;
            }
        }
        if ($expirationtimestamp > 0) {
            $is_enabled = true;
        }
    }

    // Revisión adicional de banderas de control de fecha de Moodle (_enabled)
    $enabled_flag_keys = array(
        $expirationkey . '_enabled',
        'subplugin_' . $expirationkey,
    );
    foreach ($enabled_flag_keys as $flag_key) {
        if (isset($data[$flag_key]) && empty($data[$flag_key])) {
            $is_enabled = false;
        }
    }

    // Si el dominio es externo, se exige que la fecha esté activada (Enable marcado) y sea un valor futuro.
    if (!$is_enabled || $expirationtimestamp <= time()) {
        $errors[$expirationkey] = get_string('externaldomainexpirationrequired', 'local_domainauthentication');
    }

    return $errors;
}