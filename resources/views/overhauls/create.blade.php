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
