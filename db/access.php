<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/domainauthentication:createuser' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
