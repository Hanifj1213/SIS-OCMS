<?php

namespace App\Services;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\PartRequest;
use Illuminate\Support\Facades\DB;

class FabricationRequestService
{
    private const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public function __construct(
        private ChecksheetGsheetService $gsheetService
    ) {}

    /**
     * Nomor FR resmi: FR/SIS/RC/{seq 4 digit}/{bulan Romawi}/{tahun}/INT
     */
    public function nextNumber(?\DateTimeInterface $at = null): string
    {
        $at = $at ? \Carbon\Carbon::parse($at) : now();
        $year = (int) $at->format('Y');
        $month = (int) $at->format('n');
        $roman = self::ROMAN_MONTHS[$month] ?? 'I';

        return DB::transaction(function () use ($year, $roman) {
            $numbers = FabricationRequest::where('fr_number', 'like', "FR/SIS/RC/%/{$year}/INT")
                ->lockForUpdate()
                ->pluck('fr_number');

            $maxSeq = 0;
            foreach ($numbers as $number) {
                if (preg_match('#FR/SIS/RC/(\d+)/#', $number, $matches)) {
                    $maxSeq = max($maxSeq, (int) $matches[1]);
                }
            }

            $seq = str_pad((string) ($maxSeq + 1), 4, '0', STR_PAD_LEFT);

            return "FR/SIS/RC/{$seq}/{$roman}/{$year}/INT";
        });
    }

    /**
     * Buat draft FR otomatis dari inspection_details ber-decision Repair.
     *
     * @return FabricationRequest[]
     */
    public function createFromInspectionDetails(Component $component, ?int $userId = null): array
    {
        $created = [];

        $repairs = $component->inspectionDetails()
            ->where('decision', 'Repair')
            ->get();

        foreach ($repairs as $detail) {
            if ($this->hasFrForPart($component, $detail->part_name)) {
                continue;
            }

            $created[] = $this->createDraft(
                $component,
                [
                    'part_number' => null,
                    'part_name' => $detail->part_name,
                    'qty' => 1,
                    'work_type' => 'repair',
                    'instruction' => null,
                    'source' => 'form',
                ],
                $userId
            );
        }

        return $created;
    }

