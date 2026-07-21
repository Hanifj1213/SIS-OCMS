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
