<?php
namespace local_domainauthentication\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Pure validation rules for external-domain user data.
 */
class domain_validator {
    /**
     * @return array
     */
    public static function get_authorized_domains() {
        return [
            'uady.mx',
            'fmat.uady.mx',
            'alumnos.uady.mx',
            'correo.uady.mx',
        ];
    }

    /**
     * @param string $email
     * @return string|null
     */
    public static function get_domain($email) {
        $email = trim((string)$email);
        if ($email === '' || substr_count($email, '@') !== 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        [, $domain] = explode('@', \core_text::strtolower($email), 2);
        $domain = trim($domain);
        return $domain === '' ? null : $domain;
    }

    /**
     * @param string $domain
     * @return bool
     */
    public static function is_institutional_domain($domain) {
        $domain = \core_text::strtolower(trim((string)$domain));
        foreach (self::get_authorized_domains() as $authorizeddomain) {
            if ($domain === $authorizeddomain ||
                    (strlen($domain) > strlen($authorizeddomain) &&
                    substr($domain, -(strlen($authorizeddomain) + 1)) === '.' . $authorizeddomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate data from a form integration.
     *
     * The two extra keys are intentionally plugin-owned and must be added to
     * the owning form as domainauthentication_justification and
     * domainauthentication_expirationdate.
     *
     * @param array $data
     * @param int|null $now
     * @return array
     */
    public static function validate(array $data, $now = null) {
        $errors = [];
        $email = trim((string)($data['email'] ?? ''));
        $domain = self::get_domain($email);

        if ($domain === null) {
            $errors['email'] = get_string('erroremailinvalid', 'local_domainauthentication');
            return $errors;
        }

        if (self::is_institutional_domain($domain)) {
            return $errors;
        }

        $justification = trim((string)($data['domainauthentication_justification'] ?? ''));
        if ($justification === '') {
            $errors['domainauthentication_justification'] = get_string(
                'errorjustificationrequired', 'local_domainauthentication');
        }

        $expiration = filter_var(
            trim((string)($data['domainauthentication_expirationdate'] ?? '')),
            FILTER_VALIDATE_INT
        );
        $now = $now ?? time();
        if ($expiration === false || $expiration <= $now) {
            $errors['domainauthentication_expirationdate'] = get_string(
                'errorexpirationdaterequired', 'local_domainauthentication');
        }

        return $errors;
    }
}
