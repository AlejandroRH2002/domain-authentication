<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Modifica la definición del formulario de creación/edición de usuarios.
 * Agrega una opción vacía al select 'justification' y atributos para JS.
 *
 * @param MoodleQuickForm $mform El formulario.
 */
function local_domainauthentication_user_editadvanced_form_definition($mform) {
    global $PAGE;

    // Obtener el elemento del campo personalizado 'justification' (select).
    $justificationEl = $mform->getElement('profile_field_justification');
    if ($justificationEl && $justificationEl->getType() == 'select') {
        // Añadir opción vacía al inicio.
        $options = $justificationEl->getOptions();
        $newOptions = array('' => get_string('choosedots')) + $options;
        $justificationEl->setOptions($newOptions);
        // Establecer valor por defecto a la opción vacía.
        $mform->setDefault('profile_field_justification', '');
        // Marcar como dependiente del dominio.
        $justificationEl->setAttributes(['data-domain-dependent' => '1']);
        // Añadir ayuda.
        $mform->addHelpButton('profile_field_justification', 'justificationhelp', 'local_domainauthentication');
    }

    // Obtener el elemento del campo personalizado 'expiration_date'.
    $expirationEl = $mform->getElement('profile_field_expiration_date');
    if ($expirationEl) {
        $expirationEl->setAttributes(['data-domain-dependent' => '1']);
        $mform->addHelpButton('profile_field_expiration_date', 'expirationhelp', 'local_domainauthentication');
    }

    // Cargar JavaScript para comportamiento condicional.
    $PAGE->requires->js_call_amd('local_domainauthentication/form', 'init');
}

/**
 * Valida los datos de usuario según el dominio del correo electrónico.
 *
 * @param array|stdClass $data Datos enviados desde el formulario.
 * @param array $files Archivos subidos.
 * @param MoodleQuickForm $form Formulario que se está validando.
 * @return array Errores indexados por el nombre del campo.
 */
function local_domainauthentication_validation($data, $files, $form) {
    global $DB;

    $errors = [];
    $email = is_array($data) ? ($data['email'] ?? '') : ($data->email ?? '');
    $email = trim((string)$email);
    $email = core_text::strtolower($email);

    $parts = explode('@', $email);
    $domain = count($parts) === 2 ? trim($parts[1]) : '';

    // Dominios institucionales autorizados (sin restricciones).
    $authorizeddomains = [
        'uady.mx',
        'fmat.uady.mx',
        'alumnos.uady.mx',
        'correo.uady.mx',
    ];

    $isinstitutionaldomain = in_array($domain, $authorizeddomains, true);
    if (!$isinstitutionaldomain) {
        foreach ($authorizeddomains as $authorizeddomain) {
            $suffix = '.' . $authorizeddomain;
            if (strlen($domain) > strlen($suffix) && substr($domain, -strlen($suffix)) === $suffix) {
                $isinstitutionaldomain = true;
                break;
            }
        }
    }

    if ($isinstitutionaldomain) {
        return $errors;
    }

    $justification = is_array($data)
        ? ($data['profile_field_justification'] ?? '')
        : ($data->profile_field_justification ?? '');
    $expirationdate = is_array($data)
        ? ($data['profile_field_expiration_date'] ?? '')
        : ($data->profile_field_expiration_date ?? '');

    $justification = trim((string)$justification);
    $expirationdate = trim((string)$expirationdate);

    $justificationexists = $DB->record_exists('user_info_field', ['shortname' => 'justification']);
    $expirationexists = $DB->record_exists('user_info_field', ['shortname' => 'expiration_date']);

    if (!$justificationexists || $justification === '' || strcasecmp($justification, 'Case 1') === 0 || $justification === '1') {
        $errors['profile_field_justification'] = get_string('errorjustificationrequired', 'local_domainauthentication');
    }

    $expirationtimestamp = filter_var($expirationdate, FILTER_VALIDATE_INT);
    if (!$expirationexists || $expirationtimestamp === false || $expirationtimestamp <= time()) {
        $errors['profile_field_expiration_date'] = get_string('errorexpirationdaterequired', 'local_domainauthentication');
    }

    return $errors;
}