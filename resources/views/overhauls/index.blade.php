<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <h1>Daftar Komponen</h1>
                <p><span id="componentCount">{{ $components->count() }}</span> komponen terdaftar dalam sistem</p>
            </div>
            @ocmsRegister
            <a href="{{ route('components.create') }}" class="btn-primary">
                + Daftarkan Komponen
            </a>
            @endocmsRegister
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif

    {{-- Pencarian cepat --}}
    <div class="section fade-up" style="margin-bottom: 20px;">
        <input type="search" id="componentSearch" class="ocms-input" placeholder="🔍 Cari EGI, unit code, serial number, site, status..." style="max-width: 420px;">
    </div>

    <div class="glass-card fade-up table-scroll" style="padding: 0;">
        <table class="ocms-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>EGI</th>
                    <th>Unit Code</th>
                    <th>Comp Serial No.</th>
                    <th>Component Model</th>
                    <th>Site</th>
                    <th>Status OVH</th>
                    <th>Tahap</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($components as $index => $comp)
                    <tr data-comp-id="{{ $comp->comp_id }}">
                        <td>{{ $index + 1 }}</td>
                        <td class="mono" style="font-weight: 600;">
                            {{ $comp->egi ?? $comp->model_type }}
                            @php
                                $knownEgis = ['PC2000-8','PC1250-8','D375-6','D155-6','WA800-3','GD825A-2','HD785-7','HD465-7R','HD1500-7'];
                                $templatedCategories = ['Engine', 'TC/Transmission', 'Final Drive', 'Differential', 'PTO', 'Swing Machinery', 'Control Valve'];
                                $egiUpper = strtoupper(trim($comp->egi ?? ''));
                                $hasTemplate = in_array($egiUpper, array_map('strtoupper', $knownEgis));
                                $isTemplatedCategory = in_array($comp->major_category, $templatedCategories);
                            @endphp
                            @if($isTemplatedCategory)
                                @if($hasTemplate)
                                    <span title="Checksheet khusus tersedia" style="font-size: 0.55rem; background: rgba(52,211,153,0.15); color: #34D399; padding: 2px 6px; border-radius: 6px; margin-left: 4px; font-weight: 700;">CS ✓</span>
                                @else
                                    <span title="Menggunakan checksheet generik" style="font-size: 0.55rem; background: rgba(212,175,55,0.15); color: #D4AF37; padding: 2px 6px; border-radius: 6px; margin-left: 4px; font-weight: 700;">CS ⚠</span>
                                @endif
                            @endif
                        </td>
                        <td class="mono">{{ $comp->unit_code ?? '-' }}</td>
                        <td class="mono" style="font-weight: 600;">{{ $comp->serial_number }}</td>
                        <td><span class="badge badge-cyan">{{ $comp->major_category }}</span></td>
                        <td>{{ $comp->site_district ?? '-' }}</td>
                        <td>
                            @if($comp->status_ovh == 'SCHEDULE')
                                <span class="badge badge-green" style="font-size: 0.6rem;">SCHEDULE</span>
                            @elseif($comp->status_ovh == 'UNSCHEDULE')
                                <span class="badge badge-gold" style="font-size: 0.6rem;">UNSCHEDULE</span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td class="js-stage-cell">
                            <span class="badge badge-cyan">{{ $comp->current_stage }}/7</span>
                        </td>
                        <td class="js-status-cell">
                            @if($comp->status == 'On Progress')
                                <span class="badge badge-gold">🔧 On Progress</span>
                            @else
                                <span class="badge badge-green">✅ {{ $comp->status }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary btn-sm">
                                    Lihat →
                                </a>
                                @ocmsDeveloper
                                <a href="{{ route('components.edit', $comp->comp_id) }}" class="btn-secondary btn-sm" style="font-size: 0.7rem;" title="Edit komponen (Developer/SuperAdmin)">✏️</a>
                                <form action="{{ route('components.destroy', $comp->comp_id) }}" method="POST" style="margin: 0;"
                                      onsubmit="return confirm('Hapus komponen {{ $comp->serial_number }}?\n\nSELURUH data terkait (riwayat, checksheet, FR, part request, dokumen) ikut terhapus dan TIDAK bisa dikembalikan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm" style="font-size: 0.7rem;" title="Hapus komponen (Developer/SuperAdmin)">🗑</button>
                                </form>
                                @endocmsDeveloper
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 48px; color: var(--text-muted);">
                            Belum ada komponen terdaftar. Klik tombol "+ Daftarkan Komponen" untuk memulai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        // === Pencarian cepat (client-side) ===
        document.getElementById('componentSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('tr[data-comp-id]').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // === Realtime: update badge Tahap & Status tiap 10 detik ===
        ocmsPoll('{{ route('status.components') }}', 10000, function(data) {
            const rows = document.querySelectorAll('tr[data-comp-id]');

            // Ada komponen baru / terhapus → muat ulang daftar
            if (data.count !== rows.length) {
                location.reload();
                return;
            }

            data.components.forEach(comp => {
                const row = document.querySelector('tr[data-comp-id="' + comp.comp_id + '"]');
                if (!row) return;

                const stageCell = row.querySelector('.js-stage-cell');
                const newStage = '<span class="badge badge-cyan">' + comp.current_stage + '/7</span>';
                if (stageCell && stageCell.innerHTML.trim() !== newStage) stageCell.innerHTML = newStage;

                const statusCell = row.querySelector('.js-status-cell');
                const newStatus = comp.status === 'On Progress'
                    ? '<span class="badge badge-gold">🔧 On Progress</span>'
                    : '<span class="badge badge-green">✅ ' + comp.status + '</span>';
                if (statusCell && statusCell.innerHTML.trim() !== newStatus) statusCell.innerHTML = newStatus;
            });
        });
    </script>

</x-app-layout>