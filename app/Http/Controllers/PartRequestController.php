<?php

namespace App\Http\Controllers;

use App\Models\PartRequest;
use Illuminate\Http\Request;

class PartRequestController extends Controller
{
    /**
     * Tampilkan daftar semua Part Requests untuk Planner/Warehouse.
     */
    public function index()
    {
        $partRequests = PartRequest::with('component')
            ->latest()
            ->get();

        return view('warehouse.index', compact('partRequests'));
    }

    /**
     * Update status Part Request (Pending → Available / Out of Stock).
     */
    public function updateStatus(Request $request, PartRequest $partRequest)
    {
        $request->validate([
            'status' => 'required|in:Available,Out of Stock',
        ]);

        $partRequest->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Status part "' . $partRequest->part_name . '" berhasil diperbarui menjadi: ' . $request->status);
    }
}
