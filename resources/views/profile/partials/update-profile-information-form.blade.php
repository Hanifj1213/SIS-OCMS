<section>
    <div class="section-title" style="margin-bottom: 8px;">👤 Informasi Profil</div>
    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 24px;">
        Perbarui nama dan NIK akun Anda. NIK digunakan untuk login.
    </p>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div style="margin-bottom: 16px;">
            <label class="ocms-label" for="name">Nama Lengkap</label>
            <input id="name" name="name" type="text" class="ocms-input" value="{{ old('name', $user->name) }}" required autocomplete="name">
            @error('name')
                <p style="color: var(--accent-red); font-size: 0.75rem; margin-top: 6px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 24px;">
            <label class="ocms-label" for="nik">NIK</label>
            <input id="nik" name="nik" type="text" class="ocms-input" style="font-family: 'JetBrains Mono', monospace;" value="{{ old('nik', $user->nik) }}" required autocomplete="username">
            @error('nik')
                <p style="color: var(--accent-red); font-size: 0.75rem; margin-top: 6px;">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary">Simpan Perubahan</button>
    </form>
</section>