    /**
     * Gabungkan kandidat FR/PR dari form internal + Google Sheets.
     */
    public function scanCandidates(Component $component): array
    {
        $existingFrNames = $component->fabricationRequests()
            ->get(['part_name', 'section'])
            ->map(fn ($fr) => $this->partKey($fr->part_name, $fr->section))
            ->all();

        $existingPrNames = $component->partRequests()
            ->get(['part_name', 'section'])
            ->map(fn ($pr) => $this->partKey($pr->part_name, $pr->section))
            ->all();

        $candidates = [];
        $partRequestCandidates = [];
        $skipped = [];

        foreach ($component->inspectionDetails()->where('decision', 'Repair')->get() as $detail) {
            $entry = [
                'key' => 'form:' . $detail->insp_id,
                'source' => 'form',
                'part_number' => '',
                'part_name' => $detail->part_name,
                'qty' => 1,
                'work_type' => 'repair',
                'instruction' => '',
            ];
            $this->pushFrCandidate($candidates, $skipped, $entry, $existingFrNames);
        }

        foreach ($component->inspectionDetails()->where('decision', 'Replace')->get() as $detail) {
            $entry = [
                'key' => 'form-pr:' . $detail->insp_id,
                'source' => 'form',
                'part_name' => $detail->part_name,
                'qty' => 1,
            ];
            $this->pushPrCandidate($partRequestCandidates, $skipped, $entry, $existingPrNames);
        }

        $gsheet = $this->gsheetService->readPartDecisionRows($component);
        $profile = $gsheet['profile'] ?? 'inspection';
        $profileLabel = match ($profile) {
            'disassembly' => 'Disassembly (SALVAGE → FR, REPLACE → PR)',
            'inspection+disassembly' => 'Inspection & Disassembly (U/R / SALVAGE → FR, R/N / REPLACE → PR)',
            default => 'Inspection (U/R → FR, R/N → PR)',
        };

        foreach ($gsheet['rows'] ?? [] as $row) {
            $section = trim((string) ($row['sheet'] ?? ''));
            $rowRef = ($section !== '' ? $section . '!' : '') . ($row['row'] ?? $row['part_name']);

            if (!empty($row['needs_repair'])) {
                $entry = [
                    'key' => 'gsheet-fr:' . $rowRef,
                    'source' => 'gsheet',
                    'part_number' => $row['part_number'] ?? '',
                    'part_name' => $row['part_name'],
                    'section' => $section,
                    'qty' => 1,
                    'work_type' => 'repair',
                    'instruction' => '',
                    'sheet_row' => $row['row'] ?? null,
                ];
                $this->pushFrCandidate($candidates, $skipped, $entry, $existingFrNames);
            }

            if (!empty($row['needs_replace'])) {
                $entry = [
                    'key' => 'gsheet-pr:' . $rowRef,
                    'source' => 'gsheet',
                    'part_number' => $row['part_number'] ?? '',
                    'part_name' => $row['part_name'],
                    'section' => $section,
                    'qty' => 1,
                    'sheet_row' => $row['row'] ?? null,
                ];
                $this->pushPrCandidate($partRequestCandidates, $skipped, $entry, $existingPrNames);
            }
        }

        return [
            'candidates' => $candidates,
            'part_request_candidates' => $partRequestCandidates,
            'skipped' => $skipped,
            'gsheet_error' => $gsheet['error'] ?? null,
            'gsheet_sheet' => $gsheet['sheet'] ?? null,
            'scan_profile' => $profile,
            'scan_profile_label' => $profileLabel,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return PartRequest[]
     */
    public function createPartRequestsFromCandidates(Component $component, array $items): array
    {
        $created = [];

        foreach ($items as $item) {
            $partName = trim((string) ($item['part_name'] ?? ''));
            if ($partName === '') {
                continue;
            }

            $section = trim((string) ($item['section'] ?? '')) ?: null;

            if ($this->hasPartRequestForPart($component, $partName, $section)) {
                continue;
            }

            $created[] = $component->partRequests()->create([
                'part_name' => $partName,
                'section' => $section,
                'qty' => max(1, (int) ($item['qty'] ?? 1)),
                'status' => 'Pending',
            ]);
        }

        return $created;
    }

    public function hasPartRequestForPart(Component $component, string $partName, ?string $section = null): bool
    {
        $normalized = $this->partKey($partName, $section);

        return $component->partRequests()
            ->get()
            ->contains(fn (PartRequest $pr) => $this->partKey($pr->part_name, $pr->section) === $normalized);
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<int, array<string, mixed>>  $skipped
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $existingNames
     */
    private function pushFrCandidate(array &$candidates, array &$skipped, array $entry, array $existingNames): void
    {
        $normalized = $this->partKey($entry['part_name'], $entry['section'] ?? null);

        if (in_array($normalized, $existingNames, true)) {
            $skipped[] = $entry + ['reason' => 'Sudah punya FR', 'type' => 'fr'];
            return;
        }

        $alreadyListed = collect($candidates)->contains(
            fn ($item) => $this->partKey($item['part_name'], $item['section'] ?? null) === $normalized
        );
        if ($alreadyListed) {
            return;
        }

        $candidates[] = $entry;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<int, array<string, mixed>>  $skipped
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $existingNames
     */
    private function pushPrCandidate(array &$candidates, array &$skipped, array $entry, array $existingNames): void
    {
        $normalized = $this->partKey($entry['part_name'], $entry['section'] ?? null);

        if (in_array($normalized, $existingNames, true)) {
            $skipped[] = $entry + ['reason' => 'Sudah punya Part Request', 'type' => 'pr'];
            return;
        }

        $alreadyListed = collect($candidates)->contains(
            fn ($item) => $this->partKey($item['part_name'], $item['section'] ?? null) === $normalized
        );
        if ($alreadyListed) {
            return;
        }

        $candidates[] = $entry;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return FabricationRequest[]
     */
    public function createFromCandidates(Component $component, array $items, ?int $userId = null): array
    {
        $created = [];

        foreach ($items as $item) {
            $partName = trim((string) ($item['part_name'] ?? ''));
            if ($partName === '') {
                continue;
            }

            $section = trim((string) ($item['section'] ?? '')) ?: null;

            if ($this->hasFrForPart($component, $partName, $section)) {
                continue;
            }

            $workType = strtolower(trim((string) ($item['work_type'] ?? 'repair')));
            if (!in_array($workType, ['repair', 'fabrikasi', 'modifikasi'], true)) {
                $workType = 'repair';
            }

            $source = strtolower(trim((string) ($item['source'] ?? 'manual')));
            if (!in_array($source, ['form', 'gsheet', 'manual'], true)) {
                $source = 'manual';
            }

            $created[] = $this->createDraft(
                $component,
                [
                    'part_number' => trim((string) ($item['part_number'] ?? '')) ?: null,
                    'part_name' => $partName,
                    'section' => $section,
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    'work_type' => $workType,
                    'instruction' => trim((string) ($item['instruction'] ?? '')) ?: null,
                    'source' => $source,
                ],
                $userId
            );
        }

        return $created;
    }

    public function hasFrForPart(Component $component, string $partName, ?string $section = null): bool
    {
        $normalized = $this->partKey($partName, $section);

        return $component->fabricationRequests()
            ->get()
            ->contains(fn (FabricationRequest $fr) => $this->partKey($fr->part_name, $fr->section) === $normalized);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(Component $component, array $data, ?int $userId = null): FabricationRequest
    {
        return FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => $this->nextNumber(),
            'part_number' => $data['part_number'] ?? null,
            'part_name' => $data['part_name'],
            'section' => $data['section'] ?? null,
            'qty' => $data['qty'] ?? 1,
            'work_type' => $data['work_type'] ?? 'repair',
            'instruction' => $data['instruction'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'status' => 'draft',
            'created_by' => $userId,
        ]);
    }

    private function normalizePartName(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }

    /**
     * Identitas part = nama + section (tab asal). Tanpa section, part bernama
     * sama pada unit berbeda (Control Valve NO1 vs NO2) akan saling dianggap
     * duplikat dan hanya satu yang dapat FR.
     */
    private function partKey(?string $name, ?string $section = null): string
    {
        $key = $this->normalizePartName((string) $name);
        $sectionKey = $this->normalizePartName((string) $section);

        return $sectionKey === '' ? $key : $key . '@' . $sectionKey;
    }
}
