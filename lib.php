<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Validate user creation/edit form data.
 *
 * External email domains require justification and a valid future expiration date.
 *
 * @param array $data Submitted form data.
 * @param array $files Submitted files.
 * @param moodleform $form Form instance.
 * @return array Validation errors indexed by field name.
 */
function local_domainauthentication_validation($data, $files, $form) {
    global $DB;

    $errors = array();

    // 1. Asegurar que estamos en un formulario de edición o creación de usuarios de Moodle.
    $formclass = get_class($form);
    if (strpos($formclass, 'user_edit') === false && strpos($formclass, 'user_profile') === false && !($form instanceof moodleform)) {
        // Si no es un formulario de usuario conocido, permitimos continuar para evitar bloquear otros formularios.
        // Pero para asegurarnos en "Add a new user", permitimos la ejecución si existe el campo 'email'.
        if (!isset($data['email'])) {
            return $errors;
        }
    }

    // 2. Obtener y validar el correo electrónico.
    $email = isset($data['email']) ? trim($data['email']) : '';
    if ($email === '' || !validate_email($email)) {
        return $errors;
    }

    // 3. Extraer y normalizar el dominio del correo.
    $emailparts = explode('@', core_text::strtolower($email));
    if (count($emailparts) !== 2) {
        return $errors;
    }
    $domain = trim($emailparts[1]);

    // 4. Dominios institucionales autorizados.
    $authorizeddomains = array(
        'uady.mx',
        'fmat.uady.mx',
        'alumnos.uady.mx',
        'correo.uady.mx',
    );

    // Si es un dominio institucional autorizado, no requiere validación adicional.
    if (in_array($domain, $authorizeddomains, true)) {
        return $errors;
    }

    // --- DOMINIO EXTERNO DETECTADO ---
    // A partir de aquí, exigimos los campos obligatorios de justificación y expiración.

    // Buscamos dinámicamente las llaves de los campos personalizados (pueden venir con prefijos en Moodle)
    $justificationkey = '';
    $expirationkey = '';
    $justificationval = '';
    $expirationval = '';
    $expiration_enabled = true; // Por defecto asumimos habilitado si no usa selector complejo

    foreach ($data as $key => $value) {
        if (strpos($key, 'justification') !== false) {
            $justificationkey = $key;
            $justificationval = trim((string)$value);
        }
        if (strpos($key, 'expiration_date') !== false) {
            // Evitamos capturar la bandera '_enabled' directamente como valor de fecha
            if (strpos($key, '_enabled') === false) {
                $expirationkey = $key;
                $expirationval = $value;
            } else {
                // Si existe la bandera de habilitación del campo de fecha y está en 0/falso
                if (empty($value)) {
                    $expiration_enabled = false;
                }
            }
        }
    }

    // Si no se encontraron por coincidencia parcial, usamos los nombres estándar predeterminados
    if ($justificationkey === '') {
        $justificationkey = 'profile_field_justification';
    }
    if ($expirationkey === '') {
        $expirationkey = 'profile_field_expiration_date';
    }

    // Validar Justificación: No debe estar vacía ni tener el valor por defecto inicial ("Case 1")
    $justification_current = isset($data[$justificationkey]) ? trim((string)$data[$justificationkey]) : $justificationval;
    if ($justification_current === '' || strcasecmp($justification_current, 'Case 1') === 0 || strcasecmp($justification_current, '1') === 0) {
        $errors[$justificationkey] = get_string('externaldomainjustificationrequired', 'local_domainauthentication');
    }

    // Validar Fecha de Expiración: Debe estar habilitada y ser un timestamp futuro válido
    $expiration_current = isset($data[$expirationkey]) ? $data[$expirationkey] : $expirationval;
    $timestamp = 0;

    if ($expiration_current !== '' && $expiration_current !== null) {
        if (is_numeric($expiration_current)) {
            $timestamp = (int)$expiration_current;
        } else {
            $parsed = strtotime($expiration_current);
            if ($parsed !== false) {
                $timestamp = $parsed;
            }
        }
    }

    // Verificamos si la fecha es menor o igual al tiempo actual o si el selector de fecha no está activado
    if (!$expiration_enabled || $timestamp <= time()) {
        $errors[$expirationkey] = get_string('externaldomainexpirationrequired', 'local_domainauthentication');
    }

    return $errors;
}