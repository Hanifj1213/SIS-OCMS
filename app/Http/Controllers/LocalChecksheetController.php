<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentSpreadsheetAnswer;
use App\Models\SpreadsheetLayout;
use Illuminate\Http\Request;

/**
 * Checksheet spreadsheet yang disajikan lokal — tampilan 1:1 dengan Excel,
 * datanya di database, tanpa Google Sheets.
 */
class LocalChecksheetController extends Controller
{
    /** Jenis checksheet yang boleh dibuka. */
    private const KINDS = [
        'disassembly' => 'Disassembly',
        'measurement' => 'Measurement',
        'subassy_disassembly' => 'Sub Assy Disassembly',
        'subassy_measurement' => 'Sub Assy Measurement',
        'inspection' => 'Inspection',
    ];

    /** Daftar layout yang sudah diimpor. */
    public function index()
    {
        $layouts = SpreadsheetLayout::query()
            ->orderBy('major_category')
            ->orderBy('egi_model')
            ->orderBy('kind')
            ->get(['layout_id', 'major_category', 'egi_model', 'kind', 'source_file', 'sheet_count', 'part_row_count', 'imported_at']);

        return view('checksheet.layouts', ['layouts' => $layouts]);
    }

    /** Pratinjau template (tanpa komponen) — untuk menilai kemiripan tampilan. */
    public function preview(Request $request, SpreadsheetLayout $layout)
    {
        $sheets = $layout->sheets();
        $active = $this->activeSheet($request, $sheets);

        return view('checksheet.spreadsheet', [
            'layout' => $layout,
            'sheets' => $sheets,
            'active' => $active,
            'answers' => [],
            'editable' => false,
            'component' => null,
            'title' => $layout->major_category . ' ' . ($layout->egi_model ?: '') . ' — ' . self::KINDS[$layout->kind] ?? $layout->kind,
        ]);
    }

    /** Checksheet milik satu komponen — bisa diisi. */
    public function show(Request $request, Component $component, string $kind)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        $layout = SpreadsheetLayout::forComponent($component, $kind);
        abort_if(!$layout, 404, "Layout {$kind} untuk {$component->major_category} {$component->egi} belum diimpor.");

        $sheets = $layout->sheets();
        $active = $this->activeSheet($request, $sheets);

        $answers = ComponentSpreadsheetAnswer::query()
            ->where('comp_id', $component->comp_id)
            ->where('layout_id', $layout->layout_id)
            ->where('sheet', $active['name'])
            ->pluck('value', 'cell_ref')
            ->all();

        return view('checksheet.spreadsheet', [
            'layout' => $layout,
            'sheets' => $sheets,
            'active' => $active,
            'answers' => $answers,
            'editable' => $this->canEdit(),
            'component' => $component,
            'title' => $component->serial_number . ' — ' . (self::KINDS[$kind] ?? $kind),
        ]);
    }

    /** Simpan satu sel keputusan. */
    public function saveCell(Request $request, Component $component, string $kind)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        if (!$this->canEdit()) {
            return response()->json(['ok' => false, 'message' => 'Tidak memiliki izin.'], 403);
        }

        $data = $request->validate([
            'sheet' => 'required|string|max:255',
            'cell_ref' => 'required|string|max:16',
            'value' => 'nullable|string|max:255',
        ]);

        $layout = SpreadsheetLayout::forComponent($component, $kind);
        abort_if(!$layout, 404);

        // Hanya sel yang memang terdaftar sebagai kolom keputusan yang boleh
        // ditulis — mencegah sembarang sel diubah lewat request.
        if (!$this->isDecisionCell($layout, $data['sheet'], $data['cell_ref'])) {
            return response()->json(['ok' => false, 'message' => 'Sel ini tidak bisa diisi.'], 422);
        }

        ComponentSpreadsheetAnswer::updateOrCreate(
            [
                'comp_id' => $component->comp_id,
                'layout_id' => $layout->layout_id,
                'sheet' => $data['sheet'],
                'cell_ref' => $data['cell_ref'],
            ],
            [
                'value' => $data['value'] ?: null,
                'filled_by' => auth()->id(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    private function isDecisionCell(SpreadsheetLayout $layout, string $sheet, string $ref): bool
    {
        foreach ($layout->decision_map[$sheet]['parts'] ?? [] as $part) {
            if (in_array($ref, $part['cells'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']);
    }

    /**
     * @param  list<array<string, mixed>>  $sheets
     * @return array<string, mixed>
     */
    private function activeSheet(Request $request, array $sheets): array
    {
        abort_if($sheets === [], 404, 'Layout tidak punya sheet.');

        $wanted = (string) $request->query('sheet', '');
        foreach ($sheets as $sheet) {
            if ($sheet['name'] === $wanted) {
                return $sheet;
            }
        }

        return $sheets[0];
    }
}
