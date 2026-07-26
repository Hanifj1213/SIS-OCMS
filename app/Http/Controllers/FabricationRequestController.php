<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Services\FabricationRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FabricationRequestController extends Controller
{
    public function __construct(
        private FabricationRequestService $frService
    ) {}

    private function authorizeMechanic(): ?\Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        return null;
    }

    public function index(Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $items = $component->fabricationRequests()
            ->latest('fr_id')
            ->get()
            ->map(fn (FabricationRequest $fr) => [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_number' => $fr->part_number,
                'part_name' => $fr->part_name,
                'qty' => $fr->qty,
                'work_type' => $fr->work_type,
                'work_type_label' => $fr->workTypeLabel(),
                'source' => $fr->source,
                'status' => $fr->status,
                'status_label' => $fr->statusLabel(),
                'instruction' => $fr->instruction,
                'pdf_url' => route('components.fr.pdf', [$component->comp_id, $fr->fr_id]),
                'created_at' => $fr->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function scan(Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $result = $this->frService->scanCandidates($component);

        return response()->json([
            'ok' => true,
            'candidates' => $result['candidates'],
            'part_request_candidates' => $result['part_request_candidates'],
            'skipped' => $result['skipped'],
            'gsheet_error' => $result['gsheet_error'],
            'gsheet_sheet' => $result['gsheet_sheet'],
            'scan_profile' => $result['scan_profile'],
            'scan_profile_label' => $result['scan_profile_label'],
        ]);
    }

    public function store(Request $request, Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.part_name' => 'required_with:items|string|max:255',
            'items.*.part_number' => 'nullable|string|max:255',
            'items.*.section' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|integer|min:1',
            'items.*.work_type' => 'nullable|in:repair,fabrikasi,modifikasi',
            'items.*.instruction' => 'nullable|string|max:5000',
            'items.*.source' => 'nullable|in:form,gsheet,manual',
            'part_request_items' => 'nullable|array',
            'part_request_items.*.part_name' => 'required_with:part_request_items|string|max:255',
            'part_request_items.*.section' => 'nullable|string|max:255',
            'part_request_items.*.qty' => 'nullable|integer|min:1',
        ]);

        $frItems = $validated['items'] ?? [];
        $prItems = $validated['part_request_items'] ?? [];

        if ($frItems === [] && $prItems === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Pilih minimal satu kandidat FR atau Part Request.',
            ], 422);
        }

        $created = $frItems !== []
            ? $this->frService->createFromCandidates($component, $frItems, auth()->id())
            : [];

        $createdPr = $prItems !== []
            ? $this->frService->createPartRequestsFromCandidates($component, $prItems)
            : [];

        if ($created === [] && $createdPr === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada FR/PR baru yang dibuat (part mungkin sudah ada).',
            ], 422);
        }

        $messages = [];
        if ($created !== []) {
            $messages[] = count($created) . ' Fabrication Request';
        }
        if ($createdPr !== []) {
            $messages[] = count($createdPr) . ' Part Request';
        }

        return response()->json([
            'ok' => true,
            'message' => implode(' + ', $messages) . ' berhasil dibuat.',
            'created' => collect($created)->map(fn (FabricationRequest $fr) => [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_name' => $fr->part_name,
                'pdf_url' => route('components.fr.pdf', [$component->comp_id, $fr->fr_id]),
            ]),
            'created_part_requests' => collect($createdPr)->map(fn ($pr) => [
                'req_id' => $pr->req_id,
                'part_name' => $pr->part_name,
                'qty' => $pr->qty,
            ]),
        ]);
    }

    public function pdf(Component $component, FabricationRequest $fr)
    {
        if ($fr->comp_id !== $component->comp_id) {
            abort(404);
        }

        $component->load([]);
        $fr->load('creator');

        if (auth()->check() && auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            if ($fr->status === 'draft') {
                $fr->update(['status' => 'printed']);
            }
        }

        $pdf = Pdf::loadView('fr.pdf', [
            'component' => $component,
            'fr' => $fr,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '_', $fr->fr_number) . '.pdf';

        return $pdf->stream($filename);
    }
}
