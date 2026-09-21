<?php
namespace local_domainauthentication;

use advanced_testcase;

/**
 * Explicit placeholders for checks that require a supported form integration.
 */
class form_integration_test extends advanced_testcase {
    public function test_user_without_create_permission_is_rejected(): void {
        $this->markTestSkipped(
            'Moodle 4.2-4.5 exposes no supported local-plugin hook to extend user_editadvanced_form.'
        );
    }

    public function test_invalid_external_user_is_rejected_before_creation(): void {
        $this->markTestSkipped(
            'Requires an integrated user_editadvanced_form validation point, unavailable without a core or form extension.'
        );
    }

    public function test_user_and_metadata_creation_roll_back_together(): void {
        $this->markTestSkipped(
            'Requires an integrated transaction around core user creation and plugin metadata persistence.'
        );
    }
}
