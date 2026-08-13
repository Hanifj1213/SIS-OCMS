<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Services\ChecksheetIntegrityService;
use Illuminate\Http\Request;

class ChecksheetController extends Controller
{
    public function __construct(
        private readonly ChecksheetIntegrityService $integrity,
    ) {}

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
        if (! auth()->user()?->canOperateOverhaul()) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $request->validate([
            'item_id' => 'required|string',
            'answer'  => 'required|string|in:good,bad,none',
        ]);

        $itemId = $request->item_id;
        $answer = $request->answer;
        $userId = auth()->id();

        $target = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        if ($denied = $this->integrity->mutationDeniedReason($component->fresh(), $target)) {
            return response()->json(['error' => $denied], 403);
        }

        if (! $this->integrity->isValidItemId($target, $itemId)) {
            return response()->json([
                'success' => false,
                'message' => 'Item checksheet tidak dikenal.',
            ], 422);
        }

        $checksheet = null;
        $lastError = null;
        for ($attempt = 0; $attempt < 4; $attempt++) {
            try {
                $checksheet = \Illuminate\Support\Facades\DB::transaction(function () use ($component, $stage, $itemId, $answer, $userId) {
                    $checksheet = $component->checksheets()
                        ->where('stage_number', $stage)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $componentLocked = $component->fresh();
                    if ($denied = $this->integrity->mutationDeniedReason($componentLocked, $checksheet)) {
                        abort(403, $denied);
                    }

                    $answers = $this->integrity->sanitizeAnswers($checksheet, $checksheet->answers ?? []);
                    $answers[$itemId] = $answer;

                    $updateData = [
                        'answers'   => $answers,
                        'filled_by' => $userId,
                        'completed_at' => $this->integrity->resolveCompletedAt($checksheet, $answers),
                    ];

                    $checksheet->update($updateData);

                    return $checksheet->fresh();
                });
                $lastError = null;
                break;
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                if ($e->getStatusCode() === 403) {
                    return response()->json(['error' => $e->getMessage()], 403);
                }
                throw $e;
            } catch (\Throwable $e) {
                $lastError = $e;
                $msg = $e->getMessage();
                if (! str_contains($msg, 'database is locked') && ! str_contains($msg, 'HY000')) {
                    throw $e;
                }
                usleep(120000 * ($attempt + 1));
            }
        }

        if ($lastError || ! $checksheet) {
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
        if (! auth()->user()?->canOperateOverhaul()) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $request->validate([
            'label' => 'required|string|max:255',
            'group' => 'nullable|string|max:100',
        ]);

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        if ($denied = $this->integrity->mutationDeniedReason($component->fresh(), $checksheet)) {
            return response()->json(['error' => $denied], 403);
        }

        $items = $checksheet->items;
        $customId = 'CUSTOM-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));

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
        if (! auth()->user()?->canOperateOverhaul()) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $request->validate([
            'item_id' => 'required|string',
        ]);

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        if ($denied = $this->integrity->removeItemDeniedReason($component->fresh(), $checksheet, $request->item_id)) {
            $status = str_contains($denied, 'standar') || str_contains($denied, 'tidak dikenal') ? 422 : 403;

            return response()->json(['message' => $denied], $status);
        }

        $items = collect($checksheet->items)->filter(function ($item) use ($request) {
            return $item['id'] !== $request->item_id;
        })->values()->all();

        $answers = $this->integrity->sanitizeAnswers($checksheet, $checksheet->answers ?? []);
        unset($answers[$request->item_id]);

        $updatedChecksheet = $checksheet->replicate();
        $updatedChecksheet->items = $items;

        $checksheet->update([
            'items'        => $items,
            'answers'      => $answers,
            'completed_at' => $this->integrity->resolveCompletedAt($updatedChecksheet, $answers),
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
        if (! auth()->user()?->canOperateOverhaul()) {
            return response()->json(['error' => 'Tidak memiliki izin.'], 403);
        }

        $checksheet = $component->checksheets()
            ->where('stage_number', $stage)
            ->firstOrFail();

        if ($denied = $this->integrity->mutationDeniedReason($component->fresh(), $checksheet)) {
            return response()->json(['error' => $denied], 403);
        }

        $rawAnswers = $request->input('answers', []);
        if (! is_array($rawAnswers)) {
            return response()->json(['message' => 'Format answers tidak valid.'], 422);
        }

        $invalid = $this->integrity->invalidAnswerKeys($checksheet, $rawAnswers);
        if ($invalid !== []) {
            return response()->json([
                'message' => 'Jawaban checksheet tidak valid.',
                'invalid' => $invalid,
            ], 422);
        }

        $answers = $this->integrity->sanitizeAnswers($checksheet, $rawAnswers);

        $checksheet->update([
            'answers'      => $answers,
            'filled_by'    => auth()->id(),
            'completed_at' => $this->integrity->resolveCompletedAt($checksheet, $answers),
        ]);

        return response()->json([
            'success'  => true,
            'progress' => $checksheet->fresh()->progress,
        ]);
    }
}
