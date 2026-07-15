<x-app-layout>

    {{-- Hero Section --}}
    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Dashboard</h1>
            <p>Selamat datang kembali, <strong style="color: var(--accent-gold);">{{ Auth::user()->name }}</strong>
                <span class="badge badge-gold" style="margin-left: 8px;">{{ Auth::user()->roles->pluck('name')->implode(', ') }}</span>
            </p>
        </div>
    </div>

    {{-- Executive Analytics --}}
    @role('SuperAdmin|Management|Supervisor|Planner/Warehouse')
    <div class="section">
        <div class="section-title fade-up">📊 Executive Analytics — Real-time Metrics</div>
        <div class="grid-4">
            <div class="glass-card metric-card metric-gold fade-up">
                <div class="metric-icon">🔧</div>
                <div class="metric-label">On Progress</div>
                <div class="metric-value">{{ $onProgress }}</div>
                <div class="metric-sub">komponen aktif</div>
            </div>
            <div class="glass-card metric-card metric-green fade-up">
                <div class="metric-icon">✅</div>
                <div class="metric-label">Ready for Use</div>
                <div class="metric-value">{{ $readyForUse }}</div>
                <div class="metric-sub">komponen selesai</div>
            </div>
            <div class="glass-card metric-card metric-cyan fade-up">
                <div class="metric-icon">⏱</div>
                <div class="metric-label">Avg Lead Time</div>
                <div class="metric-value">{{ $avgLeadTime }}<span style="font-size: 1rem; font-weight: 400;">h</span></div>
                <div class="metric-sub">jam rata-rata</div>
            </div>
            <div class="glass-card metric-card metric-red fade-up">
                <div class="metric-icon">📦</div>
                <div class="metric-label">Pending Parts</div>
                <div class="metric-value">{{ $pendingParts }}</div>
                <div class="metric-sub">permintaan gudang</div>
            </div>
        </div>
    </div>

    {{-- Stage Distribution --}}
    <div class="section">
        <div class="section-title fade-up">📈 Distribusi Komponen per Tahapan (On Progress)</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px;">
                @php
                    $stageLabels = [
                        1 => 'Receiving',
                        2 => 'DIS Assembling',
                        3 => 'Machining',
                        4 => 'Assembly',
                        5 => 'Test Perf.',
                        6 => 'Painting',
                        7 => 'RFU/Delivery',
                    ];
                @endphp
                @foreach($stageDistribution as $stage => $count)
                <div style="text-align: center;">
                    <div style="
                        padding: 16px 4px;
                        border-radius: 12px;
                        font-family: 'JetBrains Mono', monospace;
                        font-size: 1.4rem;
                        font-weight: 800;
                        {{ $count > 0
                            ? 'background: var(--accent-cyan-dim); color: var(--accent-cyan); box-shadow: 0 0 20px rgba(72, 202, 228, 0.1);'
                            : 'background: rgba(255,255,255,0.02); color: var(--text-muted);'
                        }}
                    ">{{ $count }}</div>
                    <p style="font-size: 0.6rem; color: var(--text-muted); margin-top: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">{{ $stageLabels[$stage] ?? 'Tahap '.$stage }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endrole

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

            @role('SuperAdmin')
            <a href="{{ route('admin.users.index') }}" class="glass-card module-card fade-up">
                <span class="module-icon">👤</span>
                <div class="module-arrow">→</div>
                <h3>Manajemen User</h3>
                <p>Tambah, lihat, dan hapus akun pengguna sistem. Khusus akses SuperAdmin / IT.</p>
            </a>
            @endrole
        </div>
    </div>

</x-app-layout>
