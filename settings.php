<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_domainauthentication_createuser',
        get_string('createexternaluser', 'local_domainauthentication'),
        new moodle_url('/local/domainauthentication/index.php'),
        'local/domainauthentication:createuser'
    ));
}
