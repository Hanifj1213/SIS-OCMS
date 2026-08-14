<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test ocms:production-check command.
 *
 * RefreshDatabase dibutuhkan agar tabel wajib (components, users, dll)
 * tersedia di :memory: SQLite. Cache driver sudah 'array' dari phpunit.xml
 * sehingga Cache::put tidak memicu transaksi DB.
 */
class ProductionCheckTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Development environment (default test config)
    // ------------------------------------------------------------------

    public function test_passes_in_default_development_environment(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->assertExitCode(0);
    }

    // ------------------------------------------------------------------
    // Production error conditions
    // ------------------------------------------------------------------

    public function test_fails_when_production_debug_is_true(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->assertExitCode(1);
    }

    public function test_fails_when_production_uses_sqlite(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'app.url' => 'https://ocms.example.com',
            'database.default' => 'sqlite',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->assertExitCode(1);
    }

    public function test_fails_when_gsheet_secret_is_empty(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => '',
        ]);

        $this->artisan('ocms:production-check')
            ->assertExitCode(1);
    }

    public function test_fails_when_app_key_is_empty(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.key' => '',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->assertExitCode(1);
    }

    // ------------------------------------------------------------------
    // Strict mode
    // ------------------------------------------------------------------

    public function test_strict_mode_fails_on_warnings(): void
    {
        // production + http url → warning; production + sqlite → error
        // strict ensures exit 1 regardless
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'app.url' => 'http://192.168.1.100',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
            'session.driver' => 'database',
            'session.encrypt' => true,
        ]);

        $this->artisan('ocms:production-check', ['--strict' => true])
            ->assertExitCode(1);
    }

    // ------------------------------------------------------------------
    // Output format & security
    // ------------------------------------------------------------------

    public function test_output_contains_check_results(): void
    {
        config([
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->expectsOutputToContain('APP_KEY')
            ->expectsOutputToContain('APP_DEBUG')
            ->expectsOutputToContain('DB Connection')
            ->expectsOutputToContain('PASS');
    }

    public function test_output_does_not_print_secret_values(): void
    {
        $secret = 'super-secret-gsheet-token-12345';
        config([
            'app.key' => 'base64:aWxvdmVsbzEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNA==',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => $secret,
        ]);

        $this->artisan('ocms:production-check')
            ->doesntExpectOutputToContain($secret)
            ->doesntExpectOutputToContain('aWxvdmVsbzEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNA');
    }

    // ------------------------------------------------------------------
    // Individual check verification
    // ------------------------------------------------------------------

    public function test_checks_storage_symlink(): void
    {
        config([
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->expectsOutputToContain('Storage Symlink');
    }

    public function test_checks_logging_configuration(): void
    {
        config([
            'app.key' => 'base64:dGVzdC1rZXktdGhhdC1pcy1kZWZpbml0ZWx5LWxvbmc=',
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/test',
            'checksheet_gsheets.secret' => 'test-secret-value',
        ]);

        $this->artisan('ocms:production-check')
            ->expectsOutputToContain('LOG_LEVEL')
            ->expectsOutputToContain('LOG_STACK');
    }
}
