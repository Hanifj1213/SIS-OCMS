<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Inventory & Gudang</h1>
            <p>{{ $partRequests->count() }} permintaan suku cadang dalam sistem</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif

    <div class="glass-card fade-up" style="padding: 0; overflow: hidden;">
        <table class="ocms-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Komponen (SN)</th>
                    <th>Nama Part</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Tanggal Request</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partRequests as $index => $pr)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <a href="{{ route('components.show', $pr->component->comp_id) }}" class="mono" style="color: var(--accent-cyan); text-decoration: none;">
                            {{ $pr->component->serial_number }}
                        </a>
                    </td>
                    <td style="font-weight: 600; color: var(--text-primary);">{{ $pr->part_name }}</td>
                    <td class="mono">{{ $pr->qty }}</td>
                    <td>
                        @if($pr->status == 'Pending')
                            <span class="badge badge-gold">⏳ Pending</span>
                        @elseif($pr->status == 'Available')
                            <span class="badge badge-green">✅ Available</span>
                        @else
                            <span class="badge badge-red">❌ Out of Stock</span>
                        @endif
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $pr->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($pr->status == 'Pending')
                            @role('SuperAdmin|Planner/Warehouse')
                            <div style="display: flex; gap: 6px;">
                                <form action="{{ route('part-requests.update', $pr->req_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="Available">
                                    <button type="submit" class="btn-primary btn-sm" style="font-size: 0.7rem;">✅ Tersedia</button>
                                </form>
                                <form action="{{ route('part-requests.update', $pr->req_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="Out of Stock">
                                    <button type="submit" class="btn-danger btn-sm" style="font-size: 0.7rem;">❌ Kosong</button>
                                </form>
                            </div>
                            @endrole
                        @else
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Diproses</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted);">
                        Belum ada permintaan suku cadang.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-app-layout>
