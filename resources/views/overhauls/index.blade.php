<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>Daftar Komponen</h1>
                <p>{{ $components->count() }} komponen terdaftar dalam sistem</p>
            </div>
            @role('SuperAdmin|Planner/Warehouse')
            <a href="{{ route('components.create') }}" class="btn-primary">
                + Daftarkan Komponen
            </a>
            @endrole
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
                    <th>Serial Number</th>
                    <th>Model Alat</th>
                    <th>Tahap</th>
                    <th>Status</th>
                    <th>Tanggal Masuk</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($components as $index => $comp)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="mono">{{ $comp->serial_number }}</td>
                    <td>{{ $comp->model_type }}</td>
                    <td>
                        <span class="badge badge-cyan">{{ $comp->current_stage }}/9</span>
                    </td>
                    <td>
                        @if($comp->status == 'On Progress')
                            <span class="badge badge-gold">🔧 On Progress</span>
                        @else
                            <span class="badge badge-green">✅ {{ $comp->status }}</span>
                        @endif
                    </td>
                    <td style="color: var(--text-muted);">{{ $comp->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary btn-sm">
                            Lihat →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted);">
                        Belum ada komponen terdaftar. Klik tombol "+ Daftarkan Komponen" untuk memulai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-app-layout>
