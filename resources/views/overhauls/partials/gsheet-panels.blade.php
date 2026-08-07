    @if($hasStageGsheetPanel)
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
        .gsheet-open-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
        }
        .gsheet-open-bar a {
            font-size: 0.78rem;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid var(--glass-border-light);
            color: var(--accent-cyan);
            text-decoration: none;
            background: rgba(var(--ink), 0.03);
        }
        .gsheet-open-bar a:hover { background: rgba(var(--ink), 0.06); }
    </style>
    {{--
        Google Sheets mode edit tidak punya opsi resmi untuk menyembunyikan
        header baris/kolom dan bar tab sheet, jadi kita "crop": iframe dibuat
        lebih besar dari kotaknya lalu digeser sehingga strip header kiri/atas
        dan bar tab bawah terpotong di luar area terlihat.
    --}}
    @if($hasDisassyPanel)
    @php
        $isEngineDisassy = $comp->major_category === 'Engine';
        $disassyCropMainlineLeft = $isEngineDisassy ? 120 : 46;
        $disassyCropMainlineBottom = 0;
        $cropLeft = $gsheetEmbedUrl ? $disassyCropMainlineLeft : 46;
        $cropTop = 25;
        $cropBottom = $gsheetEmbedUrl ? $disassyCropMainlineBottom : 0;
        $disassySrc = $gsheetEmbedUrl ?: $gsheetSubassyDisassyEmbedUrl;
        $disassyEditMain = $toEditLink($comp->gsheet_url);
        $disassyEditSub = $toEditLink($comp->gsheet_subassy_disassembly_url);
        $disassyEditInitial = $disassyEditMain ?: $disassyEditSub;
    @endphp
    <div class="section" id="checksheet-review"
         data-mainline-url="{{ $gsheetEmbedUrl }}"
         data-subassy-url="{{ $gsheetSubassyDisassyEmbedUrl }}"
         data-edit-mainline-url="{{ $disassyEditMain ?? '' }}"
         data-edit-subassy-url="{{ $disassyEditSub ?? '' }}">
        <div class="section-title fade-up">🔧 Disassembly — Checksheet</div>
        @if($gsheetEmbedUrl && $gsheetSubassyDisassyEmbedUrl)
        <div class="cs-scope-toggle fade-up" data-scope-for="disassy" role="tablist" aria-label="Disassembly scope">
            <button type="button" class="active" data-scope="mainline">Mainline</button>
            <button type="button" data-scope="subassy">Sub Assy</button>
        </div>
        @elseif($gsheetSubassyDisassyEmbedUrl && !$gsheetEmbedUrl)
        <div class="fade-up" style="margin-bottom:12px; color:var(--text-secondary); font-size:0.85rem;">Mode: Sub Assy</div>
        @endif
        @if($disassyEditInitial)
        <div class="gsheet-open-bar fade-up">
            <a href="{{ $disassyEditInitial }}" data-gsheet-edit target="_blank" rel="noopener">✏️ Edit di Google Sheets</a>
        </div>
        @endif
        <div class="glass-card fade-up gsheet-shell" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe" class="gsheet-embed"
                data-src="{{ $disassySrc }}"
                data-crop-mainline="{{ $disassyCropMainlineLeft }},25,{{ $disassyCropMainlineBottom }}"
                data-crop-subassy="46,25,0"
                style="position: absolute; top: -{{ $cropTop }}px; left: -{{ $cropLeft }}px; width: calc(100% + {{ $cropLeft }}px); height: calc(100% + {{ $cropTop + $cropBottom }}px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    @elseif($checksheetStage == 2 && !empty($disassemblyTemplateAvailable))
    <div class="section" id="checksheet-review">
        <div class="section-title fade-up">🔧 Disassembly — Checksheet</div>
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            <p style="font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Salinan Disassembly sedang disiapkan</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Template sudah ada di Google Sheets. Duplikasi berjalan di latar belakang
                (butuh <code>php artisan queue:work</code>). Refresh halaman dalam 1–2 menit.
                Jika URL tetap kosong, pastikan secret Apps Script (<code>OCMS_SECRET</code>)
                sama dengan <code>GSHEET_COPY_SECRET</code> di <code>.env</code>.
            </p>
        </div>
    </div>
    @elseif($checksheetStage == 2)
    <div class="section" id="checksheet-review">
        <div class="section-title fade-up">🔧 Disassembly — Checksheet</div>
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            <p style="font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Belum ada template Disassembly</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                EGI <strong>{{ $comp->egi }}</strong> / {{ $comp->major_category }} belum punya
                template Disassembly di config.
            </p>
        </div>
    </div>
    @endif

    @if($hasMeasurePanel)
    @php
        $measureEditMain = $toEditLink($comp->gsheet_measurement_url);
        $measureEditSub = $toEditLink($comp->gsheet_subassy_measurement_url);
        $measureEditInitial = $measureEditMain ?: $measureEditSub;
        $mCropLeft = 46;
        $mCropTop = 25;
        $measureSrc = $gsheetMeasurementEmbedUrl ?: $gsheetSubassyMeasureEmbedUrl;
    @endphp
    {{--
        Measurement menggantikan Form Inspeksi Digital lama. Kontennya mulai
        dari kolom A, jadi crop kiri hanya selebar kolom nomor baris. Crop
        bawah 0 supaya bar tab sheet tetap terlihat.
    --}}
    <div class="section"
         data-mainline-url="{{ $gsheetMeasurementEmbedUrl }}"
         data-subassy-url="{{ $gsheetSubassyMeasureEmbedUrl }}"
         data-edit-mainline-url="{{ $measureEditMain ?? '' }}"
         data-edit-subassy-url="{{ $measureEditSub ?? '' }}">
        <div class="section-title fade-up">📐 Measurement & Inspection — Form Inspeksi</div>
        @if($gsheetMeasurementEmbedUrl && $gsheetSubassyMeasureEmbedUrl)
        <div class="cs-scope-toggle fade-up" data-scope-for="measure" role="tablist" aria-label="Measurement scope">
            <button type="button" class="active" data-scope="mainline">Mainline</button>
            <button type="button" data-scope="subassy">Sub Assy</button>
        </div>
        @elseif($gsheetSubassyMeasureEmbedUrl && !$gsheetMeasurementEmbedUrl)
        <div class="fade-up" style="margin-bottom:12px; color:var(--text-secondary); font-size:0.85rem;">Mode: Sub Assy</div>
        @endif
        @if($measureEditInitial)
        <div class="gsheet-open-bar fade-up">
            <a href="{{ $measureEditInitial }}" data-gsheet-edit target="_blank" rel="noopener">✏️ Edit di Google Sheets</a>
        </div>
        @endif
        <div class="glass-card fade-up gsheet-shell" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
            <iframe id="gsheet-iframe-measure" class="gsheet-embed"
                data-src="{{ $measureSrc }}"
                data-crop-mainline="46,25,0"
                data-crop-subassy="46,25,0"
                style="position: absolute; top: -{{ $mCropTop }}px; left: -{{ $mCropLeft }}px; width: calc(100% + {{ $mCropLeft }}px); height: calc(100% + {{ $mCropTop }}px); border: none;"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    @elseif($checksheetStage == 2 && !empty($measurementTemplateAvailable))
    <div class="section">
        <div class="section-title fade-up">📐 Measurement & Inspection — Form Inspeksi</div>
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            <p style="font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Salinan Measurement sedang disiapkan</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Template sudah ada di Google Sheets. Duplikasi berjalan di latar belakang
                (butuh <code>php artisan queue:work</code>). Refresh halaman dalam 1–2 menit.
                Jika URL tetap kosong, pastikan secret Apps Script (<code>OCMS_SECRET</code>)
                sama dengan <code>GSHEET_COPY_SECRET</code> di <code>.env</code>.
            </p>
        </div>
    </div>
    @elseif($checksheetStage == 2)
    <div class="section">
        <div class="section-title fade-up">📐 Measurement & Inspection — Form Inspeksi</div>
        <div class="glass-card fade-up" style="padding: 24px; text-align: center;">
            <p style="font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Belum ada template Measurement</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                EGI <strong>{{ $comp->egi }}</strong> / {{ $comp->major_category }} belum punya
                template Measurement di config.
            </p>
        </div>
    </div>
    @endif

    @if($checksheetStage == 4)
    <div class="section" id="checksheet-assembly">
        <div class="section-title fade-up">🔩 Assembly — Checksheet</div>
        @if($gsheetAssemblyEmbedUrl)
        <div class="glass-card fade-up gsheet-shell" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
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

    {{-- Dokumentasi Assembly: upload dokumen (PDF) / foto, tampil di bawah checksheet --}}
    @php $assemblyDocs = array_values($comp->assembly_documents ?? []); @endphp
    <div class="section" id="assembly-documents-panel">
        <div class="section-title fade-up">📎 Upload File atau Foto dari Eksternal ({{ count($assemblyDocs) }})</div>
        <div class="glass-card fade-up">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                Unggah dokumen (PDF) atau foto dokumentasi tahap Assembly sebagai bukti pengerjaan perakitan.
            </p>

            @ocmsOperate
            <form action="{{ route('components.assembly.upload', $comp->comp_id) }}" method="POST" enctype="multipart/form-data"
                  style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; padding: 12px; border: 1px dashed rgba(var(--ink), 0.15); border-radius: 10px;">
                @csrf
                <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple required style="font-size: 0.75rem; max-width: 320px;">
                <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 0.8rem;">📤 Upload Dokumen/Foto</button>
                <span style="font-size: 0.7rem; color: var(--text-muted);">PDF/JPG/PNG/WebP, maks. 10 MB per file, hingga 12 file sekali unggah.</span>
            </form>
            @endocmsOperate

            @if($errors->has('assembly'))
                <div class="alert alert-error" style="margin-bottom: 16px;">{{ $errors->first('assembly') }}</div>
            @endif

            @if(count($assemblyDocs) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;">
                @foreach($assemblyDocs as $idx => $doc)
                <div style="border: 1px solid rgba(var(--ink), 0.08); border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.15);">
                    @if(($doc['type'] ?? 'image') === 'pdf')
                        <a href="{{ asset($doc['path']) }}" target="_blank" title="Buka PDF"
                           style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 170px; text-decoration: none; gap: 8px;">
                            <span style="font-size: 2.6rem;">📄</span>
                            <span style="font-size: 0.72rem; color: var(--text-secondary); padding: 0 10px; text-align: center; word-break: break-all;">
                                {{ $doc['name'] ?? basename($doc['path']) }}
                            </span>
                            <span class="badge badge-cyan" style="font-size: 0.6rem;">Buka PDF ↗</span>
                        </a>
                    @else
                        <a href="{{ asset($doc['path']) }}" target="_blank" title="Buka ukuran penuh">
                            <img src="{{ asset($doc['path']) }}" alt="Dokumen assembly {{ $idx + 1 }}"
                                 style="display: block; width: 100%; height: 170px; object-fit: cover;">
                        </a>
                    @endif
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px;">
                        <span style="font-size: 0.65rem; color: var(--text-muted);">
                            {{ $doc['uploaded_at'] ?? '' }}{{ !empty($doc['uploaded_by']) ? ' · ' . $doc['uploaded_by'] : '' }}
                        </span>
                        @ocmsOperate
                        <form action="{{ route('components.assembly.delete', $comp->comp_id) }}" method="POST" style="margin: 0;"
                              onsubmit="return confirm('Hapus dokumen ini?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="index" value="{{ $idx }}">
                            <button type="submit" class="btn-secondary" style="padding: 3px 8px; font-size: 0.65rem; color: #f87171;">🗑</button>
                        </form>
                        @endocmsOperate
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                Belum ada dokumen/foto assembly yang diunggah.
            </p>
            @endif
        </div>
    </div>
    @endif

    @if($checksheetStage == 5)
    <div class="section" id="checksheet-testbench">
        <div class="section-title fade-up">🧪 Test Bench — Checksheet</div>
        @if($gsheetTestbenchEmbedUrl)
        <div class="glass-card fade-up gsheet-shell" style="padding: 0; overflow: hidden; height: 90vh; border-radius: 12px; border: 1px solid rgba(var(--ink), 0.1); position: relative;">
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

        // Toggle Mainline | Sub Assy: ganti src iframe + crop + link edit.
        document.addEventListener('DOMContentLoaded', function() {
            function syncGsheetEditLink(section, scope) {
                const editBtn = section.querySelector('[data-gsheet-edit]');
                if (!editBtn) return;
                let editUrl = section.getAttribute('data-edit-' + scope + '-url');
                if (!editUrl) {
                    editUrl = section.getAttribute('data-edit-' + (scope === 'mainline' ? 'subassy' : 'mainline') + '-url');
                }
                if (editUrl) {
                    editBtn.href = editUrl;
                    editBtn.style.display = '';
                } else {
                    editBtn.style.display = 'none';
                }
            }

            document.querySelectorAll('.section[data-edit-mainline-url], .section[data-edit-subassy-url]').forEach(function(section) {
                const activeBtn = section.querySelector('.cs-scope-toggle button.active');
                const scope = activeBtn ? activeBtn.getAttribute('data-scope') : (section.getAttribute('data-edit-mainline-url') ? 'mainline' : 'subassy');
                syncGsheetEditLink(section, scope);
            });

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

                        syncGsheetEditLink(section, scope);

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

        // Cegah scroll-jack Google Sheets: halaman loncat ke atas saat klik/sentuh sel.
        // Aktif di desktop & HP. Tidak mem-blur iframe saat interaksi di zona GSheet.
        document.addEventListener('DOMContentLoaded', function() {
            const iframes = Array.from(document.querySelectorAll('.gsheet-embed'));
            if (iframes.length === 0) return;

            iframes.forEach(function(iframe) {
                iframe.setAttribute('tabindex', '0');
            });

            const isTouchDevice = window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window;
            const isEmbedFocused = () => iframes.includes(document.activeElement);

            function pointerInGsheetZone() {
                if (!lastPointer) return isTouchDevice;
                const stack = document.elementsFromPoint(lastPointer.x, lastPointer.y);
                return stack.some(function(el) {
                    return el.closest && el.closest('.gsheet-shell, .gsheet-open-bar, .cs-scope-toggle');
                });
            }

            let history = [{ t: performance.now(), x: window.scrollX, y: window.scrollY }];
            const lock = { active: false, x: 0, y: 0 };
            let lastInputT = 0;
            let lastPointer = null;

            function trackPointer(x, y) {
                lastPointer = { x: x, y: y };
            }

            ['wheel', 'touchstart', 'touchmove', 'mousedown', 'pointerdown', 'keydown'].forEach(function(evt) {
                document.addEventListener(evt, function(e) {
                    lastInputT = performance.now();
                    if ((evt === 'touchstart' || evt === 'touchmove') && e.touches && e.touches[0]) {
                        trackPointer(e.touches[0].clientX, e.touches[0].clientY);
                    }
                }, { passive: true, capture: true });
            });

            document.addEventListener('mousemove', function(e) {
                trackPointer(e.clientX, e.clientY);
            }, { passive: true, capture: true });
            document.addEventListener('mousedown', function(e) {
                trackPointer(e.clientX, e.clientY);
            }, { passive: true, capture: true });

            document.querySelectorAll('.gsheet-shell').forEach(function(shell) {
                shell.addEventListener('touchstart', function(e) {
                    if (e.touches && e.touches[0]) {
                        trackPointer(e.touches[0].clientX, e.touches[0].clientY);
                    }
                    recordPos();
                    lockScroll();
                }, { passive: true });

                shell.addEventListener('touchend', function() {
                    const iframe = shell.querySelector('iframe.gsheet-embed');
                    if (iframe) {
                        try { iframe.focus({ preventScroll: true }); } catch (_) { iframe.focus(); }
                    }
                }, { passive: true });
            });

            function recordPos() {
                if (lock.active) return;
                const now = performance.now();
                history.push({ t: now, x: window.scrollX, y: window.scrollY });
                while (history.length > 1 && now - history[0].t > 800) history.shift();
            }

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
                const last = history[history.length - 1];
                const jumped = Math.abs(window.scrollY - last.y) > 100
                    && performance.now() - lastInputT > 250;
                if (jumped) {
                    window.scrollTo({ left: last.x, top: last.y, behavior: 'instant' });
                    return;
                }
                recordPos();
            }, { passive: true });

            window.addEventListener('blur', function() {
                setTimeout(function() {
                    if (!isEmbedFocused()) return;
                    const iframe = document.activeElement;

                    if (!pointerInGsheetZone()) {
                        iframe.blur();
                        window.focus();
                        const p = stablePosBefore(150);
                        window.scrollTo({ left: p.x, top: p.y, behavior: 'instant' });
                        return;
                    }

                    lockScroll();
                }, 0);
            });

            window.addEventListener('focus', unlockScroll);

            function releaseIframeFocus(e) {
                if (e && e.target && e.target.closest && e.target.closest('.gsheet-shell, .gsheet-open-bar, .cs-scope-toggle')) {
                    return;
                }
                if (isEmbedFocused()) {
                    document.activeElement.blur();
                    window.focus();
                }
                unlockScroll();
            }
            ['wheel', 'mousedown'].forEach(function(evt) {
                document.addEventListener(evt, releaseIframeFocus, { passive: true });
            });
            if (isTouchDevice) {
                document.addEventListener('touchstart', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.gsheet-shell, .gsheet-open-bar, .cs-scope-toggle')) {
                        return;
                    }
                    unlockScroll();
                }, { passive: true });
            }
        });
        </script>
    @endif
