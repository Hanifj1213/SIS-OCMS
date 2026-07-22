<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menduplikasi template Google Sheets checksheet (per EGI) menjadi
 * spreadsheet milik satu komponen, via Apps Script Web App.
 *
 * Empat jenis checksheet tahap 2:
 * mainline disassembly/measurement + sub-assy disassembly/measurement.
 */
class ChecksheetGsheetService
{
    private const KINDS = [
        'disassembly' => [
            'config' => 'disassembly_templates',
            'column' => 'gsheet_url',
            'prefix' => 'DISASSY',
        ],
        'measurement' => [
            'config' => 'measurement_templates',
            'column' => 'gsheet_measurement_url',
            'prefix' => 'MEASUREMENT',
        ],
        'subassy_disassembly' => [
            'config' => 'subassy_disassembly_templates',
            'column' => 'gsheet_subassy_disassembly_url',
            'prefix' => 'SUBASSY DISASSY',
        ],
        'subassy_measurement' => [
            'config' => 'subassy_measurement_templates',
            'column' => 'gsheet_subassy_measurement_url',
            'prefix' => 'SUBASSY MEASUREMENT',
        ],
    ];

    /**
     * ID template jenis tertentu untuk komponen ini, atau null.
     */
    public function templateIdFor(Component $component, string $kind = 'disassembly'): ?string
    {
        if ($component->major_category !== 'Engine') {
            return null;
        }

        $egi = strtoupper(trim((string) $component->egi));
        $id = config('checksheet_gsheets.' . self::KINDS[$kind]['config'] . '.' . $egi);

        return $id ?: null;
    }

    /**
     * Duplikasi semua jenis template yang tersedia untuk komponen ini.
     */
    public function duplicateForComponent(Component $component): void
    {
        foreach (array_keys(self::KINDS) as $kind) {
            $this->duplicateKind($component, $kind);
        }
    }

    /**
     * Duplikasi satu jenis template dan simpan URL-nya.
     * Return true jika komponen (jadi) punya URL. Kegagalan tidak melempar
     * exception — pendaftaran komponen tidak boleh gagal karena Google down.
     */
    public function duplicateKind(Component $component, string $kind): bool
    {
        $column = self::KINDS[$kind]['column'];

        if ($component->{$column}) {
            return true; // sudah punya
        }

        $templateId = $this->templateIdFor($component, $kind);
        $webappUrl = config('checksheet_gsheets.webapp_url');

        if (!$templateId || !$webappUrl) {
            return false;
        }

        $name = sprintf(
            '%s %s - SN %s%s',
            self::KINDS[$kind]['prefix'],
            strtoupper(trim((string) $component->egi)),
            $component->serial_number,
            $component->unit_code ? ' (' . $component->unit_code . ')' : ''
        );

        try {
            $response = Http::timeout(20)->post($webappUrl, [
                'template_id' => $templateId,
                'name' => $name,
                'secret' => config('checksheet_gsheets.secret', ''),
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false) && !empty($data['url'])) {
                $component->update([$column => $data['url']]);
                return true;
            }

            Log::warning('Duplikasi GSheet gagal', [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
                'status' => $response->status(),
                'body' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Duplikasi GSheet error: ' . $e->getMessage(), [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
            ]);
        }

        return false;
    }

    /**
     * Apakah masih ada template yang tersedia tapi belum terduplikasi?
     */
    public function hasPendingDuplication(Component $component): bool
    {
        foreach (self::KINDS as $kind => $meta) {
            if (!$component->{$meta['column']} && $this->templateIdFor($component, $kind)) {
                return true;
            }
        }

        return false;
    }
}
