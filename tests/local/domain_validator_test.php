<?php
namespace local_domainauthentication;

use advanced_testcase;
use local_domainauthentication\local\domain_validator;

/**
 * Tests for the domain and external-account validation rules.
 *
 * These tests do not prove that Moodle's standard user form invokes the
 * validator. That requires an integration test against a patched or
 * extensible user form, documented in the README.
 */
class domain_validator_test extends advanced_testcase {
    public function test_institutional_email_needs_no_external_data(): void {
        $this->assertSame([], domain_validator::validate([
            'email' => 'Usuario@UADY.MX',
        ], 1700000000));
    }

    public function test_each_authorized_domain_is_institutional(): void {
        foreach (domain_validator::get_authorized_domains() as $domain) {
            $this->assertSame([], domain_validator::validate([
                'email' => 'user@' . $domain,
            ], 1700000000));
        }
    }

    public function test_external_email_with_valid_data(): void {
        $this->assertSame([], domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_justification' => 'Temporary external collaboration',
            'domainauthentication_expirationdate' => '1800000000',
        ], 1700000000));
    }

    public function test_external_email_without_justification(): void {
        $errors = domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_expirationdate' => '1800000000',
        ], 1700000000);

        $this->assertArrayHasKey('domainauthentication_justification', $errors);
    }

    public function test_external_email_without_expiration(): void {
        $errors = domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_justification' => 'Temporary external collaboration',
        ], 1700000000);

        $this->assertArrayHasKey('domainauthentication_expirationdate', $errors);
    }

    public function test_expiration_equal_to_now_is_invalid(): void {
        $errors = domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_justification' => 'Reason',
            'domainauthentication_expirationdate' => '1700000000',
        ], 1700000000);

        $this->assertArrayHasKey('domainauthentication_expirationdate', $errors);
    }

    public function test_past_expiration_is_invalid(): void {
        $errors = domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_justification' => 'Reason',
            'domainauthentication_expirationdate' => '1699999999',
        ], 1700000000);

        $this->assertArrayHasKey('domainauthentication_expirationdate', $errors);
    }

    public function test_similar_but_unauthorized_domains_are_external(): void {
        $errors = domain_validator::validate([
            'email' => 'user@uady.mx.example.com',
            'domainauthentication_justification' => 'Reason',
            'domainauthentication_expirationdate' => '1800000000',
        ], 1700000000);

        $this->assertSame([], $errors);
    }

    public function test_malformed_email_is_rejected(): void {
        $errors = domain_validator::validate([
            'email' => 'user@@uady.mx',
        ], 1700000000);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_subdomain_with_label_boundary_is_institutional(): void {
        $this->assertTrue(domain_validator::is_institutional_domain('dept.uady.mx'));
        $this->assertFalse(domain_validator::is_institutional_domain('ejemplo-uady.mx'));
    }

    public function test_manipulated_values_are_rejected(): void {
        $errors = domain_validator::validate([
            'email' => 'user@example.com',
            'domainauthentication_justification' => '   ',
            'domainauthentication_expirationdate' => 'not-a-timestamp',
        ], 1700000000);

        $this->assertCount(2, $errors);
    }
}
