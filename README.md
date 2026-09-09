```php
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Validate user email domain and external-domain authorization fields.
 *
 * This function validates data submitted by Moodle user profile forms.
 *
 * Institutional domains are allowed without additional authorization.
 * External domains require:
 * - profile_field_justification
 * - profile_field_expiration_date
 *
 * @param array $data Submitted form data.
 * @param array $files Submitted files.
 * @param moodleform|null $form Moodle form instance.
 * @return array Array of validation errors.
 */
function local_domainauthentication_validation($data, $files, $form = null) {
    $errors = [];

    // Only process user profile forms.
    if ($form !== null) {
        $formname = get_class($form);

        $validforms = [
            'user_edit_form',
            'user_editadvanced_form',
            'user_profile_form',
        ];

        if (!in_array($formname, $validforms, true)) {
            return $errors;
        }
    }

    // Make sure data can be accessed as an array.
    if (is_object($data)) {
        $data = (array) $data;
    }

    // Email is required for this validation.
    if (empty($data['email'])) {
        return $errors;
    }

    $email = trim($data['email']);

    // Extract domain from email address.
    $atposition = strrpos($email, '@');

    if ($atposition === false) {
        return $errors;
    }

    $domain = strtolower(trim(substr($email, $atposition + 1)));

    if ($domain === '') {
        return $errors;
    }

    /**
     * Institutional domains authorized by the site.
     *
     * Add or remove domains according to the institution's policy.
     */
    $authorizeddomains = [
        'uady.mx',
        'fmat.uady.mx',
        'alumnos.uady.mx',
        'correo.uady.mx',
    ];

    // Institutional domain: no additional authorization required.
    if (in_array($domain, $authorizeddomains, true)) {
        return $errors;
    }

    /*
     * External domain detected.
     *
     * The following custom Moodle profile fields are mandatory:
     *
     * profile_field_justification
     * profile_field_expiration_date
     */

    $justificationfield = 'profile_field_justification';
    $expirationfield = 'profile_field_expiration_date';

    /*
     * Check that both custom fields actually exist in Moodle.
     *
     * This is important because the requirement states that a missing
     * profile field must also block the user from being saved.
     */
    require_once($GLOBALS['CFG']->dirroot . '/user/profile/lib.php');

    $justificationinfo = profile_get_custom_field_data_by_shortname(
        'justification',
        false
    );

    $expirationinfo = profile_get_custom_field_data_by_shortname(
        'expiration_date',
        false
    );

    if (!$justificationinfo) {
        $errors[$justificationfield] = get_string(
            'justificationfieldmissing',
            'local_domainauthentication'
        );
    }

    if (!$expirationinfo) {
        $errors[$expirationfield] = get_string(
            'expirationfieldmissing',
            'local_domainauthentication'
        );
    }

    /*
     * If either field does not exist, stop here.
     */
    if (!$justificationinfo || !$expirationinfo) {
        return $errors;
    }

    /*
     * Retrieve submitted justification.
     *
     * Moodle custom profile fields are normally represented using:
     * profile_field_<shortname>
     */
    $justification = '';

    if (array_key_exists($justificationfield, $data)) {
        $justification = $data[$justificationfield];
    }

    // Some form data may contain an object instead of a plain value.
    if (is_array($justification)) {
        if (isset($justification['text'])) {
            $justification = $justification['text'];
        } else {
            $justification = reset($justification);
        }
    }

    $justification = trim((string) $justification);

    /*
     * Justification is mandatory for external domains.
     */
    if ($justification === '') {
        $errors[$justificationfield] = get_string(
            'justificationrequired',
            'local_domainauthentication'
        );
    }

    /*
     * Retrieve expiration date.
     */
    $expirationdate = '';

    if (array_key_exists($expirationfield, $data)) {
        $expirationdate = $data[$expirationfield];
    }

    /*
     * Moodle date fields can potentially arrive in different formats.
     * Convert the value into a timestamp.
     */
    $expirationtimestamp = local_domainauthentication_parse_date(
        $expirationdate
    );

    /*
     * The expiration date must:
     * 1. Exist.
     * 2. Be valid.
     * 3. Be strictly greater than the current timestamp.
     */
    if ($expirationtimestamp === false) {
        $errors[$expirationfield] = get_string(
            'expirationinvalid',
            'local_domainauthentication'
        );
    } else if ($expirationtimestamp <= time()) {
        $errors[$expirationfield] = get_string(
            'expirationfuture',
            'local_domainauthentication'
        );
    }

    return $errors;
}

/**
 * Convert a submitted expiration-date value to a Unix timestamp.
 *
 * Supports:
 * - Unix timestamps.
 * - Date strings understood by strtotime().
 * - Moodle-style date arrays containing year/month/day.
 *
 * @param mixed $value Submitted date value.
 * @return int|false Timestamp or false when invalid.
 */
function local_domainauthentication_parse_date($value) {
    if ($value === null || $value === '') {
        return false;
    }

    /*
     * Unix timestamp.
     */
    if (is_numeric($value)) {
        $timestamp = (int) $value;

        if ($timestamp <= 0) {
            return false;
        }

        return $timestamp;
    }

    /*
     * Moodle date fields may be submitted as arrays.
     */
    if (is_array($value)) {
        if (
            isset($value['year']) &&
            isset($value['month']) &&
            isset($value['day'])
        ) {
            $year = (int) $value['year'];
            $month = (int) $value['month'];
            $day = (int) $value['day'];

            if (!checkdate($month, $day, $year)) {
                return false;
            }

            $hour = isset($value['hour']) ? (int) $value['hour'] : 23;
            $minute = isset($value['minute']) ? (int) $value['minute'] : 59;

            return mktime(
                $hour,
                $minute,
                59,
                $month,
                $day,
                $year
            );
        }

        /*
         * Some date elements can use a timestamp key.
         */
        if (isset($value['timestamp']) && is_numeric($value['timestamp'])) {
            $timestamp = (int) $value['timestamp'];

            return $timestamp > 0 ? $timestamp : false;
        }

        return false;
    }

    /*
     * String date.
     */
    $value = trim((string) $value);

    if ($value === '') {
        return false;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return false;
    }

    return $timestamp;
}
```
