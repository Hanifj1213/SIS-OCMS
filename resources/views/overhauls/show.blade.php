<x-app-layout>

@php
    // Tahap yang SEDANG DILIHAT. Saat mekanik me-review tahap lama,
    // $reviewStage terisi dan panel per-tahap (FR/MOL) harus mengikutinya —
    // memakai current_stage saja membuat panel Stage 2 ikut muncul di Stage 1.
    $viewedStage = $reviewStage ?? $comp->current_stage;
@endphp

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>{{ $comp->serial_number }}</h1>
            <p>{{ $comp->egi ?? $comp->model_type }} — {{ $comp->major_category }}</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error fade-up">
            <strong>❌ Terjadi Kesalahan:</strong>
            <ul style="list-style: disc; padding-left: 20px; margin-top: 6px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Damage Core Info + QR Code --}}
    <div class="section" style="display: grid; grid-template-columns: 1fr 240px; gap: 20px;">
        <div class="glass-card fade-up">
            <div class="section-title" style="margin-bottom: 16px;">📋 Damage Core — Informasi Komponen</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
                {{-- Left Column --}}
                <div style="border-right: 1px solid rgba(var(--ink), 0.04);">
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">EGI</span>
                        <span class="mono" style="font-size: 0.85rem; font-weight: 600;">{{ $comp->egi ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Unit Code</span>
                        <span class="mono" style="font-size: 0.85rem;">{{ $comp->unit_code ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Unit Serial No.</span>
                        <span class="mono" style="font-size: 0.85rem;">{{ $comp->unit_serial_no ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Site / District</span>
                        <span style="font-size: 0.85rem;">{{ $comp->site_district ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">SMR</span>
                        <span class="mono" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-cyan);">{{ $comp->smr ? number_format($comp->smr) : '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Manifest</span>
                        <span style="font-size: 0.85rem;">{{ $comp->manifest ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 16px 10px 0;">
                        <span style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Way Bill</span>
                        <span style="font-size: 0.85rem;">{{ $comp->way_bill ?? '-' }}</span>
                    </div>
                </div>
                {{-- Right Column --}}
                <div style="padding-left: 16px;">
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Component Model</span>
                        <span style="font-size: 0.85rem;"><span class="badge badge-cyan">{{ $comp->major_category }}</span></span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Comp Serial No.</span>
                        <span class="mono" style="font-size: 0.85rem; font-weight: 600;">{{ $comp->serial_number }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">P/N Assy</span>
                        <span class="mono" style="font-size: 0.85rem;">{{ $comp->pn_assy ?? '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Status OVH</span>
                        <span>
                            @if($comp->status_ovh == 'SCHEDULE')
                                <span class="badge badge-green">📅 SCHEDULE</span>
                            @elseif($comp->status_ovh == 'UNSCHEDULE')
                                <span class="badge badge-gold">⚠️ UNSCHEDULE</span>
                            @else
                                <span style="font-size: 0.85rem;">-</span>
                            @endif
                        </span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Core Category</span>
                        <span style="font-size: 0.85rem;">
                            @if($comp->core_category == 'A')
                                <span class="badge badge-cyan" title="Kondisi komponen running, lengkap (Schedule Overhaul)">Cat. A</span>
                            @elseif($comp->core_category == 'B')
                                <span class="badge badge-gold" title="Kondisi Main Shaft Jammed, Gear broken (Unschedule Overhaul)">Cat. B</span>
                            @elseif($comp->core_category == 'C')
                                <span class="badge badge-red" title="Kondisi Housing Jebol / Broken (Unschedule Overhaul)">Cat. C</span>
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Life Time</span>
                        <span class="mono" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-cyan);">{{ $comp->life_time ? number_format($comp->life_time) : '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Date Received</span>
                        <span style="font-size: 0.85rem;">{{ $comp->date_defitted ? $comp->date_defitted->format('d M Y') : '-' }}</span>
                    </div>
                    <div style="display: flex; padding: 10px 0;">
                        <span style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Status Overhaul</span>
                        <span>
                            @if($comp->status == 'On Progress')
                                <span class="badge badge-gold">🔧 {{ $stageNames[$comp->current_stage] ?? 'Tahap '.$comp->current_stage }}</span>
                            @else
                                <span class="badge badge-green">✅ Ready for Use (RFU)</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card fade-up" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="section-title">QR Code</div>
            @if($comp->qr_code_path)
                <img src="{{ asset($comp->qr_code_path) }}" alt="QR" style="width: 140px; height: 140px; border-radius: 12px; border: 1px solid var(--glass-border); padding: 6px; background: white; margin-top: 8px;">
            @else
                <div style="width: 140px; height: 140px; border-radius: 12px; background: rgba(var(--ink), 0.03); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">N/A</div>
            @endif
        </div>
    </div>

    <style>
        .stage-review-link {
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        .stage-review-link:hover {
            transform: translateY(-2px);
            filter: brightness(1.15);
        }
        .stage-node.reviewing {
            outline: 2px solid var(--accent-gold);
            outline-offset: 2px;
        }

        /* === Waktu 3 Dimensi (Calendar / Work / Man Hour) === */
        .time3d-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .time3d-tile {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 14px;
            background: rgba(var(--ink), 0.02);
        }
        .time3d-tile::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--tile-accent, var(--accent-cyan));
            opacity: 0.7;
        }
        .time3d-tile[data-metric="calendar"] { --tile-accent: var(--accent-cyan); }
        .time3d-tile[data-metric="work"]     { --tile-accent: var(--accent-gold); }
        .time3d-tile[data-metric="man"]      { --tile-accent: var(--accent-green); }
        .t3-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .t3-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 4px;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }
        .time3d-grid[data-running="1"] .t3-value { color: var(--tile-accent, var(--accent-cyan)); }
        .t3-sub {
            font-size: 0.62rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .t3-pulse {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--accent-cyan);
            animation: t3blink 1.2s ease-in-out infinite;
            display: none;
        }
        .time3d-grid[data-running="1"] .t3-pulse { display: inline-block; }
        @keyframes t3blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }

        .crew-panel {
            margin-top: 12px;
            border: 1px dashed var(--glass-border-light);
            border-radius: 12px;
            padding: 12px 14px;
            background: rgba(var(--ink), 0.015);
        }
        .crew-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(var(--ink), 0.05);
            border: 1px solid var(--glass-border-light);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .crew-chip:hover {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.5);
            background: rgba(248, 113, 113, 0.08);
        }
    </style>

    {{-- Progress Bar --}}
    <div class="section">
        <div class="section-title fade-up">Progress Overhaul</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div class="stage-bar">
                @for($i = 1; $i <= 7; $i++)
                    @php
                        $stageCanReview = $i <= $comp->current_stage;
                        $stageNodeClass = $i < $comp->current_stage ? 'completed' : ($i == $comp->current_stage ? 'active' : 'pending');
                        if ($reviewStage === $i) $stageNodeClass .= ' reviewing';
                        $stageHref = $i < $comp->current_stage
                            ? route('components.show', ['component' => $comp->comp_id, 'review_stage' => $i]) . '#checksheet-review'
                            : route('components.show', ['component' => $comp->comp_id]) . '#checksheet-review';
                    @endphp
                    @if($stageCanReview)
                        <a href="{{ $stageHref }}"
                           class="stage-node stage-review-link {{ $stageNodeClass }}"
                           title="Lihat {{ $stageNames[$i] ?? 'Tahap '.$i }}">
                            <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                            <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                        </a>
                    @else
                        <div class="stage-node {{ $stageNodeClass }}" title="Tahap ini belum aktif">
                            <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                            <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                        </div>
                    @endif
                    @if($i < 7)
                        <div class="stage-connector {{ $i < $comp->current_stage ? 'done' : 'undone' }}"></div>
                    @endif
                @endfor
            </div>
        </div>
    </div>

    {{-- Timeline Log --}}
    <div class="section">
        <div class="section-title fade-up">Riwayat Pengerjaan</div>
        <div class="glass-card fade-up">
            @forelse($comp->overhaulLogs->sortBy('stage_number') as $log)
            <div style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid rgba(var(--ink), 0.03); {{ $loop->last ? 'border: none;' : '' }}">
                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
                    {{ $log->end_time ? 'background: var(--accent-green-dim); color: var(--accent-green);' : 'background: var(--accent-cyan-dim); color: var(--accent-cyan);' }}
                ">{{ $log->stage_number }}</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">{{ $stageNames[$log->stage_number] ?? 'Tahap '.$log->stage_number }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Petugas: {{ $log->mechanic ? $log->mechanic->name : 'Sistem' }}</div>
                        </div>
                        @if($log->end_time)
                            <span class="badge badge-green" style="font-size: 0.6rem;">Selesai</span>
                        @else
                            <span class="badge badge-cyan" style="font-size: 0.6rem;">● Aktif</span>
                        @endif
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; font-family: 'JetBrains Mono', monospace;">
                        {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('d/m/Y H:i') : '-' }}
                        → {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d/m/Y H:i') : 'Sekarang' }}
                    </div>
                    @if($log->notes)
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; font-style: italic;">"{{ $log->notes }}"</div>
                    @endif

                    {{-- Waktu 3 Dimensi: Calendar / Work / Man Hour --}}
                    @php $tm = $stageTimeMetrics[$log->stage_number] ?? null; @endphp
                    @if($tm)
                    <div class="time3d-grid"
                         data-stage="{{ $log->stage_number }}"
                         data-running="{{ $tm['running'] ? 1 : 0 }}"
                         data-active-crew="{{ $tm['active_crew'] }}">
                        <div class="time3d-tile" data-metric="calendar" data-seconds="{{ $tm['calendar_seconds'] }}">
                            <div class="t3-label">🗓 Calendar Hour <span class="t3-pulse"></span></div>
                            <div class="t3-value">{{ \App\Services\StageTimeService::formatHours($tm['calendar_seconds']) }}</div>
                            <div class="t3-sub">Waktu absolut 24/7</div>
                        </div>
                        <div class="time3d-tile" data-metric="work" data-seconds="{{ $tm['work_seconds'] }}">
                            <div class="t3-label">🔧 Work Hour <span class="t3-pulse"></span></div>
                            <div class="t3-value">{{ \App\Services\StageTimeService::formatHours($tm['work_seconds']) }}</div>
                            <div class="t3-sub">Jam operasional bengkel ({{ config('worktime.open_label') }})</div>
                        </div>
                        <div class="time3d-tile" data-metric="man" data-seconds="{{ $tm['man_seconds'] }}">
                            <div class="t3-label">👥 Man Hour <span class="t3-pulse"></span></div>
                            <div class="t3-value">{{ \App\Services\StageTimeService::formatHours($tm['man_seconds']) }}</div>
                            <div class="t3-sub">Crew aktif: <span class="t3-crew-count">{{ $tm['active_crew'] }}</span> mekanik · jeda istirahat otomatis</div>
                        </div>
                    </div>

                    {{-- Crew aktif: daftar chip nama. Tambah nama = multiplier Man Hour naik,
                         klik nama = konfirmasi lalu keluar. Tanpa start/stop — jam kerja &
                         istirahat dipotong otomatis dari config/worktime.php. --}}
                    @php
                        $isCurrentActiveStage = !$log->end_time && $log->stage_number == $comp->current_stage;
                        $canManageCrew = auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']);
                    @endphp
                    @if($isCurrentActiveStage && (count($tm['crew']) > 0 || $canManageCrew))
                    <div class="crew-panel" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
                            👥 Crew Aktif
                        </span>
                        @forelse($tm['crew'] as $crew)
                            @if($canManageCrew)
                                <form method="POST" action="{{ route('components.crew.remove', [$comp->comp_id, $crew['log_id']]) }}"
                                      onsubmit="return confirm('Keluarkan {{ $crew['name'] }} dari crew?\nMan Hour yang sudah berjalan tetap tersimpan.');"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="crew-chip" title="Sejak {{ $crew['since'] }} — klik untuk keluarkan">
                                        👤 {{ $crew['name'] }} <span style="opacity: 0.55;">✕</span>
                                    </button>
                                </form>
                            @else
                                <span class="crew-chip" style="cursor: default;" title="Sejak {{ $crew['since'] }}">👤 {{ $crew['name'] }}</span>
                            @endif
                        @empty
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Belum ada crew — Man Hour belum berjalan.</span>
                        @endforelse

                        @if($canManageCrew)
                            <form method="POST" action="{{ route('components.crew.add', $comp->comp_id) }}"
                                  style="display: flex; gap: 6px; margin-left: auto;">
                                @csrf
                                <input type="text" name="name" required maxlength="100" placeholder="Nama mekanik…"
                                       style="width: 160px; background: var(--select-option-bg); color: var(--text-primary); border: 1px solid var(--glass-border-light); border-radius: 999px; padding: 5px 12px; font-size: 0.72rem;">
                                <button type="submit" class="btn-primary" style="padding: 5px 14px; font-size: 0.72rem; border-radius: 999px;">＋ Tambah</button>
                            </form>
                        @endif
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @empty
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 24px;">Belum ada riwayat.</p>
            @endforelse
        </div>
    </div>

    {{-- Live timer Waktu 3 Dimensi: tick per detik di browser, resink ke server tiap 60 detik --}}
    <script>
    (function () {
        const offWindows = @json(config('worktime.off_windows', []));
        const breakWindows = @json(config('worktime.breaks', []));
        const metricsUrl = @json(route('components.timeMetrics', $comp->comp_id));

        function inWindows(d, windows) {
            const mins = d.getHours() * 60 + d.getMinutes();
            return windows.some(w => {
                const [sh, sm] = w.start.split(':').map(Number);
                const [eh, em] = (w.end === '24:00' ? [24, 0] : w.end.split(':').map(Number));
                return mins >= sh * 60 + sm && mins < eh * 60 + em;
            });
        }

        // Work Hour berjalan di jam buka; Man Hour ikut berhenti saat istirahat
        function isWorkshopOpen(d) { return !inWindows(d, offWindows); }
        function isCrewWorking(d) { return isWorkshopOpen(d) && !inWindows(d, breakWindows); }

        function fmt(sec) {
            sec = Math.max(0, Math.floor(sec));
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            return h + 'j ' + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 's';
        }

        function render() {
            document.querySelectorAll('.time3d-tile').forEach(tile => {
                tile.querySelector('.t3-value').textContent = fmt(parseFloat(tile.dataset.seconds || 0));
            });
        }

        setInterval(function () {
            const now = new Date();
            const open = isWorkshopOpen(now);
            const crewWorking = isCrewWorking(now);
            document.querySelectorAll('.time3d-grid[data-running="1"]').forEach(grid => {
                const activeCrew = parseInt(grid.dataset.activeCrew || '0', 10);
                grid.querySelectorAll('.time3d-tile').forEach(tile => {
                    let inc = 0;
                    if (tile.dataset.metric === 'calendar') inc = 1;
                    else if (tile.dataset.metric === 'work') inc = open ? 1 : 0;
                    else if (tile.dataset.metric === 'man') inc = crewWorking ? activeCrew : 0;
                    tile.dataset.seconds = parseFloat(tile.dataset.seconds || 0) + inc;
                });
            });
            render();
        }, 1000);
        render();

        // Resink dari server: koreksi drift + tangkap clock-in/out user lain
        setInterval(async function () {
            try {
                const res = await fetch(metricsUrl, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                Object.values(data.stages).forEach(st => {
                    const grid = document.querySelector('.time3d-grid[data-stage="' + st.stage + '"]');
                    if (!grid) return;
                    grid.dataset.running = st.running ? '1' : '0';
                    grid.dataset.activeCrew = st.active_crew;
                    grid.querySelector('[data-metric="calendar"]').dataset.seconds = st.calendar_seconds;
                    grid.querySelector('[data-metric="work"]').dataset.seconds = st.work_seconds;
                    grid.querySelector('[data-metric="man"]').dataset.seconds = st.man_seconds;
                    const crewCount = grid.querySelector('.t3-crew-count');
                    if (crewCount) crewCount.textContent = st.active_crew;
                });
                render();
            } catch (e) { /* offline sesaat: timer lokal tetap jalan */ }
        }, 60000);
    })();
    </script>

    {{-- Inspection Results --}}
    @if($comp->inspectionDetails->count() > 0)
    <div class="section">
        <div class="section-title fade-up">Hasil Inspeksi & Pengukuran</div>
        <div class="glass-card fade-up table-scroll" style="padding: 0;">
            <table class="ocms-table">
                <thead>
                    <tr>
                        <th>Nama Part</th>
                        <th>Nilai Aktual (mm)</th>
                        <th>Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comp->inspectionDetails as $insp)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $insp->part_name }}</td>
                        <td class="mono">{{ $insp->actual_value }}</td>
                        <td>
                            @if($insp->decision == 'Replace')
                                <span class="badge badge-red">Replace</span>
                            @elseif($insp->decision == 'Repair')
                                <span class="badge badge-gold">Repair</span>
                            @else
                                <span class="badge badge-green">Reused</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- FR & MOL Panel (scan + daftar) — hanya Stage 2 (Disassembly) & 3 (Machining).
         Stage 4+ memakai panel Assembly / Test Bench / Painting / Delivery sendiri. --}}
    @if(in_array($viewedStage, [2, 3], true))
    <div class="section" id="fr-panel">
        <div class="section-title fade-up">Dokumen FR & MOL</div>
        <div class="glass-card fade-up">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px;">
                @if($comp->major_category === 'Engine')
                    Scan spreadsheet <strong>Disassembly</strong> (SALVAGE → FR, REPLACE → Part Request/MOL).
                @elseif($comp->gsheet_url)
                    Scan spreadsheet <strong>Inspection &amp; Disassembly</strong> (U/R / SALVAGE → FR, R/N / REPLACE → Part Request/MOL).
                @else
                    Scan spreadsheet <strong>Inspection</strong> (U/R → FR, R/N → Part Request/MOL).
                @endif
                Form internal: Repair → FR, Replace → PR/MOL.
            </p>
            <p id="fr-scan-profile" style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 16px;"></p>

            @role('Mechanic|Supervisor|SuperAdmin')
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <button type="button" id="fr-scan-btn" class="btn-primary">🔍 Scan Spreadsheet (FR & MOL)</button>
                <a href="{{ route('components.fr.create', $comp->comp_id) }}" class="btn-secondary" style="text-decoration:none;">📝 Form FR Kosong (PLO/09/F-021)</a>
                <a href="{{ $comp->gsheet_sdr_url ?: 'https://docs.google.com/spreadsheets/d/1HvxiqXGEvH_nscYugPjOEfgIdq9Ps9nEyKqt_vNrd_8/edit?usp=sharing' }}" target="_blank" class="btn-secondary" style="text-decoration:none;">📊 SDR</a>
                <a href="https://llk-parts.ru/#" target="_blank" class="btn-secondary" style="text-decoration:none;">🔗 LLK Parts Catalog</a>
                <span id="fr-scan-status" style="font-size: 0.75rem; color: var(--text-muted); align-self: center;"></span>
            </div>
            @endrole

            {{-- Tab Toggle Buttons --}}
            <div style="display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid rgba(var(--ink), 0.06);">
                <button type="button" id="tab-fr-btn" class="btn-primary" style="border-radius: 8px 8px 0 0; padding: 8px 24px; font-size: 0.85rem;">
                    📋 Fabrication Request ({{ $comp->fabricationRequests->count() }})
                </button>
                <button type="button" id="tab-mol-btn" class="btn-secondary" style="border-radius: 8px 8px 0 0; padding: 8px 24px; font-size: 0.85rem; margin-left: 4px;">
                    📦 Material Order List ({{ $comp->partRequests->count() }})
                </button>
            </div>

            {{-- TAB: FR List --}}
            <div id="tab-fr-content" style="padding-top: 16px;">
                <div id="fr-list-wrap">
                    @if($comp->fabricationRequests->count() > 0)
                    <div class="table-scroll" style="padding: 0;">
                        <table class="ocms-table" id="fr-table">
                            <thead>
                                <tr>
                                    <th>No. FR</th>
                                    <th>Part</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comp->fabricationRequests as $fr)
                                <tr data-fr-id="{{ $fr->fr_id }}">
                                    <td class="mono" style="font-size: 0.75rem;">{{ $fr->fr_number }}</td>
                                    <td style="font-weight: 600;">
                                        {{ $fr->part_name }}
                                        @if($fr->section)
                                            <span style="font-size:0.65rem; font-weight:600; padding:1px 6px; border-radius:6px; background:rgba(96,165,250,0.15); color:#93c5fd;">{{ $fr->section }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fr->status === 'done')
                                            <span class="badge badge-green">Done</span>
                                        @elseif($fr->status === 'printed')
                                            <span class="badge badge-cyan">Printed</span>
                                        @else
                                            <span class="badge badge-gold">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <a href="{{ route('components.fr.edit', [$comp->comp_id, $fr->fr_id]) }}" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">✏️ Edit Form PLO/09/F-021</a>
                                            <a href="{{ route('components.fr.pdf', [$comp->comp_id, $fr->fr_id]) }}" target="_blank" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">🖨 PDF</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p id="fr-empty-msg" style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                        Belum ada Fabrication Request untuk komponen ini.
                    </p>
                    @endif
                </div>
            </div>

            {{-- TAB: MOL List --}}
            <div id="tab-mol-content" style="display: none; padding-top: 16px;">
                {{-- Upload Dokumen MOL --}}
                @role('Mechanic|Supervisor|SuperAdmin')
                <div style="margin-bottom: 16px; padding: 12px; border: 1px solid rgba(var(--ink), 0.08); border-radius: 10px; background: rgba(0,0,0,0.1);">
                    <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">📄 Dokumen MOL</div>
                    @if($comp->mol_document_path)
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <a href="{{ asset($comp->mol_document_path) }}" target="_blank" class="btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none;">👁 Lihat Dokumen MOL</a>
                            <form id="mol-upload-form" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
                                @csrf
                                <input type="file" name="mol_document" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.75rem; max-width: 220px;" id="mol-doc-input">
                                <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 0.75rem;">🔄 Upload Ulang</button>
                            </form>
                            <button type="button" id="mol-doc-delete-btn" class="btn-secondary" style="padding: 6px 14px; font-size: 0.75rem; color: #f87171;">🗑 Hapus</button>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">File: {{ basename($comp->mol_document_path) }}</div>
                    @else
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <form id="mol-upload-form" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
                                @csrf
                                <input type="file" name="mol_document" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.75rem; max-width: 220px;" id="mol-doc-input">
                                <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 0.75rem;">📤 Upload Dokumen MOL</button>
                            </form>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Format: PDF, JPG, PNG (maks. 10 MB)</div>
                    @endif
                    <span id="mol-upload-status" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;"></span>
                </div>
                @else
                {{-- Non-mechanic: hanya bisa lihat dokumen --}}
                @if($comp->mol_document_path)
                <div style="margin-bottom: 16px;">
                    <a href="{{ asset($comp->mol_document_path) }}" target="_blank" class="btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none;">👁 Lihat Dokumen MOL</a>
                    <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 8px;">{{ basename($comp->mol_document_path) }}</span>
                </div>
                @endif
                @endrole

                {{-- MOL Part Request List --}}
                @if($comp->partRequests->count() > 0)
                <div class="table-scroll" style="padding: 0;">
                    <table class="ocms-table">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Section</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comp->partRequests as $pr)
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);">{{ $pr->part_name }}</td>
                                <td>
                                    @if($pr->section)
                                        <span style="font-size:0.65rem; font-weight:600; padding:1px 6px; border-radius:6px; background:rgba(96,165,250,0.15); color:#93c5fd;">{{ $pr->section }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($pr->status == 'Pending')
                                        <span class="badge badge-gold">⏳ Pending</span>
                                    @elseif($pr->status == 'Available')
                                        <span class="badge badge-green">✅ Available</span>
                                    @else
                                        <span class="badge badge-red">❌ Out of Stock</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                    Belum ada Part Request / MOL untuk komponen ini.
                </p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Stage 3: Machining & Fabrication — semua output FR ditampilkan langsung --}}
    @if($viewedStage == 3)
    <div class="section" id="fr-output-panel">
        <div class="section-title fade-up">🛠 Machining & Fabrication — Output FR ({{ $comp->fabricationRequests->count() }})</div>
        @forelse($comp->fabricationRequests as $fr)
        <div class="glass-card fade-up" style="margin-bottom: 16px; padding: 0; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; padding: 14px 18px; border-bottom: 1px solid rgba(var(--ink), 0.06);">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="mono" style="font-size: 0.75rem; color: var(--accent-cyan);">{{ $fr->fr_number }}</span>
                    <strong style="font-size: 0.9rem;">{{ $fr->part_name }}</strong>
                    @if($fr->section)
                        <span style="font-size:0.65rem; font-weight:600; padding:1px 6px; border-radius:6px; background:rgba(96,165,250,0.15); color:#93c5fd;">{{ $fr->section }}</span>
                    @endif
                    <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $fr->workTypeLabel() }}</span>
                    @if($fr->status === 'done')
                        <span class="badge badge-green">Done</span>
                    @elseif($fr->status === 'printed')
                        <span class="badge badge-cyan">Printed</span>
                    @else
                        <span class="badge badge-gold">Draft</span>
                    @endif
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="{{ route('components.fr.edit', [$comp->comp_id, $fr->fr_id]) }}" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">✏️ Edit</a>
                    <a href="{{ route('components.fr.pdf', [$comp->comp_id, $fr->fr_id]) }}" target="_blank" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">🖨 Buka PDF</a>
                </div>
            </div>
            {{-- PDF di-lazy-load supaya halaman tidak merender puluhan PDF sekaligus --}}
            <iframe class="fr-pdf-embed" data-src="{{ route('components.fr.pdf', [$comp->comp_id, $fr->fr_id]) }}"
                title="FR {{ $fr->fr_number }}"
                style="display: block; width: 100%; height: 78vh; border: none; background: #fff;"></iframe>
        </div>
        @empty
        <div class="glass-card fade-up">
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 24px;">
                Belum ada Fabrication Request. Hasil scan spreadsheet Stage 2 (SALVAGE / U/R) akan muncul di sini sebagai dokumen FR PLO/09/F-021.
            </p>
        </div>
        @endforelse
    </div>
    <script>
        // Lazy-load PDF FR: src dipasang saat kartu mendekati viewport.
        document.addEventListener('DOMContentLoaded', function() {
            const frames = Array.from(document.querySelectorAll('iframe.fr-pdf-embed[data-src]'));
            if (frames.length === 0) return;

            function load(f) {
                const url = f.getAttribute('data-src');
                if (url && !f.getAttribute('src')) f.setAttribute('src', url);
                f.removeAttribute('data-src');
            }

            if (!('IntersectionObserver' in window)) { frames.forEach(load); return; }

            const obs = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) { load(e.target); obs.unobserve(e.target); }
                });
            }, { rootMargin: '400px 0px' });

            frames.forEach(function(f) { obs.observe(f); });
        });
    </script>
    @endif

    {{-- Stage 5 (Test Performance & Painting): dokumentasi foto hasil pengecatan --}}
    @if($viewedStage == 5)
    @php $paintingImages = array_values($comp->painting_images ?? []); @endphp
    <div class="section" id="painting-panel">
        <div class="section-title fade-up">🎨 Painting — Dokumentasi Foto ({{ count($paintingImages) }})</div>
        <div class="glass-card fade-up">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                Unggah foto komponen setelah selesai pengecatan sebagai bukti dokumentasi tahap Painting.
            </p>

            @role('Mechanic|Supervisor|SuperAdmin')
            <form action="{{ route('components.painting.upload', $comp->comp_id) }}" method="POST" enctype="multipart/form-data"
                  style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; padding: 12px; border: 1px dashed rgba(var(--ink), 0.15); border-radius: 10px;">
                @csrf
                <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple required style="font-size: 0.75rem; max-width: 320px;">
                <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 0.8rem;">📤 Upload Foto</button>
                <span style="font-size: 0.7rem; color: var(--text-muted);">JPG/PNG/WebP, maks. 10 MB per foto, hingga 12 foto sekali unggah.</span>
            </form>
            @endrole

            @if(count($paintingImages) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;">
                @foreach($paintingImages as $idx => $img)
                <div style="border: 1px solid rgba(var(--ink), 0.08); border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.15);">
                    <a href="{{ asset($img['path']) }}" target="_blank" title="Buka ukuran penuh">
                        <img src="{{ asset($img['path']) }}" alt="Foto painting {{ $idx + 1 }}"
                             style="display: block; width: 100%; height: 170px; object-fit: cover;">
                    </a>
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px;">
                        <span style="font-size: 0.65rem; color: var(--text-muted);">
                            {{ $img['uploaded_at'] ?? '' }}{{ !empty($img['uploaded_by']) ? ' · ' . $img['uploaded_by'] : '' }}
                        </span>
                        @role('Mechanic|Supervisor|SuperAdmin')
                        <form action="{{ route('components.painting.delete', $comp->comp_id) }}" method="POST" style="margin: 0;"
                              onsubmit="return confirm('Hapus foto ini?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="index" value="{{ $idx }}">
                            <button type="submit" class="btn-secondary" style="padding: 3px 8px; font-size: 0.65rem; color: #f87171;">🗑</button>
                        </form>
                        @endrole
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                Belum ada foto dokumentasi painting.
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Stage 7: RFU — halaman penutup, seluruh tahapan selesai --}}
    @if($viewedStage == 7)
    @php
        $firstLog = $comp->overhaulLogs->sortBy('start_time')->first();
        $lastLog = $comp->overhaulLogs->sortByDesc('end_time')->first();
        $rfuStart = $firstLog?->start_time ? \Carbon\Carbon::parse($firstLog->start_time) : null;
        $rfuEnd = $lastLog?->end_time ? \Carbon\Carbon::parse($lastLog->end_time) : null;
        $rfuDays = ($rfuStart && $rfuEnd) ? $rfuStart->diffInDays($rfuEnd) : null;
    @endphp
    <div class="section" id="rfu-panel">
        <div class="glass-card fade-up" style="text-align: center; padding: 56px 32px; border-color: rgba(52, 211, 153, 0.25);">
            <div style="font-size: 3.5rem; margin-bottom: 12px;">✅</div>
            <h2 style="font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; color: var(--accent-green); margin-bottom: 8px;">
                Ready for Use (RFU)
            </h2>
            <p style="font-size: 0.95rem; color: var(--text-secondary); max-width: 520px; margin: 0 auto 28px;">
                Seluruh tahapan overhaul komponen <strong style="color: var(--text-primary);">{{ $comp->major_category }} — {{ $comp->serial_number }}</strong>
                telah selesai. Komponen siap dikirim / digunakan kembali.
            </p>
            <div style="display: flex; justify-content: center; gap: 32px; flex-wrap: wrap; margin-bottom: 28px;">
                <div>
                    <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 4px;">Mulai</div>
                    <div class="mono" style="font-size: 0.9rem; font-weight: 600;">{{ $rfuStart ? $rfuStart->format('d M Y') : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 4px;">Selesai</div>
                    <div class="mono" style="font-size: 0.9rem; font-weight: 600;">{{ $rfuEnd ? $rfuEnd->format('d M Y') : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 4px;">Durasi</div>
                    <div class="mono" style="font-size: 0.9rem; font-weight: 600; color: var(--accent-cyan);">{{ $rfuDays !== null ? $rfuDays . ' hari' : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 4px;">Tahap Dilalui</div>
                    <div class="mono" style="font-size: 0.9rem; font-weight: 600;">7 / 7</div>
                </div>
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 20px;">
                Terima kasih kepada seluruh tim yang terlibat. Riwayat lengkap tiap tahap dapat dibuka lewat stage bar di atas.
            </p>
            <a href="{{ route('components.printPdf', $comp->comp_id) }}" target="_blank" class="btn-primary" style="text-decoration: none;">🖨 Cetak Berita Acara (PDF)</a>
        </div>
    </div>
    @endif

    {{-- Checksheet Section (Inline Interactive / Review) --}}
    @php
        $checksheetStage = $reviewStage ?? $comp->current_stage;
        $currentChecksheet = $comp->checksheets->where('stage_number', $checksheetStage)->first();
        $isReviewMode = $reviewStage !== null;
    @endphp
    @if($isReviewMode && !$currentChecksheet)
    <div class="section" id="checksheet-review">
        <div class="section-title fade-up">Tahap {{ $checksheetStage }} — {{ $stageNames[$checksheetStage] ?? '' }}</div>
        <div class="glass-card fade-up">
            <div class="section-title" style="margin-bottom:12px;">Riwayat Tahap</div>
            @php $reviewLogs = $comp->overhaulLogs->where('stage_number', $checksheetStage)->sortBy('start_time'); @endphp
            @forelse($reviewLogs as $log)
                <div style="padding:14px 0; border-bottom:1px solid rgba(var(--ink), 0.05);">
                    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                        <strong style="font-size:0.85rem;">{{ $stageNames[$checksheetStage] ?? 'Tahap '.$checksheetStage }}</strong>
                        <span class="badge {{ $log->end_time ? 'badge-green' : 'badge-cyan' }}">{{ $log->end_time ? 'Selesai' : 'Aktif' }}</span>
                    </div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;">
                        Petugas: {{ $log->mechanic ? $log->mechanic->name : 'Sistem' }} ·
                        {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('d/m/Y H:i') : '-' }}
                        → {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d/m/Y H:i') : 'Sekarang' }}
                    </div>
                    @if($log->notes)
                        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:5px; font-style:italic;">"{{ $log->notes }}"</div>
                    @endif
                </div>
            @empty
                <p style="color:var(--text-muted); font-size:0.8rem;">Belum ada log untuk tahap ini.</p>
            @endforelse
        </div>
    </div>
    @endif
    @php
        // Spreadsheet tahap 2: mainline + sub-assy (disassembly & measurement).
        // PC2000-8 lama tanpa salinan disassembly memakai sheet legacy.
        // Tahap 4 (Assembly) & 5 (Test Bench) memakai salinan GSheet sendiri.
        $toEmbed = fn ($url) => $url . (str_contains($url, '?') ? '&' : '?') . 'rm=minimal';

        $gsheetEmbedUrl = null;
        $gsheetMeasurementEmbedUrl = null;
        $gsheetSubassyDisassyEmbedUrl = null;
        $gsheetSubassyMeasureEmbedUrl = null;
        $gsheetAssemblyEmbedUrl = null;
        $gsheetTestbenchEmbedUrl = null;

        if ($checksheetStage == 4 && $comp->gsheet_assembly_url) {
            $gsheetAssemblyEmbedUrl = $toEmbed($comp->gsheet_assembly_url);
        }
        if ($checksheetStage == 5 && $comp->gsheet_testbench_url) {
            $gsheetTestbenchEmbedUrl = $toEmbed($comp->gsheet_testbench_url);
        }

        if ($checksheetStage == 2) {
            $rawGsheet = $comp->gsheet_url
                ?: (
                    $comp->major_category === 'Engine' && $comp->egi === 'PC2000-8'
                        ? 'https://docs.google.com/spreadsheets/d/1kIjBP4R4MWPkpFzXIU7Smcwnyy2DoR2Pzj2oggmn3tY/edit?usp=sharing'
                        : null
                );
            if ($rawGsheet) {
                $gsheetEmbedUrl = $toEmbed($rawGsheet);
            }
            if ($comp->gsheet_measurement_url) {
                $gsheetMeasurementEmbedUrl = $toEmbed($comp->gsheet_measurement_url);
            }
            if ($comp->gsheet_subassy_disassembly_url) {
                $gsheetSubassyDisassyEmbedUrl = $toEmbed($comp->gsheet_subassy_disassembly_url);
            }
            if ($comp->gsheet_subassy_measurement_url) {
                $gsheetSubassyMeasureEmbedUrl = $toEmbed($comp->gsheet_subassy_measurement_url);
            }
        }
        $hasDisassyPanel = $gsheetEmbedUrl || $gsheetSubassyDisassyEmbedUrl;
        $hasMeasurePanel = $gsheetMeasurementEmbedUrl || $gsheetSubassyMeasureEmbedUrl;
        $hasStageGsheetPanel = $gsheetAssemblyEmbedUrl || $gsheetTestbenchEmbedUrl;
    @endphp
    @if($hasDisassyPanel || $hasMeasurePanel || $hasStageGsheetPanel)
    <style>
        .cs-scope-toggle {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            border-radius: 10px;
            background: rgba(var(--ink), 0.04);
            border: 1px solid var(--glass-border-light);
            margin: 0 0 12px;
        }
        .cs-scope-toggle button {
            appearance: none;
            border: 0;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--text-secondary);
            background: transparent;
            transition: background .15s ease, color .15s ease;
        }
        .cs-scope-toggle button.active {
            background: var(--accent-gold-dim);
            color: var(--accent-gold);
        }
        .cs-scope-toggle button:hover:not(.active) {
            color: var(--text-primary);
            background: rgba(var(--ink), 0.04);
        }
    </style>
    {{--
        Google Sheets mode edit tidak punya opsi resmi untuk menyembunyikan
        header baris/kolom dan bar tab sheet, jadi kita "crop": iframe dibuat
        lebih besar dari kotaknya lalu digeser sehingga strip header kiri/atas
        dan bar tab bawah terpotong di luar area terlihat.
    --}}
    @if($hasDisassyPanel)
    <div class="section" id="checksheet-review"
         data-mainline-url="{{ $gsheetEmbedUrl }}"
         data-subassy-url="{{ $gsheetSubassyDisassyEmbedUrl }}">
        <div class="section-title fade-up">🔧 Disassembly — Checksheet</div>
        @if($gsheetEmbedUrl && $gsheetSubassyDisassyEmbedUrl)
        <div class="cs-scope-toggle fade-up" data-scope-for="disassy" role="tablist" aria-label="Disassembly scope">
            <button type="button" class="active" data-scope="mainline">Mainline</button>
            <button type="button" data-scope="subassy">Sub Assy</button>
        </div>
        @elseif($gsheetSubassyDisassyEmbedUrl && !$gsheetEmbedUrl)
        <div class="fade-up" style="margin-bottom:12px; color:var(--text-secondary); font-size:0.85rem;">Mode: Sub Assy</div>
        @endif
        @php
            // Engine mainline: kolom A/B kosong → crop kiri lebar + sembunyikan tab bawah.
            // Powertrain (Control Valve dkk.): konten mulai kolom A → crop kiri tipis
            // (hanya baris nomor) + tab sheet NO1/NO2/NO3 tetap terlihat.
            $isEngineDisassy = $comp->major_category === 'Engine';
            $disassyCropMainlineLeft = $isEngineDisassy ? 120 : 46;
            $disassyCropMainlineBottom = $isEngineDisassy ? 37 : 0;
            $cropLeft = $gsheetEmbedUrl ? $disassyCropMainlineLeft : 46;
            $cropTop = 25;
            $cropBottom = $gsheetEmbedUrl ? $disassyCropMainlineBottom : 0;
            $disassySrc = $gsheetEmbedUrl ?: $gsheetSubassyDisassyEmbedUrl;
        @endphp
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe" class="gsheet-embed"
                data-src="{{ $disassySrc }}"
                data-crop-mainline="{{ $disassyCropMainlineLeft }},25,{{ $disassyCropMainlineBottom }}"
                data-crop-subassy="46,25,0"
                style="position: absolute; top: -{{ $cropTop }}px; left: -{{ $cropLeft }}px; width: calc(100% + {{ $cropLeft }}px); height: calc(100% + {{ $cropTop + $cropBottom }}px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    @endif

    @if($hasMeasurePanel)
    {{--
        Measurement menggantikan Form Inspeksi Digital lama. Kontennya mulai
        dari kolom A, jadi crop kiri hanya selebar kolom nomor baris. Crop
        bawah 0 supaya bar tab sheet tetap terlihat.
    --}}
    <div class="section"
         data-mainline-url="{{ $gsheetMeasurementEmbedUrl }}"
         data-subassy-url="{{ $gsheetSubassyMeasureEmbedUrl }}">
        <div class="section-title fade-up">📐 Measurement & Inspection — Form Inspeksi</div>
        @if($gsheetMeasurementEmbedUrl && $gsheetSubassyMeasureEmbedUrl)
        <div class="cs-scope-toggle fade-up" data-scope-for="measure" role="tablist" aria-label="Measurement scope">
            <button type="button" class="active" data-scope="mainline">Mainline</button>
            <button type="button" data-scope="subassy">Sub Assy</button>
        </div>
        @elseif($gsheetSubassyMeasureEmbedUrl && !$gsheetMeasurementEmbedUrl)
        <div class="fade-up" style="margin-bottom:12px; color:var(--text-secondary); font-size:0.85rem;">Mode: Sub Assy</div>
        @endif
        @php
            $mCropLeft = 46;
            $mCropTop = 25;
            $measureSrc = $gsheetMeasurementEmbedUrl ?: $gsheetSubassyMeasureEmbedUrl;
        @endphp
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe-measure" class="gsheet-embed"
                data-src="{{ $measureSrc }}"
                data-crop-mainline="46,25,0"
                data-crop-subassy="46,25,0"
                style="position: absolute; top: -{{ $mCropTop }}px; left: -{{ $mCropLeft }}px; width: calc(100% + {{ $mCropLeft }}px); height: calc(100% + {{ $mCropTop }}px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    @endif

    @if($checksheetStage == 4)
    <div class="section" id="checksheet-assembly">
        <div class="section-title fade-up">🔩 Assembly — Checksheet</div>
        @if($gsheetAssemblyEmbedUrl)
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe-assembly" class="gsheet-embed"
                data-src="{{ $gsheetAssemblyEmbedUrl }}"
                style="position: absolute; top: -25px; left: -46px; width: calc(100% + 46px); height: calc(100% + 25px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
        @else
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            @if(!empty($assemblyTemplateAvailable))
                <p style="font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Salinan Assembly sedang disiapkan</p>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">
                    Template sudah ada di Google Sheets. Duplikasi berjalan di latar belakang
                    (butuh <code>php artisan queue:work</code>). Refresh halaman dalam 1–2 menit.
                </p>
            @else
                <p style="font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Belum ada template Assembly</p>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">
                    EGI <strong>{{ $comp->egi }}</strong> / {{ $comp->major_category }} belum punya
                    template Assembly di config (file lokal .doc atau belum diupload).
                </p>
            @endif
        </div>
        @endif
    </div>
    @endif

    @if($checksheetStage == 5)
    <div class="section" id="checksheet-testbench">
        <div class="section-title fade-up">🧪 Test Bench — Checksheet</div>
        @if($gsheetTestbenchEmbedUrl)
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe-testbench" class="gsheet-embed"
                data-src="{{ $gsheetTestbenchEmbedUrl }}"
                style="position: absolute; top: -25px; left: -46px; width: calc(100% + 46px); height: calc(100% + 25px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
        @else
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            @if(!empty($testbenchTemplateAvailable))
                <p style="font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Salinan Test Bench sedang disiapkan</p>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">
                    Template sudah ada di Google Sheets. Duplikasi berjalan di latar belakang
                    (butuh <code>php artisan queue:work</code>). Refresh halaman dalam 1–2 menit.
                </p>
            @else
                <p style="font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Belum ada template Test Bench</p>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">
                    EGI <strong>{{ $comp->egi }}</strong> / {{ $comp->major_category }} belum punya
                    template Test Bench di config (contoh: Engine GD825A memang tidak ada file-nya).
                </p>
            @endif
        </div>
        @endif
    </div>
    @endif

        <script>
        // Lazy-load iframe GSheet: src baru dipasang saat panel mendekati
        // viewport, supaya buka halaman tidak langsung memuat aplikasi
        // Google Sheets penuh (berat di tablet/PC lama).
        document.addEventListener('DOMContentLoaded', function() {
            const lazyIframes = Array.from(document.querySelectorAll('iframe.gsheet-embed[data-src]'));
            if (lazyIframes.length === 0) return;

            function loadIframe(iframe) {
                const url = iframe.getAttribute('data-src');
                if (url && !iframe.getAttribute('src')) {
                    iframe.setAttribute('src', url);
                }
                iframe.removeAttribute('data-src');
            }

            if (!('IntersectionObserver' in window)) {
                lazyIframes.forEach(loadIframe);
                return;
            }

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        loadIframe(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '300px 0px' }); // mulai load sedikit sebelum terlihat

            lazyIframes.forEach(function(iframe) { observer.observe(iframe); });
        });

        // Toggle Mainline | Sub Assy: ganti src iframe + crop.
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cs-scope-toggle').forEach(function(toggle) {
                const section = toggle.closest('.section');
                const iframe = section ? section.querySelector('iframe.gsheet-embed') : null;
                if (!section || !iframe) return;

                toggle.querySelectorAll('button[data-scope]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const scope = btn.getAttribute('data-scope');
                        const url = section.getAttribute('data-' + scope + '-url');
                        if (!url) return;

                        toggle.querySelectorAll('button').forEach(function(b) { b.classList.remove('active'); });
                        btn.classList.add('active');

                        // Kalau iframe belum sempat lazy-load, cukup ganti target-nya
                        if (iframe.hasAttribute('data-src')) {
                            iframe.setAttribute('data-src', url);
                        } else if (iframe.getAttribute('src') !== url) {
                            iframe.setAttribute('src', url);
                        }

                        const cropKey = scope === 'subassy' ? 'data-crop-subassy' : 'data-crop-mainline';
                        const parts = (iframe.getAttribute(cropKey) || '46,25,0').split(',').map(Number);
                        const left = parts[0] || 0, top = parts[1] || 0, bottom = parts[2] || 0;
                        iframe.style.top = '-' + top + 'px';
                        iframe.style.left = '-' + left + 'px';
                        iframe.style.width = 'calc(100% + ' + left + 'px)';
                        iframe.style.height = 'calc(100% + ' + (top + bottom) + 'px)';
                    });
                });
            });
        });

        // Mencegah bug auto-scroll ke atas saat mengedit sel di iframe Google Sheets.
        //
        // Strategi: BUKAN membalas scroll (itu bikin halaman "kedutan" karena
        // tarik-menarik dengan Sheets), tapi mengunci body sepenuhnya
        // (position: fixed) begitu fokus masuk ke iframe. Selama terkunci,
        // browser tidak PUNYA scroll untuk digeser, jadi Sheets tidak bisa
        // menarik halaman ke atas sama sekali. Interaksi apa pun di luar
        // iframe langsung membuka kunci dan mengembalikan posisi semula.
        document.addEventListener('DOMContentLoaded', function() {
            const iframes = Array.from(document.querySelectorAll('.gsheet-embed'));
            if (iframes.length === 0) return;

            const isEmbedFocused = () => iframes.includes(document.activeElement);

            // Riwayat posisi scroll: dipakai untuk memulihkan posisi kalau
            // jump sempat terjadi SEBELUM event blur diproses browser.
            let history = [{ t: performance.now(), x: window.scrollX, y: window.scrollY }];
            const lock = { active: false, x: 0, y: 0 };

            // Waktu input user terakhir — scroll yang terjadi TANPA input user
            // (klik/wheel/sentuh/keyboard) dalam 250ms terakhir dipastikan
            // programmatic, alias scroll-jack dari Google Sheets.
            let lastInputT = 0;
            ['wheel', 'touchstart', 'touchmove', 'mousedown', 'pointerdown', 'keydown'].forEach(function(evt) {
                document.addEventListener(evt, function() { lastInputT = performance.now(); }, { passive: true, capture: true });
            });

            // Posisi kursor terakhir — dipakai untuk membedakan klik beneran
            // ke iframe vs Google Sheets MENCURI fokus sendiri saat selesai
            // load (terjadi tiap ganti Mainline/Sub Assy karena iframe reload).
            let lastMouse = null;
            document.addEventListener('mousemove', function(e) {
                lastMouse = { x: e.clientX, y: e.clientY };
            }, { passive: true, capture: true });
            document.addEventListener('mousedown', function(e) {
                lastMouse = { x: e.clientX, y: e.clientY };
            }, { passive: true, capture: true });

            function pointerOverElement(el) {
                if (!lastMouse) return false;
                const r = el.getBoundingClientRect();
                const pad = 40; // toleransi: mousemove terakhir bisa berhenti tepat di tepi iframe
                return lastMouse.x >= r.left - pad && lastMouse.x <= r.right + pad
                    && lastMouse.y >= r.top - pad && lastMouse.y <= r.bottom + pad;
            }

            function recordPos() {
                if (lock.active) return;
                const now = performance.now();
                history.push({ t: now, x: window.scrollX, y: window.scrollY });
                while (history.length > 1 && now - history[0].t > 800) history.shift();
            }

            // Posisi tercatat terakhir yang umurnya >= msAgo (sebelum jump)
            function stablePosBefore(msAgo) {
                const cutoff = performance.now() - msAgo;
                for (let i = history.length - 1; i >= 0; i--) {
                    if (history[i].t <= cutoff) return history[i];
                }
                return history[0];
            }

            function lockScroll() {
                if (lock.active) return;
                const p = stablePosBefore(150);
                lock.active = true;
                lock.x = p.x;
                lock.y = p.y;

                // Kompensasi lebar scrollbar supaya layout tidak bergeser
                const sw = window.innerWidth - document.documentElement.clientWidth;
                const b = document.body;
                b.style.position = 'fixed';
                b.style.top = (-p.y) + 'px';
                b.style.left = (-p.x) + 'px';
                b.style.right = '0';
                b.style.width = '100%';
                b.style.overflow = 'hidden';
                if (sw > 0) b.style.paddingRight = sw + 'px';
            }

            function unlockScroll() {
                if (!lock.active) return;
                lock.active = false;
                const b = document.body;
                b.style.position = '';
                b.style.top = '';
                b.style.left = '';
                b.style.right = '';
                b.style.width = '';
                b.style.overflow = '';
                b.style.paddingRight = '';
                window.scrollTo({ left: lock.x, top: lock.y, behavior: 'instant' });
            }

            window.addEventListener('scroll', function() {
                if (lock.active) return;

                // Deteksi scroll-jack: lompatan besar tanpa input user baru-baru
                // ini (misal klik tombol menu/+ di bar tab Sheets memicu
                // scrollIntoView sebelum event blur sempat mengunci halaman).
                const last = history[history.length - 1];
                const jumped = Math.abs(window.scrollY - last.y) > 100
                    && performance.now() - lastInputT > 250;
                if (jumped) {
                    window.scrollTo({ left: last.x, top: last.y, behavior: 'instant' });
                    return; // posisi hasil jump jangan direkam
                }

                recordPos();
            }, { passive: true });

            // Fokus masuk ke iframe:
            // - Kalau kursor memang di area iframe itu (user beneran klik)
            //   → kunci halaman di posisi sebelum jump.
            // - Kalau kursor TIDAK di area iframe → fokus dicuri oleh Sheets
            //   saat selesai load → rebut balik fokus + pulihkan scroll.
            window.addEventListener('blur', function() {
                setTimeout(function() {
                    if (!isEmbedFocused()) return;
                    const iframe = document.activeElement;

                    if (lastMouse && !pointerOverElement(iframe)) {
                        iframe.blur();
                        window.focus();
                        const p = stablePosBefore(150);
                        window.scrollTo({ left: p.x, top: p.y, behavior: 'instant' });
                        return;
                    }

                    lockScroll();
                }, 0);
            });

            // Fokus kembali ke halaman → buka kunci
            window.addEventListener('focus', unlockScroll);

            // Interaksi di luar iframe → lepas fokus iframe + buka kunci.
            // Event ini tidak terpicu saat user berinteraksi DI DALAM iframe.
            function releaseIframeFocus() {
                if (isEmbedFocused()) {
                    document.activeElement.blur();
                    window.focus();
                }
                unlockScroll();
            }
            ['wheel', 'touchstart', 'mousedown'].forEach(function(evt) {
                document.addEventListener(evt, releaseIframeFocus, { passive: true });
            });
        });
        </script>
    @endif

    @if($currentChecksheet && !$hasDisassyPanel && !$hasStageGsheetPanel)
    <div class="section" id="checksheet-review">
        <div class="section-title fade-up">Checksheet — {{ $stageNames[$checksheetStage] ?? '' }}</div>
        <div class="glass-card fade-up" id="csContainer">

            {{-- Header: Progress + View Toggle --}}
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 4px;">
                        📋 {{ $comp->major_category }} — {{ $stageNames[$checksheetStage] ?? 'Checksheet' }}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 8px;" id="csProgressText">
                        {{ count($currentChecksheet->answers ?? []) }} dari {{ count($currentChecksheet->items) }} item diperiksa
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(var(--ink), 0.06); border-radius: 3px; overflow: hidden;">
                        <div id="csProgressBar" style="height: 100%; width: {{ $currentChecksheet->progress }}%; background: linear-gradient(90deg, var(--accent-cyan), var(--accent-green)); border-radius: 3px; transition: width 0.4s;"></div>
                    </div>
                </div>
                <div style="display: flex; gap: 6px; flex-shrink: 0;">
                    <button onclick="csSetView('slide')" id="csViewSlide" class="cs-view-btn cs-view-active" title="Slide View">🎯 Slide</button>
                    <button onclick="csSetView('list')" id="csViewList" class="cs-view-btn" title="Daftar Item">📑 Daftar</button>
                </div>
            </div>

            {{-- ===== SLIDE VIEW ===== --}}
            <div id="csSlideView">
                <div id="csSlideContent" style="min-height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; transition: all 0.3s ease;">
                    <!-- Filled by JS -->
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; gap: 8px; flex-wrap: wrap;">
                    <button onclick="csNav(-1)" id="csBtnPrev" class="cs-nav-btn" disabled>← Prev</button>
                    <button onclick="csNav(1)" id="csBtnNext" class="cs-nav-btn">Next →</button>
                </div>
            </div>

            {{-- ===== LIST VIEW ===== --}}
            <div id="csListView" style="display: none;">
                {{-- Group Filters --}}
                <div id="csFilterButtons" style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;"></div>
                {{-- Item List --}}
                <div id="csItemList" style="max-height: 500px; overflow-y: auto; border-radius: 12px;">
                    <!-- Filled by JS -->
                </div>
                @if(!$isReviewMode && auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']))
                <div style="margin-top: 12px; text-align: center;">
                    <button onclick="csOpenAddModal()" class="cs-add-btn" style="width: 100%;">+ Tambah Item Kustom</button>
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Add Item Modal --}}
    <div id="csAddModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); z-index:1000; align-items:center; justify-content:center; padding:24px;">
        <div style="background:linear-gradient(170deg,#0f3d36,var(--bg-secondary)); border:1px solid var(--glass-border-light); border-radius:20px; padding:32px; width:100%; max-width:420px;">
            <div class="section-title" style="color:var(--accent-gold); margin-bottom:20px;">+ Tambah Item Checksheet</div>
            <label style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px;">Nama Item</label>
            <input type="text" id="csNewLabel" class="ocms-input" placeholder="Contoh: Bracket Custom XYZ" style="margin-bottom:16px;">
            <label style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px;">Grup</label>
            <select id="csNewGroup" class="ocms-select" style="margin-bottom:20px;">
                <option value="Custom Items">Custom Items</option>
            </select>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="csCloseAddModal()" class="btn-secondary" style="padding:10px 20px;">Batal</button>
                <button onclick="csSubmitAdd()" class="btn-primary" style="padding:10px 20px;">Tambahkan</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="csToast" style="position:fixed; bottom:80px; left:50%; transform:translateX(-50%) translateY(20px); background:rgba(52,211,153,0.15); border:1px solid rgba(52,211,153,0.3); color:var(--accent-green); padding:10px 20px; border-radius:10px; font-size:0.8rem; font-weight:600; opacity:0; transition:all 0.3s; z-index:200; pointer-events:none;"></div>

    {{-- Image Lightbox --}}
    <div id="csLightbox" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); backdrop-filter:blur(12px); z-index:1100; align-items:center; justify-content:center; padding:24px; cursor:zoom-out;" onclick="csCloseLightbox()">
        <button onclick="csCloseLightbox()" style="position:fixed; top:20px; right:24px; color:white; font-size:2rem; background:rgba(var(--ink), 0.1); border:none; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:1101;">×</button>
        <img id="csLightboxImg" src="" alt="" style="max-width:95%; max-height:90vh; object-fit:contain; border-radius:8px; box-shadow:0 16px 64px rgba(0,0,0,0.5);">
        <div id="csLightboxLabel" style="position:fixed; bottom:24px; left:50%; transform:translateX(-50%); color:var(--accent-gold); font-size:0.85rem; font-weight:700; background:rgba(0,0,0,0.6); padding:8px 20px; border-radius:8px;"></div>
    </div>

    <style>
        .cs-view-btn {
            padding: 8px 14px; border-radius: 10px; border: 1px solid var(--glass-border);
            background: rgba(var(--ink), 0.03); color: var(--text-muted); font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-view-btn:hover { background: rgba(var(--ink), 0.06); color: var(--text-secondary); }
        .cs-view-active { background: var(--accent-cyan-dim) !important; color: var(--accent-cyan) !important; border-color: rgba(72,202,228,0.3) !important; }

        .cs-nav-btn {
            padding: 10px 20px; border-radius: 10px; border: 1px solid var(--glass-border);
            background: rgba(var(--ink), 0.04); color: var(--text-secondary); font-family: 'Inter', sans-serif;
            font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-nav-btn:hover { background: rgba(var(--ink), 0.08); color: var(--text-primary); }
        .cs-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        .cs-add-btn {
            padding: 9px 18px; border-radius: 10px; border: 1px dashed rgba(212,175,55,0.3);
            background: transparent; color: var(--accent-gold); font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-add-btn:hover { background: var(--accent-gold-dim); border-style: solid; }

        .cs-del-nav-btn {
            padding: 9px 18px; border-radius: 10px; border: 1px dashed rgba(248,113,113,0.35);
            background: transparent; color: #F87171; font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-del-nav-btn:hover { background: rgba(248,113,113,0.1); border-style: solid; }

        .cs-filter-btn {
            padding: 6px 14px; border-radius: 8px; border: 1px solid var(--glass-border);
            background: rgba(var(--ink), 0.03); color: var(--text-muted); font-family: 'Inter', sans-serif;
            font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-filter-btn:hover { color: var(--text-secondary); background: rgba(var(--ink), 0.06); }
        .cs-filter-active { background: var(--accent-gold-dim) !important; color: var(--accent-gold) !important; border-color: rgba(212,175,55,0.3) !important; }

        .cs-answer-btn {
            padding: 16px 22px; border-radius: 14px; border: 2px solid transparent;
            cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            flex: 1; min-width: 100px; max-width: 160px; -webkit-tap-highlight-color: transparent;
        }
        .cs-answer-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.35); }
        .cs-answer-btn:active { transform: scale(0.95); }
        .cs-answer-btn.good {
            background: rgba(52,211,153,0.12); border-color: rgba(52,211,153,0.4); color: #34D399;
        }
        .cs-answer-btn.good:hover, .cs-answer-btn.good.selected {
            background: rgba(52,211,153,0.25); border-color: #34D399; color: #34D399;
            box-shadow: 0 8px 28px rgba(52,211,153,0.2);
        }
        .cs-answer-btn.bad {
            background: rgba(248,113,113,0.12); border-color: rgba(248,113,113,0.4); color: #F87171;
        }
        .cs-answer-btn.bad:hover, .cs-answer-btn.bad.selected {
            background: rgba(248,113,113,0.25); border-color: #F87171; color: #F87171;
            box-shadow: 0 8px 28px rgba(248,113,113,0.2);
        }
        .cs-answer-btn.none {
            background: rgba(var(--ink), 0.06); border-color: rgba(var(--ink), 0.15); color: rgba(var(--ink), 0.5);
        }
        .cs-answer-btn.none:hover, .cs-answer-btn.none.selected {
            background: rgba(var(--ink), 0.12); border-color: rgba(var(--ink), 0.3); color: rgba(var(--ink), 0.7);
            box-shadow: 0 8px 28px rgba(var(--ink), 0.05);
        }

        .cs-list-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            border-bottom: 1px solid rgba(var(--ink), 0.03); cursor: pointer; transition: background 0.15s;
        }
        .cs-list-item:hover { background: rgba(var(--ink), 0.04); }
        .cs-list-item:last-child { border-bottom: none; }
        .cs-list-num {
            font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: 700;
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center;
            justify-content: center; flex-shrink: 0;
        }
        .cs-list-status {
            font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0;
        }
        .cs-del-btn {
            padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(248,113,113,0.15);
            background: rgba(248,113,113,0.06); color: var(--accent-red); font-size: 0.65rem;
            cursor: pointer; transition: all 0.2s; flex-shrink: 0; font-family: 'Inter', sans-serif;
        }
        .cs-del-btn:hover { background: rgba(248,113,113,0.15); border-color: rgba(248,113,113,0.3); }

        .cs-group-header {
            font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--accent-gold); padding: 10px 16px; background: rgba(212,175,55,0.05);
            border-bottom: 1px solid rgba(212,175,55,0.1);
        }

        @media (max-width: 480px) {
            .cs-answer-btn { max-width: 100%; flex-direction: row; padding: 12px 16px; }
        }
    </style>

    <script>
    (function() {
        const CSRF = '{{ csrf_token() }}';
        const COMP_ID = {{ $comp->comp_id }};
        const STAGE = {{ $checksheetStage }};
        const CAN_INTERACT = @json(!$isReviewMode && auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']));
        @php
            $csEgiSlug = strtolower(trim((string) ($comp->egi ?? '')));
            $csRefPath = public_path('images/inspection/'.$csEgiSlug.'/control-valve.png');
            $csImgVer = is_file($csRefPath) ? filemtime($csRefPath) : time();
        @endphp
        const CS_IMG_VER = '{{ $csImgVer }}';

        let items = @json($currentChecksheet->items);
        let answers = @json($currentChecksheet->answers ?? (object)[]);
        let currentIndex = 0;
        let currentView = 'slide';
        let currentFilter = 'all';

        // Find first unanswered
        for (let i = 0; i < items.length; i++) {
            if (!answers[items[i].id]) { currentIndex = i; break; }
        }

        // ===== VIEW TOGGLE =====
        function csGetStageTwoReferenceImages(source) {
            if (!source) return [];

            const root = '/images/inspection/d375-6/stage2/';
            const mainline = source.match(/^D375-6 EG MAINLINE\.pdf p\.(\d+)(?:-(\d+))?$/);
            if (mainline) {
                const firstPage = Number(mainline[1]);
                const lastPage = Number(mainline[2] || mainline[1]);
                const images = [];

                for (let page = firstPage; page <= lastPage; page++) {
                    images.push({
                        src: root + 'mainline-p' + String(page).padStart(2, '0') + '.jpg',
                        label: 'D375-6 EG MAINLINE - halaman ' + page,
                    });
                }

                return images;
            }

            if (source === 'D375-6 EG SUBASSY.pdf p.2' || source === 'D375-6 EG SUBASSY.pdf p.5') {
                const page = source.endsWith('p.2') ? '02' : '05';
                return [{
                    src: root + 'subassy-p' + page + '.jpg',
                    label: 'D375-6 EG SUBASSY - halaman ' + Number(page),
                }];
            }

            if (source === 'piston 170.pdf p.1 / PISTON CHECKSHEET2.pdf p.1') {
                return [
                    { src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' },
                    { src: root + 'piston-checksheet-p01.jpg', label: 'Piston Measuring Check Sheet - halaman 1' },
                ];
            }

            if (source === 'piston 170.pdf p.1') {
                return [{ src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' }];
            }

            if (source === 'PISTON CHECKSHEET2.pdf p.1' || source === 'PISTON CHECKSHEET2.pdf p.2') {
                const page = source.endsWith('p.1') ? '01' : '02';
                return [{
                    src: root + 'piston-checksheet-p' + page + '.jpg',
                    label: 'Piston Measuring Check Sheet - halaman ' + Number(page),
                }];
            }

            return [];
        }

        // Group-based reference images: Stage 2 uses the original full SOP
        // page(s) registered in item.source. Receiving keeps the existing
        // view image mapping for each EGI.
        function csGetRefImage(item) {
            if (!item.group || item.custom) return null;

            const stageTwoImages = csGetStageTwoReferenceImages(item.source);
            if (stageTwoImages.length > 0) {
                return {
                    images: stageTwoImages,
                    label: item.group,
                    tall: false,
                };
            }

            const knownEgis = ['d375-6','hd785-7','d155-6','wa800-3','gd825a-2','hd465-7r','pc1250-8','pc2000-8','hd1500-7'];
            let egi = "{{ strtolower(trim($comp->egi ?? 'd375-6')) }}";
            if (!knownEgis.includes(egi)) egi = 'd375-6';

            const majorCategory = "{{ $comp->major_category }}";
            const slug = majorCategory === 'Engine'
                ? item.group.toLowerCase().replace(/ /g, '-')
                : majorCategory.toLowerCase().replace(/\//g, '-').replace(/ /g, '-');

            const src = '/images/inspection/' + egi + '/' + slug + '.png'
                + (majorCategory !== 'Engine' ? ('?v=' + CS_IMG_VER) : '');

            return {
                images: [{
                    src: src,
                    label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                }],
                label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                // Powertrain receiving sheet = full A3 page → butuh preview lebih besar
                tall: majorCategory !== 'Engine',
            };
        }

        function csGetGroups() {
            return [...new Set(items.map(item => item.group || 'Lainnya'))];
        }

        function csRenderGroupControls() {
            const filterContainer = document.getElementById('csFilterButtons');
            if (filterContainer) {
                filterContainer.replaceChildren();

                const addFilterButton = (filter, label) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'cs-filter-btn' + (currentFilter === filter ? ' cs-filter-active' : '');
                    button.dataset.filter = filter;
                    button.textContent = label;
                    button.addEventListener('click', () => window.csFilter(filter));
                    filterContainer.appendChild(button);
                };

                addFilterButton('all', 'Semua');
                csGetGroups().forEach(group => addFilterButton(group, group));
            }

            const groupSelect = document.getElementById('csNewGroup');
            if (groupSelect) {
                const selected = groupSelect.value || 'Custom Items';
                groupSelect.replaceChildren();

                ['Custom Items', ...csGetGroups().filter(group => group !== 'Custom Items')].forEach(group => {
                    const option = document.createElement('option');
                    option.value = group;
                    option.textContent = group;
                    groupSelect.appendChild(option);
                });

                groupSelect.value = [...groupSelect.options].some(option => option.value === selected)
                    ? selected
                    : 'Custom Items';
            }
        }

        function csGetItemNumber(item, fallbackIndex) {
            // Callout number dari sheet asli (Control Valve receiving, dll.)
            if (item.number != null && item.number !== '') {
                return Number(item.number);
            }
            if (!item.custom) {
                const sourceNumber = String(item.id || '').match(/^[A-Z]+-(\d{3})$/);
                if (sourceNumber) return parseInt(sourceNumber[1], 10);
            }

            return fallbackIndex + 1;
        }

        window.csSetView = function(view) {
            currentView = view;
            document.getElementById('csSlideView').style.display = view === 'slide' ? '' : 'none';
            document.getElementById('csListView').style.display = view === 'list' ? '' : 'none';
            document.getElementById('csViewSlide').classList.toggle('cs-view-active', view === 'slide');
            document.getElementById('csViewList').classList.toggle('cs-view-active', view === 'list');
            if (view === 'slide') renderSlide();
            else if (view === 'list') renderList();
        };

        window.csOpenLightbox = function(src, label) {
            document.getElementById('csLightboxImg').src = src;
            document.getElementById('csLightboxLabel').textContent = label;
            document.getElementById('csLightbox').style.display = 'flex';
        };
        window.csCloseLightbox = function() {
            document.getElementById('csLightbox').style.display = 'none';
        };

        // ===== PROGRESS UPDATE =====
        function updateProgress() {
            const total = items.length;
            const answered = Object.keys(answers).length;
            const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
            document.getElementById('csProgressBar').style.width = pct + '%';
            document.getElementById('csProgressText').textContent = answered + ' dari ' + total + ' item diperiksa' + (pct === 100 ? ' — ✅ Selesai!' : '');
        }

        // ===== SLIDE VIEW =====
        function renderSlide() {
            const el = document.getElementById('csSlideContent');
            const total = items.length;

            if (currentIndex >= total) {
                const gc = Object.values(answers).filter(v => v === 'good').length;
                const bc = Object.values(answers).filter(v => v === 'bad').length;
                const nc = Object.values(answers).filter(v => v === 'none').length;
                el.innerHTML = `
                    <div style="font-size:3rem; margin-bottom:12px;">🎉</div>
                    <div style="font-size:1.2rem; font-weight:800; color:var(--accent-green); margin-bottom:6px;">Checksheet Selesai!</div>
                    <div style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:20px;">Semua ${total} item telah diperiksa</div>
                    <div style="display:flex; gap:24px; justify-content:center;">
                        <div style="text-align:center;"><div style="font-family:'JetBrains Mono'; font-size:1.8rem; font-weight:900; color:var(--accent-green);">${gc}</div><div style="font-size:0.6rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Good</div></div>
                        <div style="text-align:center;"><div style="font-family:'JetBrains Mono'; font-size:1.8rem; font-weight:900; color:var(--accent-red);">${bc}</div><div style="font-size:0.6rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Bad</div></div>
                        <div style="text-align:center;"><div style="font-family:'JetBrains Mono'; font-size:1.8rem; font-weight:900; color:var(--text-muted);">${nc}</div><div style="font-size:0.6rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">N/A</div></div>
                    </div>
                `;
                document.getElementById('csBtnNext').style.display = 'none';
                document.getElementById('csBtnPrev').disabled = false;
                return;
            }

            document.getElementById('csBtnNext').style.display = '';
            const item = items[currentIndex];
            const cur = answers[item.id] || null;

            const refImg = csGetRefImage(item);
            let refHtml = '';
            if (refImg) {
                const multiplePages = refImg.images.length > 1;
                const tall = !!refImg.tall;
                const pageWidth = multiplePages ? 'min(30vw, 220px)' : (tall ? 'min(92vw, 680px)' : '470px');
                const pageHeight = multiplePages ? '250px' : (tall ? 'min(58vh, 560px)' : '340px');
                const imageHtml = refImg.images.map(image => `
                    <div style="text-align:center; min-width:0;">
                        <img src="${image.src}" alt="${image.label}" style="width:${pageWidth}; max-width:100%; height:${pageHeight}; object-fit:contain; border-radius:10px; border:1px solid rgba(212,175,55,0.3); cursor:zoom-in; opacity:0.9; transition:all 0.25s; background:rgba(var(--ink), 0.02);" onclick="csOpenLightbox('${image.src}', '${image.label}')" title="📷 ${image.label}" onerror="this.parentElement.style.display='none'" onmouseover="this.style.opacity=1;this.style.borderColor='var(--accent-gold)'" onmouseout="this.style.opacity=0.9;this.style.borderColor='rgba(212,175,55,0.3)'">
                        <div style="font-size:0.52rem; font-weight:600; color:var(--accent-gold); text-transform:uppercase; letter-spacing:0.08em; margin-top:4px;">📷 ${image.label}</div>
                    </div>
                `).join('');

                refHtml = `<div style="margin-bottom:14px; display:flex; gap:8px; justify-content:center; align-items:flex-start; flex-wrap:wrap; max-width:${tall ? '720px' : '760px'};">
                    ${imageHtml}
                </div>`;
            }

            el.innerHTML = `
                ${refHtml}
                <div style="font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:var(--accent-gold); margin-bottom:8px;">${item.group || ''}</div>
                <div style="font-family:'JetBrains Mono'; font-size:2.2rem; font-weight:900; color:var(--text-primary); line-height:1; margin-bottom:6px;">#${String(csGetItemNumber(item, currentIndex)).padStart(2,'0')}</div>
                <div style="font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:4px; line-height:1.3;">${item.label}</div>
                ${item.standard ? `<div style="font-size:0.78rem; color:var(--text-secondary); line-height:1.45; max-width:620px; margin:0 auto 8px;">${item.standard}</div>` : ''}
                <div style="font-size:0.65rem; color:var(--text-muted); margin-bottom:24px;">${item.custom ? '⚡ Custom' : 'Item standar SOP'}${item.source ? ' · ' + item.source : ''}</div>
                <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                    <button class="cs-answer-btn good ${cur==='good'?'selected':''}" onclick="csAnswer('good')" ${!CAN_INTERACT?'disabled':''}>
                        <span style="font-size:1.5rem;">✓</span><span style="font-size:0.7rem; font-weight:700; text-transform:uppercase;">Good</span>
                    </button>
                    <button class="cs-answer-btn bad ${cur==='bad'?'selected':''}" onclick="csAnswer('bad')" ${!CAN_INTERACT?'disabled':''}>
                        <span style="font-size:1.5rem;">✗</span><span style="font-size:0.7rem; font-weight:700; text-transform:uppercase;">Bad</span>
                    </button>
                    <button class="cs-answer-btn none ${cur==='none'?'selected':''}" onclick="csAnswer('none')" ${!CAN_INTERACT?'disabled':''}>
                        <span style="font-size:1.5rem;">—</span><span style="font-size:0.7rem; font-weight:700; text-transform:uppercase;">N/A</span>
                    </button>
                </div>
            `;
            document.getElementById('csBtnPrev').disabled = currentIndex === 0;
        }

        // Antrian simpan: php artisan serve single-thread + SQLite mudah bentrok
        // kalau banyak POST /answer paralel (klik cepat / keyboard).
        const csSaveQueue = [];
        let csSaveBusy = false;

        async function csFlushSaves() {
            if (csSaveBusy) return;
            csSaveBusy = true;
            while (csSaveQueue.length) {
                const job = csSaveQueue.shift();
                let saved = false;
                for (let attempt = 0; attempt < 3 && !saved; attempt++) {
                    try {
                        const r = await fetch(`/components/${COMP_ID}/checksheet/${STAGE}/answer`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                            body: JSON.stringify({ item_id: job.item_id, answer: job.answer })
                        });
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) {
                            throw new Error(data.message || data.error || ('HTTP ' + r.status));
                        }
                        csToast('✓ Tersimpan');
                        saved = true;
                    } catch (e) {
                        if (attempt < 2) {
                            await new Promise(res => setTimeout(res, 250 * (attempt + 1)));
                            continue;
                        }
                        csToast('⚠ Gagal: ' + (e.message || 'jaringan'));
                    }
                }
            }
            csSaveBusy = false;
        }

        window.csAnswer = function(val) {
            if (!CAN_INTERACT) return;
            const item = items[currentIndex];
            answers[item.id] = val;
            updateProgress();

            // Gabungkan job untuk item yang sama (klik ulang sebelum flush selesai)
            const existing = csSaveQueue.findIndex(j => j.item_id === item.id);
            if (existing >= 0) csSaveQueue.splice(existing, 1);
            csSaveQueue.push({ item_id: item.id, answer: val });
            csFlushSaves();

            setTimeout(() => { currentIndex++; renderSlide(); }, 300);
        };

        window.csNav = function(dir) {
            const n = currentIndex + dir;
            if (n < 0 || n > items.length) return;
            currentIndex = n;
            renderSlide();
        };

        window.csRemoveCurrentItem = function() {
            if (currentIndex >= items.length) return;
            csRemoveItem(currentIndex);
        };

        // ===== LIST VIEW =====
        function renderList() {
            const el = document.getElementById('csItemList');
            let html = '';
            let lastGroup = '';

            const filtered = items.map((item, idx) => ({ ...item, _idx: idx }))
                .filter(item => currentFilter === 'all' || item.group === currentFilter);

            if (filtered.length === 0) {
                html = '<div style="padding:32px; text-align:center; color:var(--text-muted); font-size:0.85rem;">Tidak ada item di grup ini.</div>';
            } else {
                filtered.forEach(item => {
                    if (item.group !== lastGroup) {
                        html += `<div class="cs-group-header">${item.group || 'Lainnya'}</div>`;
                        lastGroup = item.group;
                    }

                    const ans = answers[item.id];
                    let statusHtml, numStyle;
                    if (ans === 'good') {
                        statusHtml = '<span class="cs-list-status" style="background:var(--accent-green-dim); color:var(--accent-green);">✓ Good</span>';
                        numStyle = 'background:var(--accent-green-dim); color:var(--accent-green);';
                    } else if (ans === 'bad') {
                        statusHtml = '<span class="cs-list-status" style="background:var(--accent-red-dim); color:var(--accent-red);">✗ Bad</span>';
                        numStyle = 'background:var(--accent-red-dim); color:var(--accent-red);';
                    } else if (ans === 'none') {
                        statusHtml = '<span class="cs-list-status" style="background:rgba(var(--ink), 0.04); color:var(--text-muted);">— N/A</span>';
                        numStyle = 'background:rgba(var(--ink), 0.04); color:var(--text-muted);';
                    } else {
                        statusHtml = '<span class="cs-list-status" style="background:rgba(var(--ink), 0.03); color:var(--text-muted);">Belum</span>';
                        numStyle = 'background:rgba(var(--ink), 0.04); color:var(--text-muted);';
                    }

                    const delBtn = CAN_INTERACT ?
                        `<button class="cs-del-btn" onclick="event.stopPropagation(); csRemoveItem(${item._idx})">🗑️</button><button class="cs-del-btn" onclick="event.stopPropagation(); csOpenAddModal()" style="color:var(--accent-green); border-color:rgba(52,211,153,0.2);" title="Tambah Item Baru">➕</button>` : '';

                    html += `
                        <div class="cs-list-item" onclick="csGoToItem(${item._idx})">
                            <div class="cs-list-num" style="${numStyle}">${csGetItemNumber(item, item._idx)}</div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:0.8rem; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.label}</div>
                                ${item.standard ? `<div style="font-size:0.65rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.standard}</div>` : ''}
                                ${item.custom ? '<div style="font-size:0.6rem; color:var(--accent-gold);">⚡ Custom</div>' : ''}
                            </div>
                            ${statusHtml}
                            ${delBtn}
                        </div>
                    `;
                });
            }
            el.innerHTML = html;
        }

        window.csGoToItem = function(idx) {
            currentIndex = idx;
            csSetView('slide');
        };

        window.csFilter = function(filter) {
            currentFilter = filter;
            document.querySelectorAll('.cs-filter-btn').forEach(b => {
                b.classList.toggle('cs-filter-active', b.dataset.filter === filter);
            });
            renderList();
        };

        // ===== ADD / REMOVE =====
        window.csRemoveItem = function(idx) {
            if (!CAN_INTERACT) return;
            if (!confirm('Hapus item "' + items[idx].label + '" dari checksheet?')) return;
            const item = items[idx];

            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/remove-item`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ item_id: item.id })
            }).then(r => r.json()).then(() => {
                items.splice(idx, 1);
                delete answers[item.id];
                if (currentIndex >= items.length) currentIndex = Math.max(0, items.length - 1);
                if (currentFilter !== 'all' && !items.some(item => item.group === currentFilter)) {
                    currentFilter = 'all';
                }
                csRenderGroupControls();
                updateProgress();
                if (currentView === 'slide') renderSlide(); else renderList();
                csToast('🗑️ Item dihapus');
            });
        };

        window.csOpenAddModal = function() {
            if (!CAN_INTERACT) return;
            const m = document.getElementById('csAddModal');
            m.style.display = 'flex';
            document.getElementById('csNewLabel').focus();
        };
        window.csCloseAddModal = function() {
            document.getElementById('csAddModal').style.display = 'none';
            document.getElementById('csNewLabel').value = '';
        };
        window.csSubmitAdd = function() {
            if (!CAN_INTERACT) return;
            const label = document.getElementById('csNewLabel').value.trim();
            const group = document.getElementById('csNewGroup').value;
            if (!label) return;

            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/add-item`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ label, group })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    items.push(data.item);
                    csCloseAddModal();
                    csRenderGroupControls();
                    updateProgress();
                    if (currentView === 'slide') { currentIndex = items.length - 1; renderSlide(); }
                    else renderList();
                    csToast('+ Item ditambahkan');
                }
            });
        };

        function csToast(msg) {
            const t = document.getElementById('csToast');
            t.textContent = msg;
            t.style.opacity = '1';
            t.style.transform = 'translateX(-50%) translateY(0)';
            setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(-50%) translateY(20px)'; }, 1800);
        }

        // Keyboard
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('csAddModal').style.display === 'flex') return;
            if (currentView !== 'slide') return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
            if (e.key === '1') csAnswer('good');
            else if (e.key === '2') csAnswer('bad');
            else if (e.key === '3') csAnswer('none');
            else if (e.key === 'ArrowLeft') csNav(-1);
            else if (e.key === 'ArrowRight') csNav(1);
        });

        // Init
        csRenderGroupControls();
        renderSlide();
        updateProgress();
    })();
    </script>
    @endif

    @if(!$isReviewMode)
    {{-- Realtime: pantau perubahan status komponen (stage, approval, part request) --}}
    <script>
        (function() {
            let fingerprint = null;
            let formDirty = false;

            // Jangan auto-reload kalau user sedang mengetik di form
            // (form inspeksi, remarks, dsb.) agar isian tidak hilang.
            document.addEventListener('input', function(e) {
                if (e.target.closest('form')) formDirty = true;
            });

            ocmsPoll('{{ route('status.component', $comp->comp_id) }}', 8000, function(data) {
                if (fingerprint === null) {
                    fingerprint = data.fingerprint;
                    return;
                }
                if (data.fingerprint === fingerprint) return;

                fingerprint = data.fingerprint;

                if (formDirty) {
                    // Tampilkan banner agar user reload manual tanpa kehilangan isian
                    if (!document.getElementById('staleBanner')) {
                        const banner = document.createElement('div');
                        banner.id = 'staleBanner';
                        banner.style.cssText = 'position:fixed; top:76px; left:50%; transform:translateX(-50%); z-index:500; background:rgba(212,175,55,0.95); color:#0B2B26; padding:12px 20px; border-radius:12px; font-size:0.8rem; font-weight:700; box-shadow:0 8px 32px rgba(0,0,0,0.4); cursor:pointer; display:flex; align-items:center; gap:10px;';
                        banner.innerHTML = '🔄 Status komponen berubah — klik untuk memuat ulang';
                        banner.onclick = () => location.reload();
                        document.body.appendChild(banner);
                    }
                } else {
                    location.reload();
                }
            });
        })();
    </script>

    {{-- Action Section --}}
    <div class="section">
        <div class="section-title fade-up">Aksi</div>
        <div class="glass-card fade-up">
            @if($comp->current_stage < 7)
                @if($comp->is_waiting_approval)
                    <div style="background: var(--accent-gold-dim); border: 1px solid rgba(212,175,55,0.15); border-radius: 14px; padding: 24px; margin-bottom: 24px; text-align: center;">
                        <p style="font-size: 1rem; font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Menunggu Approval Management</p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Komponen ini sedang menunggu persetujuan dari Management untuk lanjut ke Tahap {{ $comp->current_stage + 1 }}.</p>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                        @role('Management|SuperAdmin')
                        <div style="display: flex; gap: 12px;">
                            <form action="{{ route('components.rejectStage', $comp->comp_id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-secondary" style="color: var(--accent-red); border-color: rgba(248,113,113,0.3);">❌ Tolak</button>
                            </form>
                            <form action="{{ route('components.approveStage', $comp->comp_id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, var(--accent-green), #059669);">✅ Approve ke Tahap {{ $comp->current_stage + 1 }}</button>
                            </form>
                        </div>
                        @else
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Hanya Management yang bisa memberikan approval.</span>
                        @endrole
                    </div>
                @else
                    <form action="{{ route('components.updateStage', $comp->comp_id) }}" method="POST">
                        @csrf

                        {{-- Form inspeksi digital hanya untuk komponen yang TIDAK
                             memakai spreadsheet Measurement (spreadsheet menggantikannya) --}}
                        @if($comp->current_stage == 2 && !$comp->gsheet_measurement_url && !$comp->gsheet_subassy_measurement_url)
                        <div style="background: var(--accent-purple-dim); border: 1px solid rgba(167, 139, 250, 0.15); border-radius: 14px; padding: 28px; margin-bottom: 24px;">
                            <div class="section-title" style="color: var(--accent-purple); margin-bottom: 16px;">📐 Form Inspeksi Digital (Measurement & Inspection)</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 8px;">
                                <div class="ocms-label" style="margin: 0;">Nama Part</div>
                                <div class="ocms-label" style="margin: 0;">Nilai Aktual (mm)</div>
                                <div class="ocms-label" style="margin: 0;">Keputusan</div>
                            </div>
                            @php $parts = ['Crankshaft', 'Piston Ring', 'Cylinder Liner']; @endphp
                            @foreach($parts as $index => $part)
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 10px; align-items: center;">
                                <div>
                                    <input type="hidden" name="parts[{{ $index }}][name]" value="{{ $part }}">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">{{ $part }}</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="parts[{{ $index }}][actual_value]" class="ocms-input" placeholder="0.00" value="{{ old('parts.'.$index.'.actual_value') }}" required>
                                <select name="parts[{{ $index }}][decision]" class="ocms-select" required>
                                    <option value="" disabled selected>Pilih Keputusan...</option>
                                    <option value="Reused" {{ old('parts.'.$index.'.decision') == 'Reused' ? 'selected' : '' }}>🟢 Reused (Pakai Kembali)</option>
                                    <option value="Repair" {{ old('parts.'.$index.'.decision') == 'Repair' ? 'selected' : '' }}>🟡 Repair (Perbaikan)</option>
                                    <option value="Replace" {{ old('parts.'.$index.'.decision') == 'Replace' ? 'selected' : '' }}>🔴 Replace (Ganti Baru)</option>
                                </select>
                            </div>
                            @endforeach
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 12px;">*Jika ada keputusan "Replace", sistem Smart Inventory akan otomatis membuat Part Request (PR) ke Gudang. Keputusan "Repair" akan otomatis membuat draft Fabrication Request (FR).</p>
                        </div>
                        @endif

                        {{-- Quality Gate manual hanya untuk komponen TANPA checksheet
                             Test Bench GSheet (spreadsheet menggantikannya) --}}
                        @if($comp->current_stage == 5 && !$comp->gsheet_testbench_url)
                        <div style="background: var(--accent-purple-dim); border: 1px solid rgba(167, 139, 250, 0.15); border-radius: 14px; padding: 28px; margin-bottom: 24px;">
                            <div class="section-title" style="color: var(--accent-purple); margin-bottom: 16px;">🧪 Quality Gate — Test Performance</div>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">Standar Tekanan Oli: <strong style="color: var(--text-primary);">40 – 50 psi</strong></p>
                            <label class="ocms-label">Tekanan Oli Aktual (psi)</label>
                            <input type="number" step="0.1" min="0" name="oil_pressure" class="ocms-input" placeholder="45.0" value="{{ old('oil_pressure') }}" required style="max-width: 300px; font-family: 'JetBrains Mono', monospace;">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Nilai di luar 40-50 psi akan ditolak oleh Quality Gate.</p>
                        </div>
                        @endif
                        <div style="margin-bottom: 24px;">
                            <label class="ocms-label" style="display: block; margin-bottom: 8px;">Catatan / Remarks (Opsional)</label>
                            <textarea name="remarks" class="ocms-input" placeholder="Tambahkan catatan untuk Management sebelum mengajukan approval..." style="width: 100%; min-height: 80px; resize: vertical;"></textarea>
                        </div>

                        {{-- Stage 2-5 wajib approval Management (GL); stage 1 & 6 lanjut otomatis --}}
                        @php $needsApproval = in_array($comp->current_stage, [2, 3, 4, 5]); @endphp
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                            @role('Mechanic|Supervisor|SuperAdmin')
                            <button type="submit" class="btn-primary">
                                {{ $needsApproval
                                    ? 'Ajukan Approval ke Tahap ' . ($comp->current_stage + 1) . ' →'
                                    : 'Selesaikan Tahap & Lanjut ke Tahap ' . ($comp->current_stage + 1) . ' →' }}
                            </button>
                            @else
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Hanya Mekanik/Supervisor yang bisa mengajukan proses.</span>
                            @endrole
                        </div>
                    </form>
                @endif
            @else
                {{-- Stage 7 (RFU): panel penutup ada di atas; di sini hanya navigasi --}}
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                    <span style="font-size: 0.8rem; color: var(--accent-green); font-weight: 600;">🎉 Overhaul selesai — komponen berstatus RFU</span>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Skrip panel FR & MOL (Tab toggle, auto-scan, upload MOL) --}}
    @if(in_array($viewedStage, [2, 3], true))
    <script>
    (function() {
        const compId = @json($comp->comp_id);
        const csrf = @json(csrf_token());
        const scanUrl = @json(route('components.fr.scan', $comp->comp_id));
        const molUploadUrl = @json(route('components.mol.upload-document', $comp->comp_id));
        const molDeleteUrl = @json(route('components.mol.delete-document', $comp->comp_id));

        // Tab Toggle Elements
        const tabFrBtn = document.getElementById('tab-fr-btn');
        const tabMolBtn = document.getElementById('tab-mol-btn');
        const tabFrContent = document.getElementById('tab-fr-content');
        const tabMolContent = document.getElementById('tab-mol-content');

        if (tabFrBtn && tabMolBtn) {
            tabFrBtn.addEventListener('click', function() {
                tabFrBtn.className = 'btn-primary';
                tabMolBtn.className = 'btn-secondary';
                tabFrContent.style.display = 'block';
                tabMolContent.style.display = 'none';
            });

            tabMolBtn.addEventListener('click', function() {
                tabMolBtn.className = 'btn-primary';
                tabFrBtn.className = 'btn-secondary';
                tabMolContent.style.display = 'block';
                tabFrContent.style.display = 'none';
            });
        }

        // Scan Button (Auto-Create FR & MOL)
        const scanBtn = document.getElementById('fr-scan-btn');
        const scanStatus = document.getElementById('fr-scan-status');
        const scanProfile = document.getElementById('fr-scan-profile');

        if (scanBtn) {
            scanBtn.addEventListener('click', async function() {
                scanBtn.disabled = true;
                scanStatus.textContent = 'Memindai & membuat FR/MOL…';
                try {
                    const res = await fetch(scanUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({}),
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Scan gagal');

                    if (scanProfile && data.scan_profile_label) {
                        scanProfile.textContent = 'Profil scan: ' + data.scan_profile_label;
                    }

                    let msg = '✅ ' + (data.message || 'Scan selesai.');
                    if (data.gsheet_error) {
                        msg += ` (GSheet: ${data.gsheet_error})`;
                    } else if (data.gsheet_sheet) {
                        msg += ` — sheet: ${data.gsheet_sheet}`;
                    }
                    if ((data.skipped || []).length) {
                        msg += `, ${data.skipped.length} dilewati (sudah ada)`;
                    }
                    scanStatus.textContent = msg;

                    // Reload jika ada FR/PR baru yang berhasil dibuat
                    const createdFrCount = (data.created_fr || []).length;
                    const createdPrCount = (data.created_pr || []).length;
                    if (createdFrCount > 0 || createdPrCount > 0) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch (e) {
                    scanStatus.textContent = '⚠ ' + (e.message || 'Gagal scan');
                } finally {
                    scanBtn.disabled = false;
                }
            });
        }

        // Upload Dokumen MOL AJAX
        const molUploadForm = document.getElementById('mol-upload-form');
        const molUploadStatus = document.getElementById('mol-upload-status');
        if (molUploadForm) {
            molUploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const fileInput = document.getElementById('mol-doc-input');
                if (!fileInput || !fileInput.files.length) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ Pilih file terlebih dahulu.';
                    return;
                }

                const formData = new FormData(molUploadForm);
                if (molUploadStatus) molUploadStatus.textContent = 'Mengunggah dokumen…';

                try {
                    const res = await fetch(molUploadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Upload gagal');

                    if (molUploadStatus) molUploadStatus.textContent = '✅ ' + data.message;
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ ' + (err.message || 'Gagal upload');
                }
            });
        }

        // Delete Dokumen MOL AJAX
        const molDocDeleteBtn = document.getElementById('mol-doc-delete-btn');
        if (molDocDeleteBtn) {
            molDocDeleteBtn.addEventListener('click', async function() {
                if (!confirm('Hapus dokumen MOL ini?')) return;

                if (molUploadStatus) molUploadStatus.textContent = 'Menghapus dokumen…';

                try {
                    const res = await fetch(molDeleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Hapus gagal');

                    if (molUploadStatus) molUploadStatus.textContent = '✅ Dokumen dihapus.';
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ ' + (err.message || 'Gagal hapus');
                }
            });
        }
    })();
    </script>
    @endif

</x-app-layout>
