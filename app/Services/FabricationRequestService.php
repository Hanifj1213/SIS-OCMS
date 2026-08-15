<?php

namespace App\Services;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\FrNumberSequence;
use App\Models\PartRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
     * Jumlah percobaan saat reserve nomor + insert FR tabrakan (deadlock/unique).
     */
    private const MAX_RETRIES = 3;

    /**
     * Nomor FR resmi: FR/SIS/RC/{seq 4 digit}/{bulan Romawi}/{tahun}/INT
     *
     * Reserve nomor dan insert FR WAJIB dalam transaksi yang sama (createDraft).
     * Pemanggil langsung hanya untuk preview/test.
     */
    public function nextNumber(?\DateTimeInterface $at = null): string
    {
        $at = $at ? \Carbon\Carbon::parse($at) : now();

        return DB::transaction(
            fn () => $this->allocateNextNumber($at),
            self::MAX_RETRIES,
        );
    }

    /**
     * Reserve nomor berikutnya dari counter tahunan (dipanggil di dalam transaksi).
     */
    private function allocateNextNumber(\DateTimeInterface $at): string
    {
        $year = (int) $at->format('Y');
        $month = (int) $at->format('n');
        $roman = self::ROMAN_MONTHS[$month] ?? 'I';

        FrNumberSequence::query()->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
        ]);

        /** @var FrNumberSequence $row */
        $row = FrNumberSequence::query()
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();

        $row->last_number++;
        $row->save();

        $seq = str_pad((string) $row->last_number, 4, '0', STR_PAD_LEFT);

        return "FR/SIS/RC/{$seq}/{$roman}/{$year}/INT";
    }

    /**
     * Naikkan counter tahunan bila nomor manual lebih tinggi — mencegah auto
     * scan memberi nomor yang sudah dipakai manual.
     */
    public function syncSequenceFromManualNumber(string $frNumber): void
    {
        if (! preg_match('#^FR/SIS/RC/(\d+)/[^/]+/(\d{4})/INT$#', trim($frNumber), $matches)) {
            return;
        }

        $seq = (int) $matches[1];
        $year = (int) $matches[2];

        DB::transaction(function () use ($year, $seq) {
            FrNumberSequence::query()->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
            ]);

            /** @var FrNumberSequence $row */
            $row = FrNumberSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            if ($seq > $row->last_number) {
                $row->update(['last_number' => $seq]);
            }
        }, self::MAX_RETRIES);
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
     * Scan ulang juga menyesuaikan FR/MOL draft dengan centang terbaru di sheet.
     */
    public function scanCandidates(Component $component): array
    {
        $gsheet = $this->gsheetService->readPartDecisionRows($component);
        $profile = $gsheet['profile'] ?? 'inspection';
        $profileLabel = match ($profile) {
            'disassembly' => 'Disassembly (SALVAGE → FR, REPLACE → PR)',
            'inspection+disassembly' => 'Inspection & Disassembly (U/R / SALVAGE → FR, R/N / REPLACE → PR)',
            default => 'Inspection (U/R → FR, R/N → PR)',
        };

        $formDesiredFrKeys = [];
        $formDesiredPrKeys = [];
        $formManagedKeys = [];

        foreach ($component->inspectionDetails()->where('decision', 'Repair')->get() as $detail) {
            $key = $this->partKey($detail->part_name, null);
            $formManagedKeys[$key] = true;
            $formDesiredFrKeys[$key] = true;
        }

        foreach ($component->inspectionDetails()->where('decision', 'Replace')->get() as $detail) {
            $key = $this->partKey($detail->part_name, null);
            $formManagedKeys[$key] = true;
            $formDesiredPrKeys[$key] = true;
        }

        $gsheetDesiredFrKeys = [];
        $gsheetDesiredPrKeys = [];
        $gsheetManagedKeys = [];

        if (empty($gsheet['error'])) {
            foreach ($gsheet['rows'] ?? [] as $row) {
                $section = trim((string) ($row['sheet'] ?? ''));
                $key = $this->partKey($row['part_name'] ?? '', $section);
                $gsheetManagedKeys[$key] = true;

                if (! empty($row['needs_repair'])) {
                    $gsheetDesiredFrKeys[$key] = true;
                }
                if (! empty($row['needs_replace'])) {
                    $gsheetDesiredPrKeys[$key] = true;
                }
            }
        }

        $sync = $this->syncWithScanDecisions(
            $component,
            $gsheetDesiredFrKeys,
            $gsheetDesiredPrKeys,
            $gsheetManagedKeys,
            $formDesiredFrKeys,
            $formDesiredPrKeys,
            $formManagedKeys,
        );

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
            'sync' => $sync,
            'gsheet_error' => $gsheet['error'] ?? null,
            'gsheet_warning' => $gsheet['warning'] ?? null,
            'gsheet_sheet' => $gsheet['sheet'] ?? null,
            'scan_profile' => $profile,
            'scan_profile_label' => $profileLabel,
        ];
    }

    /**
     * Sesuaikan FR/MOL hasil scan sebelumnya dengan centang terbaru.
     * Hanya menyentuh FR draft (source gsheet/form) dan MOL Pending tanpa data form MOL.
     *
     * @param  array<string, true>  $gsheetDesiredFrKeys
     * @param  array<string, true>  $gsheetDesiredPrKeys
     * @param  array<string, true>  $gsheetManagedKeys
     * @param  array<string, true>  $formDesiredFrKeys
     * @param  array<string, true>  $formDesiredPrKeys
     * @param  array<string, true>  $formManagedKeys
     * @return array{
     *   removed_fr: list<array{fr_id:int, fr_number:string, part_name:string, section:?string, reason:string}>,
     *   removed_pr: list<array{req_id:int, part_name:string, section:?string, reason:string}>,
     *   blocked: list<array{type:string, part_name:string, section:?string, reason:string}>
     * }
     */
    public function syncWithScanDecisions(
        Component $component,
        array $gsheetDesiredFrKeys,
        array $gsheetDesiredPrKeys,
        array $gsheetManagedKeys,
        array $formDesiredFrKeys,
        array $formDesiredPrKeys,
        array $formManagedKeys,
    ): array {
        $removedFr = [];
        $removedPr = [];
        $blocked = [];

        foreach ($component->fabricationRequests()->get() as $fr) {
            if (! in_array($fr->source, ['gsheet', 'form'], true)) {
                continue;
            }

            if ($fr->source === 'gsheet') {
                $lookupKey = $this->partKey($fr->part_name, $fr->section);
                if (! isset($gsheetManagedKeys[$lookupKey])) {
                    continue;
                }
                $wantsFr = isset($gsheetDesiredFrKeys[$lookupKey]);
                $wantsPr = isset($gsheetDesiredPrKeys[$lookupKey]);
            } else {
                $lookupKey = $this->partKey($fr->part_name, null);
                if (! isset($formManagedKeys[$lookupKey])) {
                    continue;
                }
                $wantsFr = isset($formDesiredFrKeys[$lookupKey]);
                $wantsPr = isset($formDesiredPrKeys[$lookupKey]);
            }

            if ($wantsFr) {
                continue;
            }

            if ($fr->status !== 'draft') {
                $blocked[] = [
                    'type' => 'fr',
                    'part_name' => $fr->part_name,
                    'section' => $fr->section,
                    'reason' => $wantsPr
                        ? 'FR sudah dicetak/selesai — tidak bisa diganti ke MOL otomatis'
                        : 'FR sudah dicetak/selesai — tidak dihapus otomatis',
                ];
                continue;
            }

            $reason = $wantsPr ? 'Keputusan berubah ke REPLACE/MOL' : 'Centang dicabut di spreadsheet';

            $this->deleteFabricationRequest($fr);
            $removedFr[] = [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_name' => $fr->part_name,
                'section' => $fr->section,
                'reason' => $reason,
            ];
        }

        foreach ($component->partRequests()->get() as $pr) {
            if (! $this->isScanManagedPartRequest($pr)) {
                continue;
            }

            $lookupKey = $this->partKey($pr->part_name, $pr->section);
            $formKey = $this->partKey($pr->part_name, null);

            if (isset($gsheetManagedKeys[$lookupKey])) {
                $wantsFr = isset($gsheetDesiredFrKeys[$lookupKey]);
                $wantsPr = isset($gsheetDesiredPrKeys[$lookupKey]);
            } elseif (isset($formManagedKeys[$formKey])) {
                $wantsFr = isset($formDesiredFrKeys[$formKey]);
                $wantsPr = isset($formDesiredPrKeys[$formKey]);
            } else {
                continue;
            }

            if ($wantsPr) {
                continue;
            }

            if ($pr->status !== 'Pending') {
                $blocked[] = [
                    'type' => 'pr',
                    'part_name' => $pr->part_name,
                    'section' => $pr->section,
                    'reason' => $wantsFr
                        ? 'MOL sudah diproses gudang — tidak bisa diganti ke FR otomatis'
                        : 'MOL sudah diproses gudang — tidak dihapus otomatis',
                ];
                continue;
            }

            $reason = $wantsFr ? 'Keputusan berubah ke SALVAGE/U/R' : 'Centang dicabut di spreadsheet';

            $pr->delete();
            $removedPr[] = [
                'req_id' => $pr->req_id,
                'part_name' => $pr->part_name,
                'section' => $pr->section,
                'reason' => $reason,
            ];
        }

        return [
            'removed_fr' => $removedFr,
            'removed_pr' => $removedPr,
            'blocked' => $blocked,
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
     * @param  string|null  $frNumber  Nomor manual; null = auto dari counter tahunan
     */
    public function createDraft(
        Component $component,
        array $data,
        ?int $userId = null,
        ?string $frNumber = null,
    ): FabricationRequest {
        $manualNumber = is_string($frNumber) ? trim($frNumber) : null;
        $manualNumber = ($manualNumber !== null && $manualNumber !== '') ? $manualNumber : null;

        return DB::transaction(function () use ($component, $data, $userId, $manualNumber) {
            $number = $manualNumber ?? $this->allocateNextNumber(now());

            return FabricationRequest::create([
                'comp_id' => $component->comp_id,
                'fr_number' => $number,
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
        }, self::MAX_RETRIES);
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

    /** MOL dari scan otomatis — bukan baris form MOL manual (punya WO/figure/index/remark/part number). */
    private function isScanManagedPartRequest(PartRequest $pr): bool
    {
        foreach (['wo_number', 'figure', 'index_no', 'part_number', 'remarks'] as $field) {
            $value = trim((string) ($pr->{$field} ?? ''));
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    private function deleteFabricationRequest(FabricationRequest $fr): void
    {
        foreach ($fr->imageList() as $image) {
            $this->deletePublicStoragePath($image['path'] ?? null);
        }

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $this->deletePublicStoragePath($fr->signature($role)['image'] ?? null);
        }

        $fr->delete();
    }

    private function deletePublicStoragePath(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        $relative = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : ltrim($path, '/');

        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }
}
