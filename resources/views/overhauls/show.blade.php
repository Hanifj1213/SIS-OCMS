<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>{{ $comp->serial_number }}</h1>
            <p>{{ $comp->model_type }}</p>
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

    {{-- Info + QR Code --}}
    <div class="section" style="display: grid; grid-template-columns: 1fr 240px; gap: 20px;">
        <div class="glass-card fade-up">
            <div class="section-title" style="margin-bottom: 16px;">Informasi Utama</div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); width: 35%; font-size: 0.85rem;">Serial Number</td>
                    <td class="mono" style="padding: 10px 0; font-size: 0.95rem;">{{ $comp->serial_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Model Alat</td>
                    <td style="padding: 10px 0; font-size: 0.85rem;">{{ $comp->model_type }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Kategori</td>
                    <td style="padding: 10px 0;"><span class="badge badge-cyan">{{ $comp->major_category }}</span></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Status</td>
                    <td style="padding: 10px 0;">
                        @if($comp->status == 'On Progress')
                            <span class="badge badge-gold">🔧 {{ $stageNames[$comp->current_stage] ?? 'Tahap '.$comp->current_stage }}</span>
                        @else
                            <span class="badge badge-green">✅ Ready for Use (RFU)</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Terdaftar</td>
                    <td style="padding: 10px 0; font-size: 0.85rem; color: var(--text-secondary);">{{ $comp->created_at->format('d M Y H:i') }} WIB</td>
                </tr>
            </table>
        </div>
        <div class="glass-card fade-up" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="section-title">QR Code</div>
            @if($comp->qr_code_path)
                <img src="{{ asset($comp->qr_code_path) }}" alt="QR" style="width: 140px; height: 140px; border-radius: 12px; border: 1px solid var(--glass-border); padding: 6px; background: white; margin-top: 8px;">
            @else
                <div style="width: 140px; height: 140px; border-radius: 12px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">N/A</div>
            @endif
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="section">
        <div class="section-title fade-up">Progress Overhaul</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div class="stage-bar">
                @for($i = 1; $i <= 9; $i++)
                    <div class="stage-node {{ $i < $comp->current_stage ? 'completed' : ($i == $comp->current_stage ? 'active' : 'pending') }}">
                        <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                        <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                    </div>
                    @if($i < 9)
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
            <div style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.03); {{ $loop->last ? 'border: none;' : '' }}">
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
                </div>
            </div>
            @empty
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 24px;">Belum ada riwayat.</p>
            @endforelse
        </div>
    </div>

    {{-- Inspection Results --}}
    @if($comp->inspectionDetails->count() > 0)
    <div class="section">
        <div class="section-title fade-up">Hasil Inspeksi & Pengukuran</div>
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden;">
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

    {{-- Part Requests --}}
    @if($comp->partRequests->count() > 0)
    <div class="section">
        <div class="section-title fade-up">Permintaan Suku Cadang</div>
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden;">
            <table class="ocms-table">
                <thead>
                    <tr>
                        <th>Part</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comp->partRequests as $pr)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $pr->part_name }}</td>
                        <td class="mono">{{ $pr->qty }}</td>
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
    </div>
    @endif

    {{-- Checksheet Section (Inline Interactive) --}}
    @php $currentChecksheet = $comp->checksheets->where('stage_number', $comp->current_stage)->first(); @endphp
    @if($currentChecksheet && $comp->current_stage < 9)
    <div class="section">
        <div class="section-title fade-up">Checksheet — {{ $stageNames[$comp->current_stage] ?? '' }}</div>
        <div class="glass-card fade-up" id="csContainer">

            {{-- Header: Progress + View Toggle --}}
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 4px;">
                        📋 {{ $comp->major_category }} Receiving Inspection
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 8px;" id="csProgressText">
                        {{ count($currentChecksheet->answers ?? []) }} dari {{ count($currentChecksheet->items) }} item diperiksa
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden;">
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
                    @if(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']))
                    <div style="display: flex; gap: 8px;">
                        <button onclick="csOpenAddModal()" class="cs-add-btn">+ Tambah</button>
                        <button onclick="csRemoveCurrentItem()" id="csBtnDel" class="cs-del-nav-btn"> - Hapus</button>
                    </div>
                    @endif
                    <button onclick="csNav(1)" id="csBtnNext" class="cs-nav-btn">Next →</button>
                </div>
            </div>

            {{-- ===== LIST VIEW ===== --}}
            <div id="csListView" style="display: none;">
                {{-- Group Filters --}}
                <div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;">
                    <button onclick="csFilter('all')" class="cs-filter-btn cs-filter-active" data-filter="all">Semua</button>
                    <button onclick="csFilter('Right Side View')" class="cs-filter-btn" data-filter="Right Side View">Right Side</button>
                    <button onclick="csFilter('Left Side View')" class="cs-filter-btn" data-filter="Left Side View">Left Side</button>
                    <button onclick="csFilter('Rear Side View')" class="cs-filter-btn" data-filter="Rear Side View">Rear Side</button>
                    <button onclick="csFilter('Custom Items')" class="cs-filter-btn" data-filter="Custom Items">Custom</button>
                </div>
                {{-- Item List --}}
                <div id="csItemList" style="max-height: 500px; overflow-y: auto; border-radius: 12px;">
                    <!-- Filled by JS -->
                </div>
                @if(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']))
                <div style="margin-top: 12px; text-align: center;">
                    <button onclick="csOpenAddModal()" class="cs-add-btn" style="width: 100%;">+ Tambah Item Kustom</button>
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Add Item Modal --}}
    <div id="csAddModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); z-index:1000; display:none; align-items:center; justify-content:center; padding:24px;">
        <div style="background:linear-gradient(170deg,#0f3d36,var(--bg-secondary)); border:1px solid var(--glass-border-light); border-radius:20px; padding:32px; width:100%; max-width:420px;">
            <div class="section-title" style="color:var(--accent-gold); margin-bottom:20px;">+ Tambah Item Checksheet</div>
            <label style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px;">Nama Item</label>
            <input type="text" id="csNewLabel" class="ocms-input" placeholder="Contoh: Bracket Custom XYZ" style="margin-bottom:16px;">
            <label style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px;">Grup</label>
            <select id="csNewGroup" class="ocms-select" style="margin-bottom:20px;">
                <option value="Custom Items">Custom Items</option>
                <option value="Right Side View">Right Side View</option>
                <option value="Left Side View">Left Side View</option>
                <option value="Rear Side View">Rear Side View</option>
            </select>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button onclick="csCloseAddModal()" class="btn-secondary" style="padding:10px 20px;">Batal</button>
                <button onclick="csSubmitAdd()" class="btn-primary" style="padding:10px 20px;">Tambahkan</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="csToast" style="position:fixed; bottom:80px; left:50%; transform:translateX(-50%) translateY(20px); background:rgba(52,211,153,0.15); border:1px solid rgba(52,211,153,0.3); color:var(--accent-green); padding:10px 20px; border-radius:10px; font-size:0.8rem; font-weight:600; opacity:0; transition:all 0.3s; z-index:200; pointer-events:none;"></div>

    <style>
        .cs-view-btn {
            padding: 8px 14px; border-radius: 10px; border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.03); color: var(--text-muted); font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-view-btn:hover { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .cs-view-active { background: var(--accent-cyan-dim) !important; color: var(--accent-cyan) !important; border-color: rgba(72,202,228,0.3) !important; }

        .cs-nav-btn {
            padding: 10px 20px; border-radius: 10px; border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.04); color: var(--text-secondary); font-family: 'Inter', sans-serif;
            font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-nav-btn:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
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
            background: rgba(255,255,255,0.03); color: var(--text-muted); font-family: 'Inter', sans-serif;
            font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .cs-filter-btn:hover { color: var(--text-secondary); background: rgba(255,255,255,0.06); }
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
            background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.15); color: rgba(255,255,255,0.5);
        }
        .cs-answer-btn.none:hover, .cs-answer-btn.none.selected {
            background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.3); color: rgba(255,255,255,0.7);
            box-shadow: 0 8px 28px rgba(255,255,255,0.05);
        }

        .cs-list-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.03); cursor: pointer; transition: background 0.15s;
        }
        .cs-list-item:hover { background: rgba(255,255,255,0.04); }
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
        const STAGE = {{ $comp->current_stage }};
        const CAN_INTERACT = @json(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']));

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
        window.csSetView = function(view) {
            currentView = view;
            document.getElementById('csSlideView').style.display = view === 'slide' ? '' : 'none';
            document.getElementById('csListView').style.display = view === 'list' ? '' : 'none';
            document.getElementById('csViewSlide').classList.toggle('cs-view-active', view === 'slide');
            document.getElementById('csViewList').classList.toggle('cs-view-active', view === 'list');
            if (view === 'slide') renderSlide();
            else renderList();
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

            el.innerHTML = `
                <div style="font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:var(--accent-gold); margin-bottom:8px;">${item.group || ''}</div>
                <div style="font-family:'JetBrains Mono'; font-size:2.2rem; font-weight:900; color:rgba(255,255,255); line-height:1; margin-bottom:6px;">#${String(currentIndex+1).padStart(2,'0')}</div>
                <div style="font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:4px; line-height:1.3;">${item.label}</div>
                <div style="font-size:0.7rem; color:var(--text-muted); margin-bottom:24px;">${item.custom ? '⚡ Custom' : 'Item standar SOP'}</div>
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

        window.csAnswer = function(val) {
            if (!CAN_INTERACT) return;
            const item = items[currentIndex];
            answers[item.id] = val;
            updateProgress();

            fetch(`/components/${COMP_ID}/checksheet/${STAGE}/answer`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ item_id: item.id, answer: val })
            }).then(r => r.json()).then(() => csToast('✓ Tersimpan')).catch(() => csToast('⚠ Gagal'));

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
                        statusHtml = '<span class="cs-list-status" style="background:rgba(255,255,255,0.04); color:var(--text-muted);">— N/A</span>';
                        numStyle = 'background:rgba(255,255,255,0.04); color:var(--text-muted);';
                    } else {
                        statusHtml = '<span class="cs-list-status" style="background:rgba(255,255,255,0.03); color:var(--text-muted);">Belum</span>';
                        numStyle = 'background:rgba(255,255,255,0.04); color:var(--text-muted);';
                    }

                    const delBtn = CAN_INTERACT ?
                        `<button class="cs-del-btn" onclick="event.stopPropagation(); csRemoveItem(${item._idx})">🗑️</button>` : '';

                    html += `
                        <div class="cs-list-item" onclick="csGoToItem(${item._idx})">
                            <div class="cs-list-num" style="${numStyle}">${item._idx + 1}</div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:0.8rem; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.label}</div>
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
                updateProgress();
                if (currentView === 'slide') renderSlide(); else renderList();
                csToast('🗑️ Item dihapus');
            });
        };

        window.csOpenAddModal = function() {
            const m = document.getElementById('csAddModal');
            m.style.display = 'flex';
            document.getElementById('csNewLabel').focus();
        };
        window.csCloseAddModal = function() {
            document.getElementById('csAddModal').style.display = 'none';
            document.getElementById('csNewLabel').value = '';
        };
        window.csSubmitAdd = function() {
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
        renderSlide();
        updateProgress();
    })();
    </script>
    @endif

    {{-- Action Section --}}
    <div class="section">
        <div class="section-title fade-up">Aksi</div>
        <div class="glass-card fade-up">
            @if($comp->current_stage < 9)
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

                        @if($comp->current_stage == 4)
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
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 12px;">*Jika ada keputusan "Replace", sistem Smart Inventory akan otomatis membuat Part Request (PR) ke Gudang.</p>
                        </div>
                        @endif

                        @if($comp->current_stage == 7)
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

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                            @role('Mechanic|Supervisor|SuperAdmin')
                            <button type="submit" class="btn-primary">Ajukan Approval ke Tahap {{ $comp->current_stage + 1 }} →</button>
                            @else
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Hanya Mekanik/Supervisor yang bisa mengajukan proses.</span>
                            @endrole
                        </div>
                    </form>
                @endif
            @else
                <div style="background: var(--accent-green-dim); border: 1px solid rgba(52, 211, 153, 0.15); border-radius: 14px; padding: 24px; margin-bottom: 24px; text-align: center;">
                    <p style="font-size: 1rem; font-weight: 700; color: var(--accent-green);">🎉 Komponen telah selesai overhaul — Ready for Use (RFU)</p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                    <a href="{{ route('components.printPdf', $comp->comp_id) }}" target="_blank" class="btn-danger">🖨 Cetak Berita Acara (PDF)</a>
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
