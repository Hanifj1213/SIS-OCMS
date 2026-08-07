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
