<?php
defined('MOODLE_INTERNAL') || die();

class local_domainauthentication_observer {

    public static function user_created(\core\event\user_created $event) {
        global $DB;

        $userid = $event->objectid;
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || $user->id <= 2 || $user->deleted) {
            return;
        }

        // Extraer dominio del email.
        $parts = explode('@', core_text::strtolower($user->email));
        if (count($parts) !== 2) {
            return;
        }
        $domain = $parts[1];

        // Dominios institucionales autorizados.
        $authorizeddomains = [
            'uady.mx',
            'fmat.uady.mx',
            'alumnos.uady.mx',
            'correo.uady.mx',
        ];

        // Si es dominio interno, no hacer nada.
        if (in_array($domain, $authorizeddomains, true)) {
            return;
        }

        // --- Dominio externo: validar campos personalizados ---
        // Obtener el campo de justificación.
        $justfieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'justification']);
        $justval = '';
        if ($justfieldid) {
            $justval = $DB->get_field('user_info_data', 'data', ['userid' => $user->id, 'fieldid' => $justfieldid]);
        }

        // Obtener el campo de fecha de expiración.
        $expfieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'expiration_date']);
        $expval = 0;
        if ($expfieldid) {
            $expval = (int)$DB->get_field('user_info_data', 'data', ['userid' => $user->id, 'fieldid' => $expfieldid]);
        }

        $haserror = false;
        $errormsg = '';

        // Validar justificación (no vacía, no "Case 1", no "1").
        $justclean = trim((string)$justval);
        if ($justclean === '' || strcasecmp($justclean, 'Case 1') === 0 || $justclean === '1') {
            $haserror = true;
            $errormsg .= get_string('errorjustification', 'local_domainauthentication') . ' ';
        }

        // Validar fecha de expiración (futura).
        if ($expval <= time()) {
            $haserror = true;
            $errormsg .= get_string('errorexpiration', 'local_domainauthentication') . ' ';
        }

        // Si hay error, marcar al usuario como eliminado y lanzar excepción.
        if ($haserror) {
            $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);
            throw new \moodle_exception('errorexternalvalidation', 'local_domainauthentication', '', null, $errormsg);
        }
    }
}