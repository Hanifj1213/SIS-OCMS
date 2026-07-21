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
