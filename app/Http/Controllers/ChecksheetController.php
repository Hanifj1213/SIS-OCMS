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

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        // Update answers JSON
        $answers = $checksheet->answers ?? [];
        $answers[$request->item_id] = $request->answer;
        
        $updateData = [
            'answers'   => $answers,
            'filled_by' => auth()->id(),
        ];

        // Mark completed if all items answered
        if (count($answers) >= count($checksheet->items)) {
            $updateData['completed_at'] = now();
        }

        $checksheet->update($updateData);

        return response()->json([
            'success'  => true,
            'progress' => $checksheet->fresh()->progress,
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
