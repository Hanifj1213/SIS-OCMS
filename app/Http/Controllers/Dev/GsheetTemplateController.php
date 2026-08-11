<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\GsheetTemplate;
use App\Services\ChecksheetGsheetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD mapping template Google Sheets per kind × kategori × EGI.
 * Menggantikan edit manual config/checksheet_gsheets.php.
 */
class GsheetTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = GsheetTemplate::query()
            ->orderBy('kind')
            ->orderBy('major_category')
            ->orderBy('egi')
            ->get()
            ->groupBy('kind');

        $categories = GsheetTemplate::query()
            ->whereNotNull('major_category')
            ->distinct()
            ->orderBy('major_category')
            ->pluck('major_category');

        return view('dev.gsheet-templates', [
            'templatesByKind' => $templates,
            'kinds' => ChecksheetGsheetService::kinds(),
            'kindLabels' => GsheetTemplate::KIND_LABELS,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(ChecksheetGsheetService::kinds())],
            'major_category' => 'nullable|string|max:100',
            'egi' => 'nullable|string|max:100',
            'spreadsheet' => 'required|string|max:500',
        ]);

        $spreadsheetId = $this->extractSpreadsheetId($data['spreadsheet']);
        if (! $spreadsheetId) {
            return back()->withInput()->withErrors([
                'spreadsheet' => 'Link/ID spreadsheet tidak valid. Tempel URL Google Sheets lengkap atau ID-nya saja.',
            ]);
        }

        $category = filled($data['major_category']) ? trim($data['major_category']) : null;
        $egi = filled($data['egi']) ? strtoupper(trim($data['egi'])) : null;

        GsheetTemplate::updateOrCreate(
            ['kind' => $data['kind'], 'major_category' => $category, 'egi' => $egi],
            ['spreadsheet_id' => $spreadsheetId],
        );

        return redirect()->route('dev.gsheet-templates.index')
            ->with('success', 'Template ' . $data['kind'] . ' untuk ' . ($category ?? 'default') . ($egi ? " / {$egi}" : '') . ' berhasil disimpan.');
    }

    public function update(Request $request, GsheetTemplate $gsheetTemplate)
    {
        $data = $request->validate([
            'spreadsheet' => 'required|string|max:500',
        ]);

        $spreadsheetId = $this->extractSpreadsheetId($data['spreadsheet']);
        if (! $spreadsheetId) {
            return back()->withErrors([
                'spreadsheet' => 'Link/ID spreadsheet tidak valid.',
            ]);
        }

        $gsheetTemplate->update(['spreadsheet_id' => $spreadsheetId]);

        return redirect()->route('dev.gsheet-templates.index')
            ->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(GsheetTemplate $gsheetTemplate)
    {
        $gsheetTemplate->delete();

        return redirect()->route('dev.gsheet-templates.index')
            ->with('success', 'Mapping template dihapus. Komponen yang sudah punya salinan spreadsheet tidak terpengaruh.');
    }

    /**
     * Terima URL Google Sheets lengkap ataupun ID mentah.
     */
    private function extractSpreadsheetId(string $input): ?string
    {
        $input = trim($input);

        $fromUrl = app(ChecksheetGsheetService::class)->extractSpreadsheetId($input);
        if ($fromUrl) {
            return $fromUrl;
        }

        // ID mentah: huruf/angka/dash/underscore, panjang wajar (ID Drive ±44 char)
        if (preg_match('/^[a-zA-Z0-9-_]{20,}$/', $input)) {
            return $input;
        }

        return null;
    }
}
