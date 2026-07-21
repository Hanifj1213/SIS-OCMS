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
                @if(!$isReviewMode && auth()->user()->canOperateOverhaul())
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
        const CAN_INTERACT = @json(!$isReviewMode && auth()->user()->canOperateOverhaul());
        @php
            // Peta gambar referensi dihitung server-side (hanya file yang ada).
            // Prioritas per item: gambar grup > gambar "semua item" (kategori).
            // Konvensi file di public/images/inspection/{egi}/:
            //   semua item : {kategori}.png            (contoh: control-valve.png)
            //   per grup   : {kategori}--{grup}.png    (Engine lama: {grup}.png)
            $csEgiSlug = str_replace(' ', '-', strtolower(trim((string) ($comp->egi ?? '')))) ?: 'd375-6';
            $csCatSlug = str_replace(['/', ' '], '-', strtolower((string) $comp->major_category));
            $csIsEngine = $comp->major_category === 'Engine';

            $csGroupImages = [];
            foreach (collect($currentChecksheet->items)->pluck('group')->filter()->unique() as $csGroup) {
                $csGroupSlug = str_replace(' ', '-', strtolower(trim((string) $csGroup)));
                $csCandidates = $csIsEngine
                    ? [$csGroupSlug, $csCatSlug . '--' . $csGroupSlug]
                    : [$csCatSlug . '--' . $csGroupSlug];
                foreach ($csCandidates as $csFile) {
                    $csPath = public_path("images/inspection/{$csEgiSlug}/{$csFile}.png");
                    if (is_file($csPath)) {
                        $csGroupImages[$csGroup] = asset("images/inspection/{$csEgiSlug}/{$csFile}.png") . '?v=' . filemtime($csPath);
                        break;
                    }
                }
            }

            $csCatPath = public_path("images/inspection/{$csEgiSlug}/{$csCatSlug}.png");
            $csCategoryImage = is_file($csCatPath)
                ? asset("images/inspection/{$csEgiSlug}/{$csCatSlug}.png") . '?v=' . filemtime($csCatPath)
                : null;
        @endphp
        const CS_GROUP_IMAGES = @json($csGroupImages);
        const CS_CATEGORY_IMAGE = @json($csCategoryImage);
        const CS_CATEGORY_LABEL = @json($comp->major_category . ' Reference');

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

        // Gambar referensi per item. Urutan prioritas:
        // 1. Gambar SOP stage 2 lama (dari item.source) — snapshot komponen lama.
        // 2. Gambar grup (gaya Engine) — diupload per grup lewat panel Dev.
        // 3. Gambar "semua item" (gaya Control Valve) — satu gambar untuk
        //    seluruh checksheet, berlaku juga untuk item custom.
        function csGetRefImage(item) {
            const stageTwoImages = csGetStageTwoReferenceImages(item.source);
            if (stageTwoImages.length > 0) {
                return { images: stageTwoImages, label: item.group, tall: false };
            }

            const groupImage = item.group ? CS_GROUP_IMAGES[item.group] : null;
            if (groupImage) {
                return {
                    images: [{ src: groupImage, label: item.group }],
                    label: item.group,
                    tall: false,
                };
            }

            if (CS_CATEGORY_IMAGE) {
                return {
                    images: [{ src: CS_CATEGORY_IMAGE, label: CS_CATEGORY_LABEL }],
                    label: CS_CATEGORY_LABEL,
                    // Sheet satu halaman penuh (A3) → butuh preview lebih besar
                    tall: true,
                };
            }

            return null;
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

        // Helper DOM aman. Seluruh teks dinamis (label, group, standard,
        // source, label gambar) masuk lewat textContent/setAttribute — tidak
        // pernah lewat innerHTML dan tidak pernah menjadi inline event
        // handler. Data checksheet berasal dari template & input pengguna,
        // jadi tidak boleh pernah dieksekusi sebagai markup.
        function csEl(tag, style, text) {
            const el = document.createElement(tag);
            if (style) el.setAttribute('style', style);
            if (text != null && text !== '') el.textContent = String(text);
            return el;
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

                el.replaceChildren();
                el.appendChild(csEl('div', 'font-size:3rem; margin-bottom:12px;', '🎉'));
                el.appendChild(csEl('div', 'font-size:1.2rem; font-weight:800; color:var(--accent-green); margin-bottom:6px;', 'Checksheet Selesai!'));
                el.appendChild(csEl('div', 'font-size:0.85rem; color:var(--text-secondary); margin-bottom:20px;', 'Semua ' + total + ' item telah diperiksa'));

                const summary = csEl('div', 'display:flex; gap:24px; justify-content:center;');
                [['Good', gc, 'var(--accent-green)'], ['Bad', bc, 'var(--accent-red)'], ['N/A', nc, 'var(--text-muted)']]
                    .forEach(([label, count, color]) => {
                        const box = csEl('div', 'text-align:center;');
                        box.appendChild(csEl('div', "font-family:'JetBrains Mono'; font-size:1.8rem; font-weight:900; color:" + color + ';', count));
                        box.appendChild(csEl('div', 'font-size:0.6rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;', label));
                        summary.appendChild(box);
                    });
                el.appendChild(summary);

                document.getElementById('csBtnNext').style.display = 'none';
                document.getElementById('csBtnPrev').disabled = false;
                return;
            }

            document.getElementById('csBtnNext').style.display = '';
            const item = items[currentIndex];
            const cur = answers[item.id] || null;

            el.replaceChildren();

            const refImg = csGetRefImage(item);
            if (refImg) {
                const multiplePages = refImg.images.length > 1;
                const tall = !!refImg.tall;
                const pageWidth = multiplePages ? 'min(30vw, 220px)' : (tall ? 'min(92vw, 680px)' : '470px');
                const pageHeight = multiplePages ? '250px' : (tall ? 'min(58vh, 560px)' : '340px');

                const refWrap = csEl('div', 'margin-bottom:14px; display:flex; gap:8px; justify-content:center; align-items:flex-start; flex-wrap:wrap; max-width:' + (tall ? '720px' : '760px') + ';');

                refImg.images.forEach(image => {
                    const box = csEl('div', 'text-align:center; min-width:0;');

                    const img = document.createElement('img');
                    img.setAttribute('style', 'width:' + pageWidth + '; max-width:100%; height:' + pageHeight + '; object-fit:contain; border-radius:10px; border:1px solid rgba(212,175,55,0.3); cursor:zoom-in; opacity:0.9; transition:all 0.25s; background:rgba(var(--ink), 0.02);');
                    img.src = image.src;
                    img.alt = image.label || '';
                    img.title = '📷 ' + (image.label || '');
                    img.addEventListener('click', () => window.csOpenLightbox(image.src, image.label || ''));
                    img.addEventListener('error', () => { box.style.display = 'none'; });
                    img.addEventListener('mouseover', () => { img.style.opacity = 1; img.style.borderColor = 'var(--accent-gold)'; });
                    img.addEventListener('mouseout', () => { img.style.opacity = 0.9; img.style.borderColor = 'rgba(212,175,55,0.3)'; });
                    box.appendChild(img);

                    box.appendChild(csEl('div', 'font-size:0.52rem; font-weight:600; color:var(--accent-gold); text-transform:uppercase; letter-spacing:0.08em; margin-top:4px;', '📷 ' + (image.label || '')));
                    refWrap.appendChild(box);
                });

                el.appendChild(refWrap);
            }

            el.appendChild(csEl('div', 'font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:var(--accent-gold); margin-bottom:8px;', item.group || ''));
            el.appendChild(csEl('div', "font-family:'JetBrains Mono'; font-size:2.2rem; font-weight:900; color:var(--text-primary); line-height:1; margin-bottom:6px;", '#' + String(csGetItemNumber(item, currentIndex)).padStart(2, '0')));
            el.appendChild(csEl('div', 'font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:4px; line-height:1.3;', item.label));

            if (item.standard) {
                el.appendChild(csEl('div', 'font-size:0.78rem; color:var(--text-secondary); line-height:1.45; max-width:620px; margin:0 auto 8px;', item.standard));
            }

            el.appendChild(csEl(
                'div',
                'font-size:0.65rem; color:var(--text-muted); margin-bottom:24px;',
                (item.custom ? '⚡ Custom' : 'Item standar SOP') + (item.source ? ' · ' + item.source : '')
            ));

            const answerRow = csEl('div', 'display:flex; gap:10px; justify-content:center; flex-wrap:wrap;');
            [['good', '✓', 'Good'], ['bad', '✗', 'Bad'], ['none', '—', 'N/A']].forEach(([value, icon, label]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cs-answer-btn ' + value + (cur === value ? ' selected' : '');
                button.disabled = !CAN_INTERACT;
                button.appendChild(csEl('span', 'font-size:1.5rem;', icon));
                button.appendChild(csEl('span', 'font-size:0.7rem; font-weight:700; text-transform:uppercase;', label));
                button.addEventListener('click', () => window.csAnswer(value));
                answerRow.appendChild(button);
            });
            el.appendChild(answerRow);

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
            let lastGroup = '';

            el.replaceChildren();

            const filtered = items.map((item, idx) => ({ ...item, _idx: idx }))
                .filter(item => currentFilter === 'all' || item.group === currentFilter);

            if (filtered.length === 0) {
                el.appendChild(csEl('div', 'padding:32px; text-align:center; color:var(--text-muted); font-size:0.85rem;', 'Tidak ada item di grup ini.'));
                return;
            }

            const STATUS = {
                good: ['✓ Good', 'background:var(--accent-green-dim); color:var(--accent-green);', 'background:var(--accent-green-dim); color:var(--accent-green);'],
                bad: ['✗ Bad', 'background:var(--accent-red-dim); color:var(--accent-red);', 'background:var(--accent-red-dim); color:var(--accent-red);'],
                none: ['— N/A', 'background:rgba(var(--ink), 0.04); color:var(--text-muted);', 'background:rgba(var(--ink), 0.04); color:var(--text-muted);'],
            };

            filtered.forEach(item => {
                if (item.group !== lastGroup) {
                    const header = csEl('div', null, item.group || 'Lainnya');
                    header.className = 'cs-group-header';
                    el.appendChild(header);
                    lastGroup = item.group;
                }

                const ans = answers[item.id];
                const [statusLabel, statusStyle, numStyle] = STATUS[ans]
                    || ['Belum', 'background:rgba(var(--ink), 0.03); color:var(--text-muted);', 'background:rgba(var(--ink), 0.04); color:var(--text-muted);'];

                const row = csEl('div');
                row.className = 'cs-list-item';
                row.addEventListener('click', () => window.csGoToItem(item._idx));

                const num = csEl('div', numStyle, csGetItemNumber(item, item._idx));
                num.className = 'cs-list-num';
                row.appendChild(num);

                const body = csEl('div', 'flex:1; min-width:0;');
                body.appendChild(csEl('div', 'font-size:0.8rem; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;', item.label));
                if (item.standard) {
                    body.appendChild(csEl('div', 'font-size:0.65rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;', item.standard));
                }
                if (item.custom) {
                    body.appendChild(csEl('div', 'font-size:0.6rem; color:var(--accent-gold);', '⚡ Custom'));
                }
                row.appendChild(body);

                const status = csEl('span', statusStyle, statusLabel);
                status.className = 'cs-list-status';
                row.appendChild(status);

                if (CAN_INTERACT) {
                    const del = csEl('button', null, '🗑️');
                    del.type = 'button';
                    del.className = 'cs-del-btn';
                    del.addEventListener('click', event => {
                        event.stopPropagation();
                        window.csRemoveItem(item._idx);
                    });
                    row.appendChild(del);

                    const add = csEl('button', 'color:var(--accent-green); border-color:rgba(52,211,153,0.2);', '➕');
                    add.type = 'button';
                    add.className = 'cs-del-btn';
                    add.title = 'Tambah Item Baru';
                    add.addEventListener('click', event => {
                        event.stopPropagation();
                        window.csOpenAddModal();
                    });
                    row.appendChild(add);
                }

                el.appendChild(row);
            });
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
