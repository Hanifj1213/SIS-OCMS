    {{-- Timeline Log --}}
    <div class="section">
        <div class="section-title fade-up">Riwayat Pengerjaan</div>
        <div class="glass-card fade-up">
            @forelse($comp->overhaulLogs->sortBy('stage_number') as $log)
            <div class="timeline-entry" style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid rgba(var(--ink), 0.03); {{ $loop->last ? 'border: none;' : '' }}">
                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
                    {{ $log->end_time ? 'background: var(--accent-green-dim); color: var(--accent-green);' : 'background: var(--accent-cyan-dim); color: var(--accent-cyan);' }}
                ">{{ $log->stage_number }}</div>
                <div class="timeline-entry-body" style="flex: 1;">
                    <div class="timeline-entry-header" style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                        <div style="min-width: 0; flex: 1;">
                            <div class="timeline-stage-title" style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">{{ $stageNames[$log->stage_number] ?? 'Tahap '.$log->stage_number }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Petugas: {{ $log->mechanic ? $log->mechanic->name : 'Sistem' }}</div>
                        </div>
                        @if($log->end_time)
                            <span class="badge badge-green timeline-entry-badge" style="font-size: 0.6rem;">Selesai</span>
                        @else
                            <span class="badge badge-cyan timeline-entry-badge" style="font-size: 0.6rem;">● Aktif</span>
                        @endif
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; font-family: 'JetBrains Mono', monospace;">
                        {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('d/m/Y H:i') : '-' }}
                        → {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d/m/Y H:i') : 'Sekarang' }}
                    </div>
                    @if($log->notes)
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; font-style: italic;">"{{ $log->notes }}"</div>
                    @endif

                    {{-- Jejak approval: pengaju, penyetuju, dan jeda waktunya --}}
                    @if($log->approval_requested_at || $log->approved_at)
                    <div style="margin-top: 8px; padding: 10px 12px; border-radius: 10px; background: rgba(212,175,55,0.06); border: 1px solid rgba(212,175,55,0.15); font-size: 0.72rem; line-height: 1.7;">
                        @if($log->approval_requested_at)
                            <div style="color: var(--text-secondary);">
                                📨 Diajukan approval oleh
                                <strong style="color: var(--accent-gold);">{{ $log->approvalRequester?->name ?? 'Tidak diketahui' }}</strong>
                                <span class="mono" style="color: var(--text-muted);">· {{ $log->approval_requested_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                        @if($log->approved_at)
                            @php
                                $approvalGapText = null;
                                if ($log->approval_requested_at) {
                                    $gapSeconds = (int) abs($log->approved_at->getTimestamp() - $log->approval_requested_at->getTimestamp());
                                    $gapDays = intdiv($gapSeconds, 86400);
                                    $gapHours = intdiv($gapSeconds % 86400, 3600);
                                    $gapMinutes = intdiv($gapSeconds % 3600, 60);
                                    $parts = [];
                                    if ($gapDays > 0) $parts[] = $gapDays . ' hari';
                                    if ($gapHours > 0) $parts[] = $gapHours . ' jam';
                                    if ($gapMinutes > 0) $parts[] = $gapMinutes . ' menit';
                                    if (empty($parts)) $parts[] = $gapSeconds . ' detik';
                                    $approvalGapText = implode(' ', $parts);
                                }
                            @endphp
                            <div style="color: var(--text-secondary);">
                                ✅ Di-approve oleh
                                <strong style="color: var(--accent-green);">{{ $log->approver?->name ?? 'Tidak diketahui' }}</strong>
                                <span class="mono" style="color: var(--text-muted);">· {{ $log->approved_at->format('d/m/Y H:i') }}</span>
                                @if($approvalGapText)
                                    <span style="color: var(--accent-cyan);">— disetujui setelah {{ $approvalGapText }} dari pengajuan</span>
                                @endif
                            </div>
                        @elseif($log->approval_requested_at)
                            <div style="color: var(--text-muted); font-style: italic;">⏳ Menunggu persetujuan Group Leader / Supervisor…</div>
                        @endif
                    </div>
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
                        $canManageCrew = auth()->user()->canOperateOverhaul();
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
                                  class="crew-add-form" style="display: flex; gap: 6px; margin-left: auto;">
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
