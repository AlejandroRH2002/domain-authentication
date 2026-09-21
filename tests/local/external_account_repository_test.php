<?php
namespace local_domainauthentication;

use advanced_testcase;
use local_domainauthentication\local\external_account_repository;

/**
 * Integration tests for the plugin metadata table.
 */
class external_account_repository_test extends advanced_testcase {
    public function test_external_account_metadata_can_be_saved_and_updated(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $id = external_account_repository::save($user->id, 'Reason', 1800000000, 1700000000);

        global $DB;
        $stored = $DB->get_record('domainauthentication', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame($user->id, (int)$stored->userid);
        $this->assertSame('Reason', $stored->justification);
        $this->assertSame(1800000000, (int)$stored->expirationdate);

        $updatedid = external_account_repository::save($user->id, 'Updated reason', 1900000000, 1700000100);
        $this->assertSame($id, $updatedid);
        $this->assertSame('Updated reason', $DB->get_field('domainauthentication', 'justification', ['userid' => $user->id]));
    }

    public function test_external_account_metadata_can_be_deleted(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        external_account_repository::save($user->id, 'Reason', 1800000000, 1700000000);

        $this->assertTrue(external_account_repository::delete($user->id));
        global $DB;
        $this->assertFalse($DB->record_exists('domainauthentication', ['userid' => $user->id]));
    }
}
