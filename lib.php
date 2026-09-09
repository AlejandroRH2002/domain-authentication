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
 * Validación personalizada para el formulario de creación/edición de usuarios.
 * También modifica los datos para dominios internos (limpia los campos).
 *
 * @param stdClass $data Datos enviados desde el formulario (pasado por referencia).
 * @param array $files Archivos subidos.
 * @param array $errors Array de errores (por referencia) donde añadir los mensajes.
 */
function local_domainauthentication_user_editadvanced_form_validation($data, $files, &$errors) {
    global $DB;

    // Extraer dominio del email.
    $email = trim($data->email);
    if (empty($email)) return;

    $parts = explode('@', core_text::strtolower($email));
    if (count($parts) !== 2) return;
    $domain = $parts[1];

    // Dominios institucionales autorizados (sin restricciones).
    $authorizeddomains = [
        'uady.mx',
        'fmat.uady.mx',
        'alumnos.uady.mx',
        'correo.uady.mx',
    ];

    // Si es dominio interno: forzar campos a vacío y salir sin validar.
    if (in_array($domain, $authorizeddomains, true)) {
        // Limpiar valores para que no se guarden datos residuales.
        $data->profile_field_justification = '';
        $data->profile_field_expiration_date = '';
        return; // No se añaden errores.
    }

    // --- Dominio externo: validar campos personalizados ---
    $justification = isset($data->profile_field_justification) ? trim($data->profile_field_justification) : '';
    $expiration    = isset($data->profile_field_expiration_date) ? $data->profile_field_expiration_date : '';

    // Validar justificación (no vacía y no "Case 1" ni "1").
    if ($justification === '' || strcasecmp($justification, 'Case 1') === 0 || $justification === '1') {
        $errors['profile_field_justification'] = get_string('errorjustification', 'local_domainauthentication');
    }

    // Validar fecha de expiración (debe ser futura).
    if (!empty($expiration)) {
        $timestamp = strtotime($expiration);
        if ($timestamp === false || $timestamp <= time()) {
            $errors['profile_field_expiration_date'] = get_string('errorexpiration', 'local_domainauthentication');
        }
    } else {
        $errors['profile_field_expiration_date'] = get_string('errorexpirationempty', 'local_domainauthentication');
    }
}