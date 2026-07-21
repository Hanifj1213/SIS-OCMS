<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Pendaftaran Komponen Baru</h1>
            <p>Registrasikan Damage Core baru yang masuk ke Plant Rebuild Centre (PRC)</p>
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
            .form-grid-2,
            .form-grid-3 { grid-template-columns: 1fr; gap: 16px; }
            .form-grid-ops {
                grid-template-columns: 1fr 1fr !important;
                gap: 12px !important;
            }
            .form-grid-ops .form-span-full { grid-column: 1 / -1; }
            .form-mono,
            .form-mono.ocms-select { font-size: 0.78rem; }
            .ocms-input,
            .ocms-select { padding: 12px 14px; font-size: 0.85rem; }
            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }
            .form-actions .btn-primary,
            .form-actions .btn-secondary {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
        }
    </style>

    <form method="POST" action="{{ route('components.store') }}">
        @csrf

        {{-- Section 1: Data Komponen --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-gold-dim); color: var(--accent-gold); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">1</span>
                Data Komponen
            </div>
            <div class="glass-card fade-up">
                <div class="form-grid-2">
                    <div>
                        <label for="major_category" class="ocms-label">Component Model <span
                                style="color: var(--accent-red);">*</span></label>
                        @php
                            // Ikon untuk kategori bawaan; kategori baru dari
                            // panel Developer tampil tanpa ikon.
                            $categoryIcons = [
                                'Engine' => '🔧', 'TC/Transmission' => '⚙️', 'Differential' => '🔩',
                                'Final Drive' => '🏗️', 'PTO' => '🔄', 'Control Valve' => '🎛️',
                                'Hydraulic Pump' => '💧', 'Travel Motor' => '🚜', 'Swing Motor' => '🔁',
                                'Swing Machinery' => '🏭', 'Hydraulic Cylinder' => '🛢️',
                                'Front Suspension' => '🛞', 'Rear Suspension' => '🛞',
                            ];
                            $categoryList = $categories ?? array_keys($categoryIcons);
                        @endphp
                        <select id="major_category" name="major_category" class="ocms-select" required>
                            <option value="">— Pilih Component Model —</option>
                            @foreach($categoryList as $category)
                                <option value="{{ $category }}" {{ old('major_category') == $category ? 'selected' : '' }}>{{ trim(($categoryIcons[$category] ?? '') . ' ' . $category) }}</option>
                            @endforeach
                        </select>
                        @error('major_category')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="serial_number" class="ocms-label">Comp Serial No. <span
                                style="color: var(--accent-red);">*</span></label>
                        <input id="serial_number" class="ocms-input form-mono" type="text" name="serial_number"
                            value="{{ old('serial_number') }}" required placeholder="Contoh: VJ-634870">
                        @error('serial_number')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="pn_assy" class="ocms-label">P/N Assy</label>
                        <input id="pn_assy" class="ocms-input form-mono" type="text" name="pn_assy" value="{{ old('pn_assy') }}"
                            placeholder="Contoh: 626A-G0-0040">
                        @error('pn_assy')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="status_ovh" class="ocms-label">Status OVH <span
                                style="color: var(--accent-red);">*</span></label>
                        <select id="status_ovh" name="status_ovh" class="ocms-select" required>
                            <option value="">— Pilih Status —</option>
                            <option value="SCHEDULE" {{ old('status_ovh') == 'SCHEDULE' ? 'selected' : '' }}>📅 SCHEDULE
                            </option>
                            <option value="UNSCHEDULE" {{ old('status_ovh') == 'UNSCHEDULE' ? 'selected' : '' }}>⚠️
                                UNSCHEDULE</option>
                        </select>
                        @error('status_ovh')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Core Category --}}
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(var(--ink), 0.05);">
                    <label class="ocms-label" style="font-size: 0.85rem; margin-bottom: 12px;">Evaluasi Core Category :</label>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="core_category" value="A" {{ old('core_category') == 'A' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent-cyan);">
                            <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">A</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="core_category" value="B" {{ old('core_category') == 'B' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent-gold);">
                            <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">B</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="core_category" value="C" {{ old('core_category') == 'C' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent-red);">
                            <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">C</span>
                        </label>
                    </div>
                    <div style="margin-top: 16px; font-size: 0.75rem; color: var(--text-muted); line-height: 1.6;">
                        <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; font-style: italic;">Note :</div>
                        <div><strong style="color: var(--text-primary);">A</strong> : Kondisi komponen running, lengkap (Schedule Overhaul)</div>
                        <div><strong style="color: var(--text-primary);">B</strong> : Kondisi Main Shaft Jammed, Gear broken (Unschedule Overhaul)</div>
                        <div><strong style="color: var(--text-primary);">C</strong> : Kondisi Housing Jebol / Broken (Unschedule Overhaul)</div>
                    </div>
                    @error('core_category')
                        <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Data Unit --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-cyan-dim); color: var(--accent-cyan); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">2</span>
                Data Unit
            </div>
            <div class="glass-card fade-up">
                <div class="form-grid-2">
                    <div>
                        <label for="egi" class="ocms-label">EGI <span style="color: var(--accent-red);">*</span></label>
                        {{-- Text input (non-Engine or fallback) --}}
                        <input id="egi_text" class="ocms-input form-mono" type="text" name="egi" value="{{ old('egi') }}" required
                            placeholder="Contoh: D155A-6">
                        {{-- Dropdown (For components with specific templates) --}}
                        <select id="egi_select" class="ocms-select form-mono" style="display: none;">
                            <option value="">— Pilih Model EGI —</option>
                            <!-- Options will be populated by JS -->
                        </select>
                        {{-- Custom EGI input (saat pilih "Lainnya") --}}
                        <input id="egi_custom" class="ocms-input form-mono" type="text" value="{{ old('egi_custom') }}"
                            placeholder="Ketik model EGI manual..." style="display: none; margin-top: 8px;">
                        <p id="egi_hint" style="display: none; font-size: 0.7rem; color: var(--accent-gold); margin-top: 6px; font-style: italic;">
                            ⚡ Model ini punya checksheet khusus dengan item inspeksi spesifik
                        </p>
                        @error('egi')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit_code" class="ocms-label">Unit Code <span
                                style="color: var(--accent-red);">*</span></label>
                        <input id="unit_code" class="ocms-input form-mono" type="text" name="unit_code"
                            value="{{ old('unit_code') }}" required placeholder="Contoh: DZ040-0037">
                        @error('unit_code')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit_serial_no" class="ocms-label">Unit Serial No.</label>
                        <input id="unit_serial_no" class="ocms-input form-mono" type="text" name="unit_serial_no"
                            value="{{ old('unit_serial_no') }}" placeholder="Contoh: 80588">
                        @error('unit_serial_no')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="site_district" class="ocms-label">Site / District <span
                                style="color: var(--accent-red);">*</span></label>
                        <input id="site_district" class="ocms-input" type="text" name="site_district"
                            value="{{ old('site_district') }}" required placeholder="Contoh: SIS ADMO">
                        @error('site_district')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Informasi Operasional --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-green-dim); color: var(--accent-green); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">3</span>
                Informasi Operasional
            </div>
            <div class="glass-card fade-up">
                <div class="form-grid-3 form-grid-ops">
                    <div>
                        <label for="smr" class="ocms-label">SMR</label>
                        <input id="smr" class="ocms-input form-mono" type="number" name="smr" value="{{ old('smr') }}" min="0"
                            placeholder="44088">
                        @error('smr')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="life_time" class="ocms-label">Life Time</label>
                        <input id="life_time" class="ocms-input form-mono" type="number" name="life_time"
                            value="{{ old('life_time') }}" min="0" placeholder="20067">
                        @error('life_time')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-span-full">
                        <label for="date_defitted" class="ocms-label">Date Received</label>
                        <input id="date_defitted" class="ocms-input" type="date" name="date_defitted"
                            value="{{ old('date_defitted') }}">
                        @error('date_defitted')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Logistik --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-purple-dim); color: var(--accent-purple); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">4</span>
                Data Logistik
            </div>
            <div class="glass-card fade-up">
                <div class="form-grid-2">
                    <div>
                        <label for="manifest" class="ocms-label">Manifest / Way Bill</label>
                        <input id="manifest" class="ocms-input" type="text" name="manifest"
                            value="{{ old('manifest') }}" placeholder="Nomor manifest / way bill pengiriman">
                        @error('manifest')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="ro_number" class="ocms-label">RO</label>
                        <input id="ro_number" class="ocms-input" type="text" name="ro_number"
                            value="{{ old('ro_number') }}" placeholder="Nomor RO">
                        @error('ro_number')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="section fade-up">
            <div class="form-actions">
                <a href="{{ route('components.index') }}" class="btn-secondary">← Batal</a>
                <button type="submit" class="btn-primary">📋 Daftarkan & Generate QR</button>
            </div>
        </div>

    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('major_category');
        const egiText = document.getElementById('egi_text');
        const egiSelect = document.getElementById('egi_select');
        const egiCustom = document.getElementById('egi_custom');
        const egiHint = document.getElementById('egi_hint');

        const oldEgi = "{{ old('egi') }}";

        // EGI per kategori — diselaraskan dengan checksheet COMPLETED Powertrain
        // + template Engine yang sudah ada.
        const templateMap = {
            'Engine': ['PC2000-8', 'PC1250-8', 'D375-6', 'D155-6', 'WA800-3', 'GD825A-2', 'HD785-7', 'HD465-7R'],
            'TC/Transmission': ['HD785-7', 'D155-6', 'D375-6', 'GD825A-2', 'HD1500-7', 'WA800-3'],
            'Final Drive': ['HD785-7', 'D155-6', 'D375-6', 'GD825A-2', 'PC1250-8', 'PC2000-8'],
            'Differential': ['HD785-7'],
            'PTO': ['PC1250-8', 'PC2000-8'],
            'Swing Machinery': ['PC1250-8', 'PC2000-8'],
            'Control Valve': ['PC1250-8', 'PC2000-8', 'D155-6', 'D375-6', 'HD785-7', 'GD825A-2', 'WA800-3'],
            'Hydraulic Cylinder': ['HD785-7'],
            'Front Suspension': ['HD785-7'],
            'Rear Suspension': ['HD785-7']
        };

        function populateEgiSelect(category) {
            // Keep the first option
            egiSelect.innerHTML = '<option value="">— Pilih Model EGI —</option>';
            
            if (templateMap[category]) {
                templateMap[category].forEach(model => {
                    const opt = document.createElement('option');
                    opt.value = model;
                    opt.textContent = model;
                    if (oldEgi === model) opt.selected = true;
                    egiSelect.appendChild(opt);
                });
            }
            
            // Add 'Other' option
            const otherOpt = document.createElement('option');
            otherOpt.value = '__OTHER__';
            otherOpt.textContent = 'Lainnya (ketik manual)';
            egiSelect.appendChild(otherOpt);
        }

        function toggleEgiMode() {
            const category = categorySelect.value;
            const hasTemplates = templateMap.hasOwnProperty(category);

            if (hasTemplates) {
                // Category has templates: show dropdown, hide text input
                egiText.style.display = 'none';
                egiText.removeAttribute('name');
                egiText.removeAttribute('required');

                populateEgiSelect(category);
                egiSelect.style.display = '';
                
                handleEgiSelectChange();
            } else {
                // No templates for this category: show text input, hide dropdown & custom
                egiText.style.display = '';
                egiText.setAttribute('name', 'egi');
                egiText.setAttribute('required', 'required');

                egiSelect.style.display = 'none';
                egiCustom.style.display = 'none';
                egiCustom.removeAttribute('name');
                egiCustom.removeAttribute('required');
                egiHint.style.display = 'none';
            }
        }

        function handleEgiSelectChange() {
            const val = egiSelect.value;

            if (val === '__OTHER__') {
                // Show custom input
                egiCustom.style.display = '';
                egiCustom.setAttribute('name', 'egi');
                egiCustom.setAttribute('required', 'required');
                egiSelect.removeAttribute('name');
                egiHint.style.display = 'none';
            } else {
                // Selected a known model
                egiCustom.style.display = 'none';
                egiCustom.removeAttribute('name');
                egiCustom.removeAttribute('required');
                egiSelect.setAttribute('name', 'egi');

                if (val) {
                    egiSelect.setAttribute('required', 'required');
                    egiHint.style.display = '';
                } else {
                    egiSelect.setAttribute('required', 'required');
                    egiHint.style.display = 'none';
                }
            }
        }

        categorySelect.addEventListener('change', function() {
            toggleEgiMode();
            // Reset selection when category changes
            if (templateMap.hasOwnProperty(categorySelect.value)) {
                egiSelect.value = '';
                handleEgiSelectChange();
            }
        });
        
        egiSelect.addEventListener('change', handleEgiSelectChange);

        // Initialize on page load (handles old() values)
        toggleEgiMode();
        
        // Handle case where oldEgi was custom text while in a templated category
        if (templateMap.hasOwnProperty(categorySelect.value) && oldEgi) {
            if (!templateMap[categorySelect.value].includes(oldEgi)) {
                egiSelect.value = '__OTHER__';
                egiCustom.value = oldEgi;
                handleEgiSelectChange();
            }
        }
    });
    </script>

</x-app-layout>