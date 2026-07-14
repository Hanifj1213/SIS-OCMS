<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Pendaftaran Komponen Baru</h1>
            <p>Registrasikan komponen baru yang masuk ke Plant Rebuild Centre (PRC)</p>
        </div>
    </div>

    <div class="glass-card fade-up" style="max-width: 640px;">
        <form method="POST" action="{{ route('components.store') }}">
            @csrf

            <div style="margin-bottom: 24px;">
                <label for="serial_number" class="ocms-label">Serial Number / ID Komponen</label>
                <input id="serial_number" class="ocms-input" type="text" name="serial_number" value="{{ old('serial_number') }}" required autofocus placeholder="Contoh: ENG-CAT789D-001" style="font-family: 'JetBrains Mono', monospace;">
                @error('serial_number')
                    <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="major_category" class="ocms-label">Kategori Major Component</label>
                <select id="major_category" name="major_category" class="ocms-select" required>
                    <option value="">— Pilih Kategori —</option>
                    <option value="Engine" {{ old('major_category') == 'Engine' ? 'selected' : '' }}>🔧 Engine</option>
                    <option value="TC/Transmission" {{ old('major_category') == 'TC/Transmission' ? 'selected' : '' }}>⚙️ TC / Transmission</option>
                    <option value="Differential" {{ old('major_category') == 'Differential' ? 'selected' : '' }}>🔩 Differential</option>
                    <option value="Final Drive" {{ old('major_category') == 'Final Drive' ? 'selected' : '' }}>🏗️ Final Drive</option>
                    <option value="PTO" {{ old('major_category') == 'PTO' ? 'selected' : '' }}>🔄 PTO</option>
                    <option value="Control Valve" {{ old('major_category') == 'Control Valve' ? 'selected' : '' }}>🎛️ Control Valve</option>
                    <option value="Hydraulic Pump" {{ old('major_category') == 'Hydraulic Pump' ? 'selected' : '' }}>💧 Hydraulic Pump</option>
                    <option value="Travel Motor" {{ old('major_category') == 'Travel Motor' ? 'selected' : '' }}>🚜 Travel Motor</option>
                    <option value="Swing Motor" {{ old('major_category') == 'Swing Motor' ? 'selected' : '' }}>🔁 Swing Motor</option>
                    <option value="Swing Machinery" {{ old('major_category') == 'Swing Machinery' ? 'selected' : '' }}>🏭 Swing Machinery</option>
                    <option value="Hydraulic Cylinder" {{ old('major_category') == 'Hydraulic Cylinder' ? 'selected' : '' }}>🛢️ Hydraulic Cylinder</option>
                </select>
                @error('major_category')
                    <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 32px;">
                <label for="model_type" class="ocms-label">Model Alat (Contoh: Cat 789D — Engine)</label>
                <input id="model_type" class="ocms-input" type="text" name="model_type" value="{{ old('model_type') }}" required placeholder="Contoh: Komatsu PC2000 - Hydraulic Pump">
                @error('model_type')
                    <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('components.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Daftarkan & Generate QR</button>
            </div>
        </form>
    </div>

</x-app-layout>
