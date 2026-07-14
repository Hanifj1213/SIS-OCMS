<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checksheet — {{ $comp->serial_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0B2B26;
            --bg-secondary: #091528;
            --accent-gold: #D4AF37;
            --accent-gold-dim: rgba(212, 175, 55, 0.15);
            --accent-cyan: #48CAE4;
            --accent-cyan-dim: rgba(72, 202, 228, 0.12);
            --accent-green: #34D399;
            --accent-green-dim: rgba(52, 211, 153, 0.12);
            --accent-red: #F87171;
            --accent-red-dim: rgba(248, 113, 113, 0.12);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-border-light: rgba(255, 255, 255, 0.10);
            --text-primary: rgba(255, 255, 255, 0.92);
            --text-secondary: rgba(255, 255, 255, 0.55);
            --text-muted: rgba(255, 255, 255, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(170deg, var(--bg-primary) 0%, var(--bg-secondary) 40%, #0d1f3c 100%);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* === HEADER === */
        .cs-header {
            padding: 16px 24px;
            background: rgba(11, 43, 38, 0.6);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 10;
        }
        .cs-header-back {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        .cs-header-back:hover { color: var(--text-primary); }
        .cs-header-title {
            text-align: center;
        }
        .cs-header-title h2 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent-gold);
        }
        .cs-header-title span {
            font-size: 0.65rem;
            color: var(--text-muted);
        }
        .cs-header-counter {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }

        /* === PROGRESS BAR === */
        .cs-progress {
            padding: 0 24px;
            flex-shrink: 0;
            margin-top: 12px;
        }
        .cs-progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 3px;
            overflow: hidden;
        }
        .cs-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-green));
            border-radius: 3px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cs-progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* === SLIDE CONTAINER === */
        .cs-slide-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            position: relative;
        }
        .cs-slide {
            width: 100%;
            max-width: 520px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cs-slide.slide-enter-right { transform: translateX(100px); opacity: 0; }
        .cs-slide.slide-enter-left { transform: translateX(-100px); opacity: 0; }
        .cs-slide.slide-active { transform: translateX(0); opacity: 1; }

        .cs-group-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--accent-gold);
            margin-bottom: 12px;
        }
        .cs-item-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 3rem;
            font-weight: 900;
            color: rgba(255,255,255,0.06);
            margin-bottom: 8px;
            line-height: 1;
        }
        .cs-item-label {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .cs-item-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* === ANSWER BUTTONS === */
        .cs-answers {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .cs-answer-btn {
            flex: 1;
            min-width: 100px;
            max-width: 160px;
            padding: 20px 16px;
            border-radius: 16px;
            border: 2px solid var(--glass-border);
            background: rgba(255,255,255,0.03);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            -webkit-tap-highlight-color: transparent;
        }
        .cs-answer-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.3);
        }
        .cs-answer-btn:active { transform: scale(0.96); }

        .cs-answer-btn .cs-answer-icon { font-size: 1.8rem; }
        .cs-answer-btn .cs-answer-text {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .cs-answer-btn .cs-answer-key {
            font-size: 0.6rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-muted);
            padding: 2px 8px;
            background: rgba(255,255,255,0.04);
            border-radius: 4px;
        }

        /* Answer states */
        .cs-answer-btn.good { border-color: rgba(52, 211, 153, 0.3); }
        .cs-answer-btn.good:hover, .cs-answer-btn.good.selected {
            background: var(--accent-green-dim);
            border-color: var(--accent-green);
            color: var(--accent-green);
        }
        .cs-answer-btn.bad { border-color: rgba(248, 113, 113, 0.3); }
        .cs-answer-btn.bad:hover, .cs-answer-btn.bad.selected {
            background: var(--accent-red-dim);
            border-color: var(--accent-red);
            color: var(--accent-red);
        }
        .cs-answer-btn.none { border-color: rgba(255,255,255,0.1); }
        .cs-answer-btn.none:hover, .cs-answer-btn.none.selected {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.2);
            color: var(--text-secondary);
        }

        /* === NAVIGATION === */
        .cs-nav {
            padding: 16px 24px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            gap: 12px;
        }
        .cs-nav-btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.04);
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cs-nav-btn:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
        .cs-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .cs-nav-btn.finish {
            background: linear-gradient(135deg, var(--accent-gold), #EAA112);
            color: #0B2B26;
            border: none;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
        }
        .cs-nav-btn.finish:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3); }

        .cs-add-btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px dashed rgba(212, 175, 55, 0.3);
            background: transparent;
            color: var(--accent-gold);
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cs-add-btn:hover { background: var(--accent-gold-dim); border-style: solid; }

        /* === COMPLETION SCREEN === */
        .cs-complete {
            text-align: center;
        }
        .cs-complete-icon { font-size: 4rem; margin-bottom: 16px; }
        .cs-complete h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-green);
            margin-bottom: 8px;
        }
        .cs-complete p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        .cs-complete-stats {
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-bottom: 32px;
        }
        .cs-stat {
            text-align: center;
        }
        .cs-stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            font-weight: 900;
        }
        .cs-stat-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
        }

        /* === MODAL === */
        .cs-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 24px;
        }
        .cs-modal {
            background: linear-gradient(170deg, #0f3d36, var(--bg-secondary));
            border: 1px solid var(--glass-border-light);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 420px;
        }
        .cs-modal h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent-gold);
        }
        .cs-modal input, .cs-modal select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            margin-bottom: 16px;
        }
        .cs-modal input:focus, .cs-modal select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        .cs-modal select option { background: var(--bg-secondary); color: white; }
        .cs-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .cs-item-label { font-size: 1.1rem; }
            .cs-item-number { font-size: 2.2rem; }
            .cs-answers { flex-direction: column; align-items: center; }
            .cs-answer-btn { max-width: 100%; width: 100%; flex-direction: row; padding: 16px 20px; }
            .cs-answer-btn .cs-answer-icon { font-size: 1.4rem; }
            .cs-answer-btn .cs-answer-key { display: none; }
        }

        /* Toast */
        .cs-toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: var(--accent-green);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s;
            z-index: 200;
            pointer-events: none;
        }
        .cs-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="cs-header">
        <a href="{{ route('components.show', $comp->comp_id) }}" class="cs-header-back">
            ← Kembali
        </a>
        <div class="cs-header-title">
            <h2>{{ $comp->major_category }} Checksheet</h2>
            <span>{{ $stageName }} — {{ $comp->serial_number }}</span>
        </div>
        <div class="cs-header-counter" id="counter">
            {{ count($checksheet->answers ?? []) }}/{{ count($checksheet->items) }}
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="cs-progress">
        <div class="cs-progress-bar">
            <div class="cs-progress-fill" id="progressFill" style="width: {{ $checksheet->progress }}%"></div>
        </div>
        <div class="cs-progress-text">
            <span id="progressGroup">—</span>
            <span id="progressPercent">{{ $checksheet->progress }}%</span>
        </div>
    </div>

    <!-- Slide Area -->
    <div class="cs-slide-area">
        <div class="cs-slide slide-active" id="slideContent">
            <!-- Filled by JS -->
        </div>
    </div>

    <!-- Navigation -->
    <div class="cs-nav">
        <button class="cs-nav-btn" id="btnPrev" onclick="navigate(-1)" disabled>← Prev</button>

        @if(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']))
        <button class="cs-add-btn" onclick="openAddModal()">+ Tambah Item</button>
        @endif

        <button class="cs-nav-btn" id="btnNext" onclick="navigate(1)">Next →</button>
    </div>

    <!-- Add Item Modal -->
    <div class="cs-modal-overlay" id="addModal" style="display: none;">
        <div class="cs-modal">
            <h3>+ Tambah Item Checksheet</h3>
            <label style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Nama Item</label>
            <input type="text" id="newItemLabel" placeholder="Contoh: Bracket Custom XYZ">
            <label style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Grup</label>
            <select id="newItemGroup">
                <option value="Custom Items">Custom Items</option>
                <option value="Right Side View">Right Side View</option>
                <option value="Left Side View">Left Side View</option>
                <option value="Rear Side View">Rear Side View</option>
            </select>
            <div class="cs-modal-actions">
                <button class="cs-nav-btn" onclick="closeAddModal()">Batal</button>
                <button class="cs-nav-btn finish" onclick="submitAddItem()">Tambahkan</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="cs-toast" id="toast">✓ Tersimpan</div>

    <script>
    (function() {
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const COMP_ID = {{ $comp->comp_id }};
        const STAGE = {{ $stage }};

        let items = @json($checksheet->items);
        let answers = @json($checksheet->answers ?? (object)[]);
        let currentIndex = 0;
        let canInteract = @json(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']));

        // Find first unanswered
        for (let i = 0; i < items.length; i++) {
            if (!answers[items[i].id]) { currentIndex = i; break; }
        }

        function render() {
            const slide = document.getElementById('slideContent');
            const total = items.length;
            const answered = Object.keys(answers).length;

            // Update progress
            const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressPercent').textContent = pct + '%';
            document.getElementById('counter').textContent = answered + '/' + total;

            // Completion screen
            if (currentIndex >= total) {
                const goodCount = Object.values(answers).filter(v => v === 'good').length;
                const badCount = Object.values(answers).filter(v => v === 'bad').length;
                const noneCount = Object.values(answers).filter(v => v === 'none').length;

                document.getElementById('progressGroup').textContent = 'Selesai!';
                slide.innerHTML = `
                    <div class="cs-complete">
                        <div class="cs-complete-icon">🎉</div>
                        <h2>Checksheet Selesai!</h2>
                        <p>Semua ${total} item telah diperiksa</p>
                        <div class="cs-complete-stats">
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-green);">${goodCount}</div>
                                <div class="cs-stat-label">Good ✓</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-red);">${badCount}</div>
                                <div class="cs-stat-label">Bad ✗</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--text-muted);">${noneCount}</div>
                                <div class="cs-stat-label">N/A —</div>
                            </div>
                        </div>
                        <a href="${'{{ route('components.show', $comp->comp_id) }}'}" class="cs-nav-btn finish" style="text-decoration:none; display:inline-block;">← Kembali ke Detail Komponen</a>
                    </div>
                `;
                document.getElementById('btnNext').style.display = 'none';
                document.getElementById('btnPrev').disabled = false;
                return;
            }

            document.getElementById('btnNext').style.display = '';

            const item = items[currentIndex];
            const currentAnswer = answers[item.id] || null;
            document.getElementById('progressGroup').textContent = item.group || '—';

            const deleteBtn = (canInteract && item.custom)
                ? `<button onclick="removeCurrentItem()" style="margin-top:12px; padding:6px 14px; border-radius:8px; border:1px solid rgba(248,113,113,0.2); background:rgba(248,113,113,0.08); color:var(--accent-red); font-size:0.7rem; font-weight:600; cursor:pointer; font-family:'Inter',sans-serif;">🗑️ Hapus Item Ini</button>`
                : '';

            slide.innerHTML = `
                <div class="cs-group-label">${item.group || ''}</div>
                <div class="cs-item-number">#${String(currentIndex + 1).padStart(2, '0')}</div>
                <div class="cs-item-label">${item.label}</div>
                <div class="cs-item-meta">${item.custom ? '⚡ Custom Item' : 'Item standar SOP'}</div>
                <div class="cs-answers">
                    <button class="cs-answer-btn good ${currentAnswer === 'good' ? 'selected' : ''}" onclick="answer('good')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✓</span>
                        <span class="cs-answer-text">Good</span>
                        <span class="cs-answer-key">1</span>
                    </button>
                    <button class="cs-answer-btn bad ${currentAnswer === 'bad' ? 'selected' : ''}" onclick="answer('bad')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✗</span>
                        <span class="cs-answer-text">Bad</span>
                        <span class="cs-answer-key">2</span>
                    </button>
                    <button class="cs-answer-btn none ${currentAnswer === 'none' ? 'selected' : ''}" onclick="answer('none')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">—</span>
                        <span class="cs-answer-text">N/A</span>
                        <span class="cs-answer-key">3</span>
                    </button>
                </div>
                ${deleteBtn}
            `;

            // Nav buttons
            document.getElementById('btnPrev').disabled = currentIndex === 0;
        }

        window.answer = function(val) {
            if (!canInteract) return;
            const item = items[currentIndex];
            answers[item.id] = val;

            // Save via AJAX
            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/answer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ item_id: item.id, answer: val })
            }).then(r => r.json()).then(data => {
                showToast('✓ Tersimpan');
            }).catch(() => {
                showToast('⚠ Gagal menyimpan');
            });

            // Auto-advance after short delay
            setTimeout(() => {
                currentIndex++;
                animateSlide('right');
            }, 350);
        };

        window.navigate = function(dir) {
            const newIndex = currentIndex + dir;
            if (newIndex < 0) return;
            if (newIndex > items.length) return;
            currentIndex = newIndex;
            animateSlide(dir > 0 ? 'right' : 'left');
        };

        function animateSlide(direction) {
            const slide = document.getElementById('slideContent');
            slide.classList.remove('slide-active', 'slide-enter-right', 'slide-enter-left');
            slide.classList.add(direction === 'right' ? 'slide-enter-right' : 'slide-enter-left');

            setTimeout(() => {
                render();
                slide.classList.remove('slide-enter-right', 'slide-enter-left');
                void slide.offsetWidth; // force reflow
                slide.classList.add('slide-active');
            }, 150);
        }

        window.removeCurrentItem = function() {
            if (!confirm('Hapus item ini dari checksheet?')) return;
            const item = items[currentIndex];

            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/remove-item`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ item_id: item.id })
            }).then(r => r.json()).then(data => {
                items.splice(currentIndex, 1);
                delete answers[item.id];
                if (currentIndex >= items.length) currentIndex = items.length - 1;
                if (currentIndex < 0) currentIndex = 0;
                render();
                showToast('🗑️ Item dihapus');
            });
        };

        window.openAddModal = function() {
            document.getElementById('addModal').style.display = 'flex';
            document.getElementById('newItemLabel').focus();
        };

        window.closeAddModal = function() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('newItemLabel').value = '';
        };

        window.submitAddItem = function() {
            const label = document.getElementById('newItemLabel').value.trim();
            const group = document.getElementById('newItemGroup').value;
            if (!label) return;

            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/add-item`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ label, group })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    items.push(data.item);
                    closeAddModal();
                    currentIndex = items.length - 1;
                    render();
                    showToast('+ Item ditambahkan');
                }
            });
        };

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 1800);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('addModal').style.display === 'flex') return;
            if (e.key === '1') answer('good');
            else if (e.key === '2') answer('bad');
            else if (e.key === '3') answer('none');
            else if (e.key === 'ArrowLeft') navigate(-1);
            else if (e.key === 'ArrowRight') navigate(1);
        });

        // Touch swipe
        let touchStartX = 0;
        document.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
        document.addEventListener('touchend', e => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 60) {
                navigate(diff < 0 ? 1 : -1);
            }
        });

        // Initial render
        render();
    })();
    </script>
</body>
</html>
