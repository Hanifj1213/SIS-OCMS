<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Template Checksheet</h1>
            <p>Checksheet internal per kategori × EGI — hanya Receiving (Step 1) &amp; Delivery (Step 6). Step 2–5 memakai Google Sheets.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error fade-up">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    {{-- Form buat template baru / duplikat --}}
    <div class="section fade-up">
        <div class="section-title">Buat Template Baru</div>
        <div class="glass-card">
            <form method="POST" action="{{ route('dev.checksheet-templates.store') }}">
                @csrf
                <div class="grid-4 stack-mobile" style="align-items: end;">
                    <div>
                        <label class="ocms-label">Kategori *</label>
                        <input type="text" name="major_category" class="ocms-input" list="ct-category-list"
                            value="{{ old('major_category') }}" required placeholder="Contoh: TC/Transmission">
                        <datalist id="ct-category-list">
                            @foreach($templates->pluck('major_category')->unique() as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="ocms-label">EGI</label>
                        <input type="text" name="egi_model" class="ocms-input" value="{{ old('egi_model') }}"
                            placeholder="Contoh: HD785-7">
                    </div>
                    <div>
                        <label class="ocms-label">Step *</label>
                        <select name="stage_number" class="ocms-select" required>
                            <option value="1" {{ old('stage_number') == '1' ? 'selected' : '' }}>1 — Receiving</option>
                            <option value="6" {{ old('stage_number') == '6' ? 'selected' : '' }}>6 — Delivery</option>
                        </select>
                    </div>
                    <div>
                        <label class="ocms-label">Nama Template *</label>
                        <input type="text" name="template_name" class="ocms-input" value="{{ old('template_name') }}"
                            required placeholder="Contoh: TC Receiving Inspection Sheet">
                    </div>
                </div>
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">
                    💡 Kategori bebas diketik — kategori baru otomatis muncul di form registrasi komponen.
                    EGI dikosongkan = template <strong>Generic</strong> (fallback untuk EGI yang belum punya template sendiri).
                </p>
                <div class="grid-2 stack-mobile" style="margin-top: 16px; align-items: end;">
                    <div>
                        <label class="ocms-label">Duplikat Item Dari (opsional)</label>
                        <select name="copy_from" class="ocms-select">
                            <option value="">— Mulai dari kosong —</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ old('copy_from') == $tpl->id ? 'selected' : '' }}>
                                    {{ $tpl->major_category }}{{ $tpl->egi_model ? ' / ' . $tpl->egi_model : ' (Generic)' }} — Step {{ $tpl->stage_number }} ({{ count($tpl->items ?? []) }} item)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" class="btn-primary">📋 Buat Template</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar template --}}
    <div class="section fade-up">
        <div class="section-title">Template Terdaftar ({{ $templates->count() }})</div>

        {{-- Search & filter (client-side) --}}
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 12px;">
            <input type="text" id="tpl-search" class="ocms-input" placeholder="🔍 Cari kategori / EGI / nama template…"
                style="flex: 1; min-width: 220px; max-width: 380px;">
            <select id="tpl-step-filter" class="ocms-select" style="width: auto;">
                <option value="">Semua Step</option>
                <option value="1">Step 1 — Receiving</option>
                <option value="6">Step 6 — Delivery</option>
            </select>
            <span id="tpl-count" style="font-size: 0.75rem; color: var(--text-muted);"></span>
        </div>

        <div class="glass-card table-scroll" style="padding: 0;">
            <table class="ocms-table" id="tpl-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>EGI</th>
                        <th>Step</th>
                        <th>Nama Template</th>
                        <th>Jumlah Item</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                    <tr data-step="{{ $tpl->stage_number }}" data-search="{{ strtolower($tpl->major_category . ' ' . ($tpl->egi_model ?? 'generic semua egi') . ' ' . $tpl->template_name) }}">
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $tpl->major_category }}</td>
                        <td class="mono">{{ $tpl->egi_model ?? 'Generic (semua EGI)' }}</td>
                        <td><span class="badge {{ $tpl->stage_number == 1 ? 'badge-cyan' : 'badge-purple' }}">Step {{ $tpl->stage_number }}</span></td>
                        <td>{{ $tpl->template_name }}</td>
                        <td class="mono">{{ count($tpl->items ?? []) }}</td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="{{ route('dev.checksheet-templates.edit', $tpl->id) }}" class="btn-secondary btn-sm">✏️ Edit Item</a>
                                <form method="POST" action="{{ route('dev.checksheet-templates.destroy', $tpl->id) }}" style="margin: 0;"
                                    onsubmit="return confirm('Hapus template {{ $tpl->template_name }}? Checksheet komponen yang sudah ada tidak terpengaruh.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada template checksheet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <script>
            (function () {
                const search = document.getElementById('tpl-search');
                const stepFilter = document.getElementById('tpl-step-filter');
                const rows = Array.from(document.querySelectorAll('#tpl-table tbody tr[data-search]'));
                const count = document.getElementById('tpl-count');

                function apply() {
                    const q = search.value.trim().toLowerCase();
                    const step = stepFilter.value;
                    let visible = 0;
                    rows.forEach(row => {
                        const match = (!q || row.dataset.search.includes(q)) && (!step || row.dataset.step === step);
                        row.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    count.textContent = q || step ? visible + ' dari ' + rows.length + ' template' : '';
                }

                search.addEventListener('input', apply);
                stepFilter.addEventListener('change', apply);
            })();
        </script>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px;">
            ⚠️ Perubahan template hanya berlaku untuk komponen yang <strong>didaftarkan setelah</strong> perubahan disimpan.
            Checksheet komponen yang sudah berjalan merupakan snapshot dan tidak ikut berubah.
        </p>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
            💡 <strong>Generic (semua EGI)</strong> = template cadangan: dipakai hanya kalau komponen yang didaftarkan
            EGI-nya <strong>belum punya</strong> template sendiri di kategori tersebut. Contoh: registrasi Control Valve HD1500-7
            (tidak ada templatenya) → sistem memakai "Control Valve … (Generic)". Kalau EGI-nya sudah punya template
            spesifik (misal Control Valve D375-6), Generic tidak dipakai.
        </p>
    </div>

    <div class="section fade-up">
        <a href="{{ route('dev.index') }}" class="btn-secondary">← Kembali ke Panel Developer</a>
    </div>

</x-app-layout>
