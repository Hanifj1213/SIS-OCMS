<x-app-layout>

    {{-- Hero Section --}}
    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Dashboard</h1>
            <div class="ocms-welcome-role">
                <p>Selamat datang kembali, <strong style="color: var(--accent-gold);">{{ Auth::user()->name }}</strong></p>
                <span class="badge badge-gold ocms-role-badge">{{ Auth::user()->roles->pluck('name')->implode(', ') }}</span>
            </div>
        </div>
    </div>

    {{-- Executive Analytics --}}
    @ocmsExecutive
    <div class="section">
        <div class="section-title fade-up">📊 Executive Analytics — Real-time Metrics</div>
        <div class="grid-4">
            <div class="glass-card metric-card metric-gold fade-up">
                <div class="metric-icon">🔧</div>
                <div class="metric-label">On Progress</div>
                <div class="metric-value" id="metricOnProgress">{{ $onProgress }}</div>
                <div class="metric-sub">komponen aktif</div>
            </div>
            <div class="glass-card metric-card metric-green fade-up">
                <div class="metric-icon">✅</div>
                <div class="metric-label">Ready for Use</div>
                <div class="metric-value" id="metricReadyForUse">{{ $readyForUse }}</div>
                <div class="metric-sub">komponen selesai</div>
            </div>
            <div class="glass-card metric-card metric-cyan fade-up">
                <div class="metric-icon">⏱</div>
                <div class="metric-label">Avg Lead Time</div>
                <div class="metric-value"><span id="metricAvgLeadTime">{{ $avgLeadTime }}</span><span style="font-size: 1rem; font-weight: 400;">h</span></div>
                <div class="metric-sub">jam rata-rata</div>
            </div>
            <div class="glass-card metric-card metric-red fade-up">
                <div class="metric-icon">📦</div>
                <div class="metric-label">Pending Parts</div>
                <div class="metric-value" id="metricPendingParts">{{ $pendingParts }}</div>
                <div class="metric-sub">permintaan gudang</div>
            </div>
        </div>
    </div>

    {{-- Stage Distribution --}}
    <div class="section">
        <div class="section-title fade-up">📈 Distribusi Komponen per Tahapan</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div class="stage-dist-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px;">
                @php
                    $stageLabels = [
                        1 => 'Receiving',
                        2 => 'DIS Assembling',
                        3 => 'Machining',
                        4 => 'Assembly',
                        5 => 'Test & Paint',
                        6 => 'Delivery',
                        7 => 'RFU',
                    ];
                @endphp
                @foreach($stageDistribution as $stage => $count)
                <div style="text-align: center;">
                    <div class="stage-dist-cell {{ $count > 0 ? 'has-count' : '' }}" data-stage="{{ $stage }}">{{ $count }}</div>
                    <p style="font-size: 0.6rem; color: var(--text-muted); margin-top: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">{{ $stageLabels[$stage] ?? 'Tahap '.$stage }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endocmsExecutive

    <style>
        .stage-dist-cell {
            padding: 16px 4px;
            border-radius: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem;
            font-weight: 800;
            background: rgba(var(--ink), 0.02);
            color: var(--text-muted);
            transition: all 0.4s;
        }
        .stage-dist-cell.has-count {
            background: var(--accent-cyan-dim);
            color: var(--accent-cyan);
            box-shadow: 0 0 20px rgba(72, 202, 228, 0.1);
        }
        @media (max-width: 768px) {
            .stage-dist-grid { grid-template-columns: repeat(4, 1fr) !important; }
        }
        @media (max-width: 420px) {
            .stage-dist-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>

    @ocmsExecutive
    <script>
        // Realtime: perbarui metrik dashboard tiap 10 detik tanpa refresh
        ocmsPoll('{{ route('status.dashboard') }}', 10000, function(data) {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el && el.textContent != String(val)) el.textContent = val;
            };
            set('metricOnProgress', data.onProgress);
            set('metricReadyForUse', data.readyForUse);
            set('metricAvgLeadTime', data.avgLeadTime);
            set('metricPendingParts', data.pendingParts);

            document.querySelectorAll('.stage-dist-cell').forEach(cell => {
                const count = data.stageDistribution[cell.dataset.stage] ?? 0;
                cell.textContent = count;
                cell.classList.toggle('has-count', count > 0);
            });
        });
    </script>
    @endocmsExecutive

    {{-- Quick Access Modules --}}
    <div class="section">
        <div class="section-title fade-up">🚀 Modul Sistem</div>
        <div class="grid-2">
            <a href="{{ route('components.index') }}" class="glass-card module-card fade-up">
                <span class="module-icon">📋</span>
                <div class="module-arrow">→</div>
                <h3>Komponen & Overhaul</h3>
                <p>Daftar komponen, pendaftaran baru, dan update tahapan proses overhaul alat berat.</p>
            </a>

            <a href="{{ route('part-requests.index') }}" class="glass-card module-card fade-up">
                <span class="module-icon">📦</span>
                <div class="module-arrow">→</div>
                <h3>Inventory & Gudang</h3>
                <p>Monitoring permintaan suku cadang (parts request) dan status ketersediaan stok.</p>
            </a>

            <a href="{{ route('scan') }}" class="glass-card module-card fade-up">
                <span class="module-icon">📱</span>
                <div class="module-arrow">→</div>
                <h3>Scan QR Code</h3>
                <p>Gunakan kamera smartphone atau tablet untuk memindai dan mengakses data komponen secara instan.</p>
            </a>

            @ocmsAdmin
            <a href="{{ route('admin.users.index') }}" class="glass-card module-card fade-up">
                <span class="module-icon">👤</span>
                <div class="module-arrow">→</div>
                <h3>Manajemen User</h3>
                <p>Tambah, lihat, dan hapus akun pengguna sistem. Khusus akses SuperAdmin / IT.</p>
            </a>
            @endocmsAdmin
        </div>
    </div>

</x-app-layout>
