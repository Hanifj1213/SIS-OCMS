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

    <form method="POST" action="{{ route('components.store') }}">
        @csrf

        {{-- Section 1: Data Unit --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-cyan-dim); color: var(--accent-cyan); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">1</span>
                Data Unit
            </div>
            <div class="glass-card fade-up">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="egi" class="ocms-label">EGI <span style="color: var(--accent-red);">*</span></label>
                        {{-- Text input (non-Engine or fallback) --}}
                        <input id="egi_text" class="ocms-input" type="text" name="egi" value="{{ old('egi') }}" required
                            placeholder="Contoh: D155A-6" style="font-family: 'JetBrains Mono', monospace;">
                        {{-- Dropdown (For components with specific templates) --}}
                        <select id="egi_select" class="ocms-select" style="display: none; font-family: 'JetBrains Mono', monospace;">
                            <option value="">— Pilih Model EGI —</option>
                            <!-- Options will be populated by JS -->
                        </select>
                        {{-- Custom EGI input (saat pilih "Lainnya") --}}
                        <input id="egi_custom" class="ocms-input" type="text" value="{{ old('egi_custom') }}"
                            placeholder="Ketik model EGI manual..." style="display: none; margin-top: 8px; font-family: 'JetBrains Mono', monospace;">
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
                        <input id="unit_code" class="ocms-input" type="text" name="unit_code"
                            value="{{ old('unit_code') }}" required placeholder="Contoh: DZ040-0037"
                            style="font-family: 'JetBrains Mono', monospace;">
                        @error('unit_code')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit_serial_no" class="ocms-label">Unit Serial No.</label>
                        <input id="unit_serial_no" class="ocms-input" type="text" name="unit_serial_no"
                            value="{{ old('unit_serial_no') }}" placeholder="Contoh: 80588"
                            style="font-family: 'JetBrains Mono', monospace;">
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

        {{-- Section 2: Data Komponen --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-gold-dim); color: var(--accent-gold); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">2</span>
                Data Komponen
            </div>
            <div class="glass-card fade-up">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="major_category" class="ocms-label">Component Model <span
                                style="color: var(--accent-red);">*</span></label>
                        <select id="major_category" name="major_category" class="ocms-select" required>
                            <option value="">— Pilih Component Model —</option>
                            <option value="Engine" {{ old('major_category') == 'Engine' ? 'selected' : '' }}>🔧 Engine
                            </option>
                            <option value="TC/Transmission" {{ old('major_category') == 'TC/Transmission' ? 'selected' : '' }}>⚙️ TC / Transmission</option>
                            <option value="Differential" {{ old('major_category') == 'Differential' ? 'selected' : '' }}>
                                🔩 Differential</option>
                            <option value="Final Drive" {{ old('major_category') == 'Final Drive' ? 'selected' : '' }}>🏗️
                                Final Drive</option>
                            <option value="PTO" {{ old('major_category') == 'PTO' ? 'selected' : '' }}>🔄 PTO</option>
                            <option value="Control Valve" {{ old('major_category') == 'Control Valve' ? 'selected' : '' }}>🎛️ Control Valve</option>
                            <option value="Hydraulic Pump" {{ old('major_category') == 'Hydraulic Pump' ? 'selected' : '' }}>💧 Hydraulic Pump</option>
                            <option value="Travel Motor" {{ old('major_category') == 'Travel Motor' ? 'selected' : '' }}>
                                🚜 Travel Motor</option>
                            <option value="Swing Motor" {{ old('major_category') == 'Swing Motor' ? 'selected' : '' }}>🔁
                                Swing Motor</option>
                            <option value="Swing Machinery" {{ old('major_category') == 'Swing Machinery' ? 'selected' : '' }}>🏭 Swing Machinery</option>
                            <option value="Hydraulic Cylinder" {{ old('major_category') == 'Hydraulic Cylinder' ? 'selected' : '' }}>🛢️ Hydraulic Cylinder</option>
                            <option value="Front Suspension" {{ old('major_category') == 'Front Suspension' ? 'selected' : '' }}>🛞 Front Suspension</option>
                            <option value="Rear Suspension" {{ old('major_category') == 'Rear Suspension' ? 'selected' : '' }}>🛞 Rear Suspension</option>
                        </select>
                        @error('major_category')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="serial_number" class="ocms-label">Comp Serial No. <span
                                style="color: var(--accent-red);">*</span></label>
                        <input id="serial_number" class="ocms-input" type="text" name="serial_number"
                            value="{{ old('serial_number') }}" required placeholder="Contoh: VJ-634870"
                            style="font-family: 'JetBrains Mono', monospace;">
                        @error('serial_number')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="pn_assy" class="ocms-label">P/N Assy</label>
                        <input id="pn_assy" class="ocms-input" type="text" name="pn_assy" value="{{ old('pn_assy') }}"
                            placeholder="Contoh: 626A-G0-0040" style="font-family: 'JetBrains Mono', monospace;">
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

        {{-- Section 3: Informasi Operasional --}}
        <div class="section">
            <div class="section-title fade-up" style="display: flex; align-items: center; gap: 10px;">
                <span
                    style="background: var(--accent-green-dim); color: var(--accent-green); width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">3</span>
                Informasi Operasional
            </div>
            <div class="glass-card fade-up">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="smr" class="ocms-label">SMR</label>
                        <input id="smr" class="ocms-input" type="number" name="smr" value="{{ old('smr') }}" min="0"
                            placeholder="Contoh: 44088" style="font-family: 'JetBrains Mono', monospace;">
                        @error('smr')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="life_time" class="ocms-label">Life Time</label>
                        <input id="life_time" class="ocms-input" type="number" name="life_time"
                            value="{{ old('life_time') }}" min="0" placeholder="Contoh: 20067"
                            style="font-family: 'JetBrains Mono', monospace;">
                        @error('life_time')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="manifest" class="ocms-label">Manifest</label>
                        <input id="manifest" class="ocms-input" type="text" name="manifest"
                            value="{{ old('manifest') }}" placeholder="Nomor manifest pengiriman">
                        @error('manifest')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="way_bill" class="ocms-label">Way Bill</label>
                        <input id="way_bill" class="ocms-input" type="text" name="way_bill"
                            value="{{ old('way_bill') }}" placeholder="Nomor way bill">
                        @error('way_bill')
                            <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="section fade-up">
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
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