<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_domainauthentication.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_domainauthentication_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026092100) {
        $table = new xmldb_table('domainauthentication');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('justification', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('expirationdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('userid_uix', XMLDB_INDEX_UNIQUE, ['userid']);
            $table->add_index('expirationdate_ix', XMLDB_INDEX_NOTUNIQUE, ['expirationdate']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026092100, 'local', 'domainauthentication');
    }

    if ($oldversion < 2026092101) {
        upgrade_plugin_savepoint(true, 2026092101, 'local', 'domainauthentication');
    }

    return true;
}
