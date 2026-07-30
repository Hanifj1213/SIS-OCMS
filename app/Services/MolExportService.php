<?php

namespace App\Services;

/**
 * Konstanta reason code MOL. Export PDF otomatis sudah dihapus —
 * dokumen MOL kini diisi manual oleh mekanik lalu diunggah (upload-document).
 */
class MolExportService
{
    public const ORDER_CODES = [
        'A' => 'A = SPO (Standard Part Overhaul / Normal Order)',
        'B' => 'B = NONE WHEN RECEIVE (Part tidak ada saat diterima di PRC)',
        'C' => 'C = BROKEN WHEN RECEIVE (Part rusak saat diterima di PRC)',
        'D' => 'D = UNSPEC WHEN RECEIVE (Part tidak sesuai spesifikasi / upgrade)',
        'E' => 'E = MISSING WHEN OVH PROGRESS (Part hilang saat proses OVH)',
        'F' => 'F = BROKEN WHEN OVH PROGRESS (Part rusak saat proses OVH)',
        'G' => 'G = MISS INSPECTION (Part terlewat proses inspection oleh QA)',
        'H' => 'H = REDO MACHINING (Part rusak saat dikerjakan vendor/bengkel luar)',
        'I' => 'I = MISS ORDER (Part terlewat tidak di-request)',
        'J' => 'J = MISS ADMINISTRATION (Part terlewat proses PPC/administrasi)',
        'K' => 'K = OTHER (Lain-lain)',
    ];
}
