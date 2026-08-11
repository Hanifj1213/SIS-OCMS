<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Edit Template Checksheet</h1>
            <p>
                {{ $template->major_category }}{{ $template->egi_model ? ' / ' . $template->egi_model : ' — Generic (semua EGI)' }}
                — Step {{ $template->stage_number }} ({{ $template->stage_number == 6 ? 'Delivery' : 'Receiving' }})
            </p>
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

    <style>
        .item-row { display: grid; grid-template-columns: 90px 200px 1fr 110px; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(var(--ink), 0.04); }
        .item-row .ocms-input { padding: 8px 12px; font-size: 0.8rem; }
        .item-move-btn { background: rgba(var(--ink), 0.04); border: 1px solid var(--glass-border); border-radius: 6px; color: var(--text-secondary); cursor: pointer; padding: 4px 8px; font-size: 0.7rem; }
        .item-move-btn:hover { background: rgba(var(--ink), 0.1); color: var(--text-primary); }
        .item-del-btn { background: var(--accent-red-dim); border: 1px solid rgba(248, 113, 113, 0.2); border-radius: 6px; color: var(--accent-red); cursor: pointer; padding: 4px 8px; font-size: 0.7rem; }
        @media (max-width: 768px) {
            .item-row { grid-template-columns: 1fr; gap: 4px; padding: 12px 0; }
        }
    </style>

    <form method="POST" action="{{ route('dev.checksheet-templates.update', $template->id) }}">
        @csrf @method('PUT')

        <div class="section fade-up">
            <div class="glass-card">
                <div class="grid-2 stack-mobile" style="align-items: end;">
                    <div>
                        <label class="ocms-label">Nama Template *</label>
                        <input type="text" name="template_name" class="ocms-input" required
                            value="{{ old('template_name', $template->template_name) }}">
                    </div>
                    <div style="text-align: right;">
                        <span class="badge badge-cyan" id="item-count-badge"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section fade-up">
            <div class="section-title">Daftar Item Checksheet</div>
            <div class="glass-card">
                <div class="item-row" style="border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    <div class="ocms-label" style="margin: 0;">ID</div>
                    <div class="ocms-label" style="margin: 0;">Grup / Section</div>
                    <div class="ocms-label" style="margin: 0;">Nama Item *</div>
                    <div class="ocms-label" style="margin: 0;">Aksi</div>
                </div>

                <div id="items-container">
                    @foreach(array_values(old('items', $template->items ?? [])) as $idx => $item)
                        <div class="item-row" data-item-row>
                            <input type="text" name="items[{{ $idx }}][id]" class="ocms-input mono" value="{{ $item['id'] ?? '' }}" placeholder="(auto)">
                            <input type="text" name="items[{{ $idx }}][group]" class="ocms-input" value="{{ $item['group'] ?? '' }}" placeholder="Umum" list="group-list">
                            <input type="text" name="items[{{ $idx }}][label]" class="ocms-input" value="{{ $item['label'] ?? '' }}" required placeholder="Nama item / part yang dicek">
                            <div style="display: flex; gap: 4px;">
                                <button type="button" class="item-move-btn" onclick="moveRow(this, -1)" title="Naik">▲</button>
                                <button type="button" class="item-move-btn" onclick="moveRow(this, 1)" title="Turun">▼</button>
                                <button type="button" class="item-del-btn" onclick="removeRow(this)" title="Hapus item">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <datalist id="group-list">
                    @foreach(collect($template->items ?? [])->pluck('group')->filter()->unique() as $group)
                        <option value="{{ $group }}"></option>
                    @endforeach
                </datalist>

                <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <button type="button" class="btn-secondary btn-sm" onclick="addRow()">+ Tambah Item</button>
                    <span style="font-size: 0.7rem; color: var(--text-muted);">Grup / Section bebas diketik — grup baru otomatis jadi filter &amp; judul section di checksheet.</span>
                </div>
            </div>
        </div>

        <div class="section fade-up">
            <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('dev.checksheet-templates.index') }}" class="btn-secondary">← Kembali</a>
                <button type="submit" class="btn-primary">💾 Simpan Template</button>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px; text-align: right;">
                Berlaku untuk komponen yang didaftarkan setelah disimpan — checksheet komponen lama tidak berubah.
            </p>
        </div>
    </form>

    {{-- Gambar referensi checksheet (form terpisah dari form item) --}}
    <div class="section fade-up">
        <div class="section-title">Gambar Referensi Checksheet</div>
        <div class="glass-card">
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 4px;">
                Gambar ini muncul di slide checksheet Receiving/Delivery sebagai acuan visual mekanik.
            </p>
            <p style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 16px;">
                Dua gaya, bebas dipilih (boleh dicampur):
                <strong>Semua item</strong> = satu gambar untuk seluruh checksheet (gaya Control Valve) ·
                <strong>Per grup</strong> = tiap grup/section punya gambarnya sendiri (gaya Engine).
                Jika keduanya ada, gambar grup yang menang untuk item di grup itu.
            </p>

            <form method="POST" action="{{ route('dev.checksheet-templates.image.upload', $template->id) }}" enctype="multipart/form-data"
                  style="margin-bottom: 20px; padding: 12px; border: 1px dashed rgba(var(--ink), 0.15); border-radius: 10px;">
                @csrf
                <div class="grid-4 stack-mobile" style="align-items: end;">
                    <div style="display: flex; flex-direction: column;">
                        <label class="ocms-label">EGI *</label>
                        <input type="text" name="egi" class="ocms-input" required
                            value="{{ old('egi', $template->egi_model) }}" placeholder="Contoh: HD785-7">
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <label class="ocms-label">Berlaku Untuk *</label>
                        <select name="group" class="ocms-select">
                            <option value="">🖼 Semua item (satu gambar)</option>
                            @foreach($refGroups as $group)
                                <option value="{{ $group }}">Hanya grup: {{ $group }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <label class="ocms-label">File Gambar *</label>
                        <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" required
                            style="font-size: 0.75rem; width: 100%; padding: 12px; background: var(--select-option-bg); color: var(--text-primary); border: 1px solid var(--glass-border-light); border-radius: 12px; height: 50px;">
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: flex-end;">
                        <button type="submit" class="btn-primary" style="width: auto; align-self: flex-start; height: 42px; justify-content: center; padding: 0 16px; font-size: 0.8rem; white-space: nowrap;">📤 Upload Gambar</button>
                    </div>
                </div>
                <p style="font-size: 0.65rem; color: var(--text-muted); margin-top: 8px; margin-bottom: 0;">
                    PNG/JPG/WebP, maks. 5 MB — upload dengan EGI + target yang sama akan menimpa gambar lama.
                </p>
            </form>

            @if(count($refImages) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;">
                @foreach($refImages as $img)
                <div style="border: 1px solid rgba(var(--ink), 0.08); border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.15);">
                    <a href="{{ $img['url'] }}" target="_blank" title="Buka ukuran penuh">
                        <img src="{{ $img['url'] }}" alt="Referensi {{ $img['egi'] }}"
                             style="display: block; width: 100%; height: 140px; object-fit: contain; background: rgba(var(--ink), 0.03);">
                    </a>
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px;">
                        <span class="mono" style="font-size: 0.68rem; color: var(--text-secondary);">
                            {{ strtoupper($img['egi']) }} · {{ $img['group'] ?? 'Semua item' }}
                        </span>
                        <form method="POST" action="{{ route('dev.checksheet-templates.image.delete', $template->id) }}" style="margin: 0;"
                              onsubmit="return confirm('Hapus gambar referensi {{ strtoupper($img['egi']) }} ({{ $img['group'] ?? 'semua item' }})?');">
                            @csrf @method('DELETE')
                            <input type="hidden" name="egi" value="{{ $img['egi'] }}">
                            <input type="hidden" name="group" value="{{ $img['group'] }}">
                            <button type="submit" class="btn-danger btn-sm">🗑</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color: var(--text-muted); font-size: 0.8rem; text-align: center; padding: 12px;">
                Belum ada gambar referensi untuk EGI template ini — checksheet tetap jalan, hanya tampil tanpa gambar.
            </p>
            @endif
        </div>
    </div>

    <script>
        const container = document.getElementById('items-container');
        // Index unik untuk baris baru — urutan submit tetap mengikuti urutan
        // DOM (PHP mempertahankan urutan field pada request body).
        let nextIdx = {{ count(old('items', $template->items ?? [])) + 1000 }};

        function refreshCount() {
            const n = container.querySelectorAll('[data-item-row]').length;
            document.getElementById('item-count-badge').textContent = n + ' item';
        }

        function addRow() {
            const i = nextIdx++;
            const row = document.createElement('div');
            row.className = 'item-row';
            row.setAttribute('data-item-row', '');
            row.innerHTML = `
                <input type="text" name="items[${i}][id]" class="ocms-input mono" placeholder="(auto)">
                <input type="text" name="items[${i}][group]" class="ocms-input" placeholder="Umum" list="group-list">
                <input type="text" name="items[${i}][label]" class="ocms-input" required placeholder="Nama item / part yang dicek">
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="item-move-btn" onclick="moveRow(this, -1)" title="Naik">▲</button>
                    <button type="button" class="item-move-btn" onclick="moveRow(this, 1)" title="Turun">▼</button>
                    <button type="button" class="item-del-btn" onclick="removeRow(this)" title="Hapus item">✕</button>
                </div>`;
            container.appendChild(row);
            row.querySelector('input[name$="[label]"]').focus();
            refreshCount();
        }

        function removeRow(btn) {
            btn.closest('[data-item-row]').remove();
            refreshCount();
        }

        function moveRow(btn, dir) {
            const row = btn.closest('[data-item-row]');
            if (dir < 0 && row.previousElementSibling?.hasAttribute('data-item-row')) {
                row.parentNode.insertBefore(row, row.previousElementSibling);
            } else if (dir > 0 && row.nextElementSibling) {
                row.parentNode.insertBefore(row.nextElementSibling, row);
            }
        }

        refreshCount();
    </script>

</x-app-layout>
