<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentChecksheet;
use Illuminate\Http\Request;

class ChecksheetController extends Controller
{
    /**
     * Tampilkan UI checksheet interaktif (Typeform-style).
     */
    public function show(Component $component, int $stage)
    {
        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        $stageNames = ComponentController::STAGE_NAMES;

        return view('overhauls.checksheet', [
            'comp'       => $component,
            'checksheet' => $checksheet,
            'stageName'  => $stageNames[$stage] ?? 'Tahap ' . $stage,
            'stage'      => $stage,
        ]);
    }

    /**
     * Simpan jawaban per-item (dipanggil via AJAX/fetch).
     */
    public function saveAnswer(Request $request, Component $component, int $stage)
    {
        $request->validate([
            'item_id' => 'required|string',
            'answer'  => 'required|string|in:good,bad,none',
        ]);

        $itemId = $request->item_id;
        $answer = $request->answer;
        $userId = auth()->id();

        // Retry singkat untuk SQLite "database is locked" saat polling/status
        // dan POST jawaban saling berkejaran di artisan serve.
        $checksheet = null;
        $lastError = null;
        for ($attempt = 0; $attempt < 4; $attempt++) {
            try {
                $checksheet = \Illuminate\Support\Facades\DB::transaction(function () use ($component, $stage, $itemId, $answer, $userId) {
                    $checksheet = $component->checksheets()
                        ->where('stage_number', $stage)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $answers = $checksheet->answers ?? [];
                    $answers[$itemId] = $answer;

                    $updateData = [
                        'answers'   => $answers,
                        'filled_by' => $userId,
                    ];

                    if (count($answers) >= count($checksheet->items ?? [])) {
                        $updateData['completed_at'] = now();
                    }

                    $checksheet->update($updateData);

                    return $checksheet->fresh();
                });
                $lastError = null;
                break;
            } catch (\Throwable $e) {
                $lastError = $e;
                $msg = $e->getMessage();
                if (!str_contains($msg, 'database is locked') && !str_contains($msg, 'HY000')) {
                    throw $e;
                }
                usleep(120000 * ($attempt + 1));
            }
        }

        if ($lastError || !$checksheet) {
            return response()->json([
                'success' => false,
                'message' => 'Database sibuk, coba lagi.',
            ], 503);
        }

        return response()->json([
            'success'  => true,
            'progress' => $checksheet->progress,
        ]);
    }

    /**
     * Tambah custom item ke checksheet (Mechanic/Admin only).
     */
    public function addItem(Request $request, Component $component, int $stage)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $request->validate([
            'label' => 'required|string|max:255',
            'group' => 'nullable|string|max:100',
        ]);

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        $items = $checksheet->items;
        $customId = 'CUSTOM-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $items[] = [
            'id'     => $customId,
            'group'  => $request->group ?: 'Custom Items',
            'label'  => trim($request->label),
            'custom' => true,
        ];

        $checksheet->update(['items' => $items]);

        return response()->json([
            'success' => true,
            'item'    => end($items),
            'total'   => count($items),
        ]);
    }

    /**
     * Hapus/skip item dari checksheet (Mechanic/Admin only).
     */
    public function removeItem(Request $request, Component $component, int $stage)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $request->validate([
            'item_id' => 'required|string',
        ]);

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        $items = collect($checksheet->items)->filter(function ($item) use ($request) {
            return $item['id'] !== $request->item_id;
        })->values()->all();

        // Also remove answer if exists
        $answers = $checksheet->answers ?? [];
        unset($answers[$request->item_id]);

        $checksheet->update([
            'items'   => $items,
            'answers' => $answers,
        ]);

        return response()->json([
            'success' => true,
            'total'   => count($items),
        ]);
    }

    /**
     * Simpan data JSON dari Jspreadsheet untuk komponen tahap tertentu.
     */
    public function saveSpreadsheet(Request $request, Component $component, int $stage)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        $answers = $request->input('answers', []);
        
        $checksheet->update([
            'answers' => $answers,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
