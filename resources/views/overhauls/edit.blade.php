<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Edit Komponen</h1>
            <p>{{ $comp->serial_number }} — {{ $comp->major_category }} {{ $comp->egi }}</p>
        </div>
    </div>

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

    <style>
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .form-mono { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }
        .form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }
        @media (max-width: 768px) {
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; gap: 16px; }
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
            .form-actions .btn-primary, .form-actions .btn-secondary { width: 100%; justify-content: center; }
        }
    </style>

    <form method="POST" action="{{ route('components.update', $comp->comp_id) }}">
        @csrf @method('PUT')

        {{-- Section 1: Data Komponen --}}
        <div class="section">
            <div class="section-title fade-up">1 · Data Komponen</div>
            <div class="glass-card fade-up">
                <div class="form-grid-2">
                    <div>
                        <label for="major_category" class="ocms-label">Component Model *</label>
                        <select id="major_category" name="major_category" class="ocms-select" required>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('major_category', $comp->major_category) == $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="serial_number" class="ocms-label">Comp Serial No. *</label>
                        <input id="serial_number" class="ocms-input form-mono" type="text" name="serial_number"
                            value="{{ old('serial_number', $comp->serial_number) }}" required>
                    </div>
                    <div>
                        <label for="pn_assy" class="ocms-label">P/N Assy</label>
                        <input id="pn_assy" class="ocms-input form-mono" type="text" name="pn_assy"
                            value="{{ old('pn_assy', $comp->pn_assy) }}">
                    </div>
                    <div>
                        <label for="status_ovh" class="ocms-label">Status OVH *</label>
                        <select id="status_ovh" name="status_ovh" class="ocms-select" required>
                            <option value="SCHEDULE" {{ old('status_ovh', $comp->status_ovh) == 'SCHEDULE' ? 'selected' : '' }}>📅 SCHEDULE</option>
                            <option value="UNSCHEDULE" {{ old('status_ovh', $comp->status_ovh) == 'UNSCHEDULE' ? 'selected' : '' }}>⚠️ UNSCHEDULE</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(var(--ink), 0.05);">
                    <label class="ocms-label" style="margin-bottom: 12px;">Core Category</label>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        @foreach(['A', 'B', 'C'] as $cat)
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="core_category" value="{{ $cat }}"
                                    {{ old('core_category', $comp->core_category) == $cat ? 'checked' : '' }}
                                    style="width: 18px; height: 18px;">
                                <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">{{ $cat }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Data Unit --}}
        <div class="section">
            <div class="section-title fade-up">2 · Data Unit</div>
            <div class="glass-card fade-up">
                <div class="form-grid-2">
                    <div>
                        <label for="egi" class="ocms-label">EGI *</label>
                        <input id="egi" class="ocms-input form-mono" type="text" name="egi"
                            value="{{ old('egi', $comp->egi) }}" required>
                    </div>
                    <div>
                        <label for="unit_code" class="ocms-label">Unit Code *</label>
                        <input id="unit_code" class="ocms-input form-mono" type="text" name="unit_code"
                            value="{{ old('unit_code', $comp->unit_code) }}" required>
                    </div>
                    <div>
                        <label for="unit_serial_no" class="ocms-label">Unit Serial No.</label>
                        <input id="unit_serial_no" class="ocms-input form-mono" type="text" name="unit_serial_no"
                            value="{{ old('unit_serial_no', $comp->unit_serial_no) }}">
                    </div>
                    <div>
                        <label for="site_district" class="ocms-label">Site / District *</label>
                        <input id="site_district" class="ocms-input" type="text" name="site_district"
                            value="{{ old('site_district', $comp->site_district) }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Operasional & Logistik --}}
        <div class="section">
            <div class="section-title fade-up">3 · Operasional &amp; Logistik</div>
            <div class="glass-card fade-up">
                <div class="form-grid-3">
                    <div>
                        <label for="smr" class="ocms-label">SMR</label>
                        <input id="smr" class="ocms-input form-mono" type="number" name="smr" min="0"
                            value="{{ old('smr', $comp->smr) }}">
                    </div>
                    <div>
                        <label for="life_time" class="ocms-label">Life Time</label>
                        <input id="life_time" class="ocms-input form-mono" type="number" name="life_time" min="0"
                            value="{{ old('life_time', $comp->life_time) }}">
                    </div>
                    <div>
                        <label for="date_defitted" class="ocms-label">Date Received</label>
                        <input id="date_defitted" class="ocms-input" type="date" name="date_defitted"
                            value="{{ old('date_defitted', $comp->date_defitted?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="manifest" class="ocms-label">Manifest / Way Bill</label>
                        <input id="manifest" class="ocms-input" type="text" name="manifest"
                            value="{{ old('manifest', $comp->manifest) }}">
                    </div>
                    <div>
                        <label for="ro_number" class="ocms-label">RO</label>
                        <input id="ro_number" class="ocms-input" type="text" name="ro_number"
                            value="{{ old('ro_number', $comp->ro_number) }}">
                    </div>
                    <div>
                        <label for="date_delivery" class="ocms-label">Date Delivery</label>
                        <input id="date_delivery" class="ocms-input" type="date" name="date_delivery"
                            value="{{ old('date_delivery', $comp->date_delivery?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Link Google Sheets --}}
        <div class="section">
            <div class="section-title fade-up">4 · Link Google Sheets</div>
            <div class="glass-card fade-up">
                <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 18px;">
                    Kosongkan sebuah link agar sistem menduplikasi ulang dari template master
                    (berjalan otomatis saat halaman detail komponen dibuka, bila template tersedia).
                </p>
                <div class="form-grid-2">
                    @foreach([
                        'gsheet_url' => 'Disassembly',
                        'gsheet_measurement_url' => 'Measurement / Inspection',
                        'gsheet_subassy_disassembly_url' => 'Sub-Assy Disassembly',
                        'gsheet_subassy_measurement_url' => 'Sub-Assy Measurement',
                        'gsheet_sdr_url' => 'SDR',
                        'gsheet_assembly_url' => 'Assembly (Stage 4)',
                        'gsheet_testbench_url' => 'Test Bench (Stage 5)',
                    ] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="ocms-label">{{ $label }}</label>
                            <input id="{{ $field }}" class="ocms-input form-mono" type="url" name="{{ $field }}"
                                value="{{ old($field, $comp->{$field}) }}" placeholder="https://docs.google.com/spreadsheets/d/...">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="section fade-up">
            <div class="form-actions">
                <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary">← Batal</a>
                <button type="submit" class="btn-primary">💾 Simpan Perubahan</button>
            </div>
        </div>

    </form>

</x-app-layout>
