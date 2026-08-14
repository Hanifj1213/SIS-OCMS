<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Services\ChecksheetGsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * P1.3 — Apps Script harus fail-closed dan hanya menerima aksi runtime.
 *
 * Secret kosong berarti seluruh integrasi dinonaktifkan (bukan diizinkan).
 * Aplikasi hanya boleh memanggil action yang ada di allow-list, dan hanya
 * untuk spreadsheet yang memang dikelola OCMS. Log tidak boleh memuat
 * secret ataupun isi payload.
 */
class GsheetWebappSecurityTest extends TestCase
{
    use RefreshDatabase;

    private ChecksheetGsheetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ChecksheetGsheetService::class);

        config([
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/macros/s/TEST/exec',
            'checksheet_gsheets.secret' => 'test-secret',
        ]);
    }

    private function makeComponent(array $attributes = []): Component
    {
        return Component::create(array_merge([
            'serial_number' => 'SN-'.uniqid(),
            'egi' => 'PC1250-8',
            'major_category' => 'Control Valve',
            'model_type' => 'TEST',
            'current_stage' => 2,
            'status' => 'On Progress',
        ], $attributes));
    }

    public function test_secret_kosong_menonaktifkan_seluruh_integrasi(): void
    {
        config(['checksheet_gsheets.secret' => '']);
        Http::fake();

        $result = $this->service->postWebapp(['action' => 'ping']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('GSHEET_COPY_SECRET belum dikonfigurasi', $result['error']);

        // Fail-closed: permintaan tidak pernah dikirim ke jaringan.
        Http::assertNothingSent();
    }

    public function test_action_tidak_dikenal_dan_action_admin_ditolak(): void
    {
        Http::fake();

        foreach (['restore_from_xlsx', 'restore_revision', 'list_revisions', 'apply_decision_boxes', 'tidak_ada'] as $action) {
            $result = $this->service->postWebapp(['action' => $action]);

            $this->assertFalse($result['ok'], "Action {$action} seharusnya ditolak.");
            $this->assertStringContainsString('tidak diizinkan', $result['error']);
        }

        Http::assertNothingSent();
    }

    public function test_action_runtime_yang_diizinkan_berhasil_dengan_http_fake(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true, 'ping' => true], 200),
        ]);

        $result = $this->service->postWebapp(['action' => 'ping']);

        $this->assertTrue($result['ok']);
        Http::assertSentCount(1);
    }

    public function test_secret_selalu_diambil_dari_konfigurasi_bukan_dari_pemanggil(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        // Pemanggil mencoba menyuntikkan secret sendiri — harus ditimpa.
        $this->service->postWebapp(['action' => 'ping', 'secret' => 'secret-palsu']);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return ($body['secret'] ?? null) === 'test-secret';
        });
    }

    public function test_spreadsheet_di_luar_mapping_ocms_ditolak(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true, 'sheets' => []], 200),
        ]);

        $result = $this->service->readSpreadsheetValues(
            'https://docs.google.com/spreadsheets/d/SPREADSHEET_ORANG_LAIN/edit'
        );

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('tidak terdaftar', $result['error']);
        Http::assertNothingSent();
    }

    public function test_spreadsheet_milik_komponen_ocms_diizinkan(): void
    {
        $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/ID_OCMS/edit',
        ]);

        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'sheet' => 'INSPEKSI',
                'values' => [],
                'sheets' => [['name' => 'INSPEKSI', 'values' => []]],
            ], 200),
        ]);

        $result = $this->service->readSpreadsheetValues(
            'https://docs.google.com/spreadsheets/d/ID_OCMS/edit'
        );

        $this->assertTrue($result['ok']);
        Http::assertSentCount(1);
    }

    public function test_log_tidak_pernah_memuat_secret_atau_body(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('koneksi gagal');
        });

        $captured = [];
        Log::listen(function ($message) use (&$captured) {
            $captured[] = json_encode([$message->message, $message->context]);
        });

        $this->service->postWebapp(['action' => 'read', 'spreadsheet_id' => 'ID_RAHASIA']);

        $this->assertNotEmpty($captured, 'Kegagalan seharusnya tercatat di log.');

        foreach ($captured as $line) {
            $this->assertStringNotContainsString('test-secret', $line);
            $this->assertStringNotContainsString('ID_RAHASIA', $line);
        }
    }

    public function test_kegagalan_menghasilkan_correlation_id_untuk_penelusuran(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('koneksi gagal');
        });

        $result = $this->service->postWebapp(['action' => 'ping']);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('correlation_id', $result);
        $this->assertNotEmpty($result['correlation_id']);
    }

    public function test_apps_script_menyimpan_secret_di_script_properties_bukan_source(): void
    {
        $source = file_get_contents(base_path('tools/gsheet_copy_webapp.gs'));

        // Tidak boleh ada variabel SECRET literal di source.
        $this->assertStringNotContainsString("var SECRET = '", $source);

        // Secret diambil dari Script Properties dan fail-closed.
        $this->assertStringContainsString('PropertiesService.getScriptProperties', $source);
        $this->assertStringContainsString('OCMS_SECRET', $source);
        $this->assertStringContainsString('secretMatches_', $source);

        // Aksi administratif dipisahkan dan default nonaktif.
        $this->assertStringContainsString('OCMS_ADMIN_ACTIONS', $source);
        $this->assertStringContainsString('adminActionsEnabled_', $source);
        $this->assertStringContainsString('RUNTIME_ACTIONS', $source);
    }
}
