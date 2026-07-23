<?php

namespace App\Services;

use App\Models\Component;
use App\Models\SpreadsheetLayout;

/**
 * Aturan integritas checksheet spreadsheet lokal (tanpa GSheet).
 */
class LocalChecksheetIntegrityService
{
    /** @var array<string, int> */
    private const KIND_ACTIVE_STAGE = [
        'disassembly' => 2,
        'measurement' => 2,
        'subassy_disassembly' => 2,
        'subassy_measurement' => 2,
        'inspection' => 2,
    ];

    /** @var list<string> */
    public const ALLOWED_VALUES = ['', '1'];

    public function mutationDeniedReason(Component $component, string $kind): ?string
    {
        $requiredStage = self::KIND_ACTIVE_STAGE[$kind] ?? null;

        if ($requiredStage === null) {
            return 'Jenis checksheet tidak dikenal.';
        }

        if ($component->current_stage !== $requiredStage) {
            return 'Checksheet spreadsheet hanya dapat diubah pada tahap aktif yang sesuai.';
        }

        if ($component->is_waiting_approval) {
            return 'Checksheet tidak dapat diubah saat menunggu approval.';
        }

        return null;
    }

    public function layoutMatchesComponent(Component $component, SpreadsheetLayout $layout, string $kind): bool
    {
        if ($layout->kind !== $kind) {
            return false;
        }

        if ($layout->major_category !== $component->major_category) {
            return false;
        }

        $egi = strtoupper(trim((string) $component->egi));
        $layoutEgi = $layout->egi_model !== null ? strtoupper(trim((string) $layout->egi_model)) : null;

        return $layoutEgi === null || $layoutEgi === $egi;
    }

    public function isDecisionCell(SpreadsheetLayout $layout, string $sheet, string $ref): bool
    {
        foreach ($layout->decision_map[$sheet]['parts'] ?? [] as $part) {
            if (in_array($ref, $part['cells'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    public function normalizeValue(?string $value): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (! in_array($normalized, self::ALLOWED_VALUES, true)) {
            return null;
        }

        return $normalized;
    }

    public function sheetExists(SpreadsheetLayout $layout, string $sheet): bool
    {
        foreach ($layout->sheets() as $row) {
            if (($row['name'] ?? null) === $sheet) {
                return true;
            }
        }

        return false;
    }
}
