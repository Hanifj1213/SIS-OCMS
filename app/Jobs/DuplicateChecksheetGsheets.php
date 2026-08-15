<?php

namespace App\Jobs;

use App\Models\Component;
use App\Services\ChecksheetGsheetService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Duplikasi template Google Sheets untuk satu komponen di latar belakang.
 *
 * Sebelumnya ini dijalankan langsung di ComponentController@store. Ada empat
 * jenis template (disassembly, measurement, subassy disassembly, subassy
 * measurement) dan tiap panggilan Apps Script bisa memakan 10–20 detik, jadi
 * totalnya melewati batas 30 detik PHP dan pendaftaran komponen gagal dengan
 * "Maximum execution time exceeded" — padahal komponennya sudah tersimpan.
 *
 * ShouldBeUnique: halaman detail men-dispatch ulang setiap kali dibuka selama
 * masih ada URL kosong. Tanpa uniqueness, antrean bisa berisi banyak job untuk
 * komponen yang sama — dan bila ada lebih dari satu worker, dua job bisa
 * menyalin template yang sama bersamaan (file ganda di Google Drive).
 */
class DuplicateChecksheetGsheets implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /** Panggilan ke Apps Script lambat; beri ruang waktu yang cukup. */
    public int $timeout = 300;

    public int $tries = 3;

    /** Lock unik dilepas otomatis setelah 10 menit jika job macet. */
    public int $uniqueFor = 600;

    public function __construct(public int $compId) {}

    public function uniqueId(): string
    {
        return (string) $this->compId;
    }

    public function handle(ChecksheetGsheetService $gsheetService): void
    {
        $component = Component::find($this->compId);

        if (!$component) {
            return;
        }

        try {
            $gsheetService->duplicateForComponent($component);
        } catch (\Throwable $e) {
            // Kegagalan tidak boleh menghalangi alur kerja: halaman detail
            // komponen akan mencoba lagi saat dibuka.
            Log::warning('Duplikasi GSheet gagal untuk komponen ' . $this->compId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /** Jeda antar percobaan ulang: 30 detik, lalu 2 menit. */
    public function backoff(): array
    {
        return [30, 120];
    }
}
