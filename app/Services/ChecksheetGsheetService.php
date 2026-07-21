<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menduplikasi template Google Sheets checksheet (per EGI) menjadi
 * spreadsheet milik satu komponen, via Apps Script Web App.
 */
class ChecksheetGsheetService
{
    /**
     * ID template untuk komponen ini, atau null jika tidak ada.
     */
    public function templateIdFor(Component $component): ?string
    {
        if ($component->major_category !== 'Engine') {
            return null;
        }

        $egi = strtoupper(trim((string) $component->egi));

        return config('checksheet_gsheets.templates.' . $egi);
    }

    /**
     * Duplikasi template untuk komponen dan simpan URL-nya.
     * Return true jika berhasil. Kegagalan tidak melempar exception —
     * pendaftaran komponen tidak boleh gagal hanya karena Google down.
     */
    public function duplicateForComponent(Component $component): bool
    {
        if ($component->gsheet_url) {
            return true; // sudah punya
        }

        $templateId = $this->templateIdFor($component);
        $webappUrl = config('checksheet_gsheets.webapp_url');

        if (!$templateId || !$webappUrl) {
            return false;
        }

        $name = sprintf(
            'DISASSY %s - SN %s%s',
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
                $component->update(['gsheet_url' => $data['url']]);
                return true;
            }

            Log::warning('Duplikasi GSheet gagal', [
                'comp_id' => $component->comp_id,
                'status' => $response->status(),
                'body' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Duplikasi GSheet error: ' . $e->getMessage(), [
                'comp_id' => $component->comp_id,
            ]);
        }

        return false;
    }
}
