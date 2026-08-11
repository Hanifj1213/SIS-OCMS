<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Template Google Sheets</h1>
            <p>Mapping spreadsheet master per jenis × kategori × EGI — dipakai saat duplikasi checksheet komponen baru</p>
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

    {{-- Form tambah mapping baru --}}
    <div class="section fade-up">
        <div class="section-title">Tambah / Timpa Mapping</div>
        <div class="glass-card">
            <form method="POST" action="{{ route('dev.gsheet-templates.store') }}">
                @csrf
                <div class="grid-4 stack-mobile" style="align-items: end;">
                    <div>
                        <label class="ocms-label">Jenis (Kind) *</label>
                        <select name="kind" class="ocms-select" required>
                            @foreach($kinds as $kind)
                                <option value="{{ $kind }}" {{ old('kind') == $kind ? 'selected' : '' }}>{{ $kindLabels[$kind] ?? $kind }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ocms-label">Kategori Komponen</label>
                        <input type="text" name="major_category" class="ocms-input" list="category-list"
                            value="{{ old('major_category') }}" placeholder="Contoh: TC/Transmission">
                        <datalist id="category-list">
                            @foreach($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="ocms-label">EGI</label>
                        <input type="text" name="egi" class="ocms-input" value="{{ old('egi') }}"
                            placeholder="Contoh: HD785-7">
                    </div>
                    <div>
                        <label class="ocms-label">Link / ID Spreadsheet *</label>
                        <input type="text" name="spreadsheet" class="ocms-input" value="{{ old('spreadsheet') }}"
                            required placeholder="https://docs.google.com/spreadsheets/d/...">
                    </div>
                </div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px; margin-bottom: 0;">
                    💡 Kategori bebas diketik — kategori baru otomatis muncul di form registrasi komponen.
                    Kosongkan Kategori &amp; EGI untuk menjadikan spreadsheet ini <em>default</em> jenis tersebut (seperti SDR).
                    Jika kombinasi sudah ada, mapping lama akan ditimpa.
                </p>
                <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                    <button type="submit" class="btn-primary">💾 Simpan Mapping</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar mapping per kind --}}
    @foreach($kinds as $kind)
        @php $rows = $templatesByKind->get($kind, collect()); @endphp
        <div class="section fade-up">
            <div class="section-title">{{ $kindLabels[$kind] ?? $kind }} <span style="color: var(--text-muted); text-transform: none; letter-spacing: 0;">— {{ $rows->count() }} mapping</span></div>
            <div class="glass-card table-scroll" style="padding: 0;">
                <table class="ocms-table">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Kategori</th>
                            <th style="width: 12%;">EGI</th>
                            <th>Spreadsheet</th>
                            <th style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->major_category ?? '(default semua kategori)' }}</td>
                            <td class="mono">{{ $row->egi ?? '—' }}</td>
                            <td>
                                <a href="https://docs.google.com/spreadsheets/d/{{ $row->spreadsheet_id }}" target="_blank" rel="noopener"
                                   class="mono" style="color: var(--accent-cyan); font-size: 0.75rem; text-decoration: none; word-break: break-all;">
                                    {{ $row->spreadsheet_id }} ↗
                                </a>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <form method="POST" action="{{ route('dev.gsheet-templates.update', $row->id) }}" style="margin: 0; display: flex; gap: 6px;">
                                        @csrf @method('PATCH')
                                        <input type="text" name="spreadsheet" class="ocms-input mono" placeholder="Link/ID baru..."
                                            style="padding: 6px 10px; font-size: 0.7rem; width: 150px;" required>
                                        <button type="submit" class="btn-secondary btn-sm" title="Ganti spreadsheet">💾</button>
                                    </form>
                                    <form method="POST" action="{{ route('dev.gsheet-templates.destroy', $row->id) }}" style="margin: 0;"
                                        onsubmit="return confirm('Hapus mapping {{ $kind }} {{ $row->major_category }} {{ $row->egi }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm" title="Hapus mapping">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada mapping untuk jenis ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="section fade-up">
        <a href="{{ route('dev.index') }}" class="btn-secondary">← Kembali ke Panel Developer</a>
    </div>

</x-app-layout>
