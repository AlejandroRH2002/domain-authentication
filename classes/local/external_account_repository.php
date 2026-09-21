<?php
namespace local_domainauthentication\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistence for external-domain account metadata.
 */
class external_account_repository {
    /**
     * Save metadata for an existing Moodle user.
     *
     * The caller must invoke this only after the user form has passed all
     * validation and as part of the same transaction as user creation.
     *
     * @param int $userid
     * @param string $justification
     * @param int $expirationdate
     * @param int|null $now
     * @return int
     */
    public static function save($userid, $justification, $expirationdate, $now = null) {
        global $DB;

        $now = $now ?? time();
        $userid = (int)$userid;
        $user = $DB->get_record('user', ['id' => $userid], 'id,email', MUST_EXIST);
        $domain = domain_validator::get_domain($user->email);
        $justification = trim((string)$justification);
        $expirationdate = filter_var((string)$expirationdate, FILTER_VALIDATE_INT);

        if ($domain === null || domain_validator::is_institutional_domain($domain) || $justification === '' ||
                $expirationdate === false || $expirationdate <= $now) {
            throw new \invalid_parameter_exception('Invalid external account metadata.');
        }

        $record = (object)[
            'userid' => $userid,
            'justification' => $justification,
            'expirationdate' => $expirationdate,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $existing = $DB->get_record('domainauthentication', ['userid' => $record->userid]);
        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('domainauthentication', $record);
            return (int)$record->id;
        }

        return (int)$DB->insert_record('domainauthentication', $record);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public static function delete($userid) {
        global $DB;
        return $DB->delete_records('domainauthentication', ['userid' => (int)$userid]);
    }
}
