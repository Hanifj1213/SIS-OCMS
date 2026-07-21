<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Tambah User Baru</h1>
            <p>Buat akun baru untuk karyawan PT Saptaindra Sejati</p>
        </div>
    </div>

    <div class="glass-card fade-up" style="max-width: 640px;">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div style="margin-bottom: 20px;">
                <label for="name" class="ocms-label">Nama Lengkap</label>
                <input id="name" class="ocms-input" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Contoh: Budi Santoso">
                @error('name') <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="nik" class="ocms-label">NIK / Username</label>
                <input id="nik" class="ocms-input" type="text" name="nik" value="{{ old('nik') }}" required placeholder="Contoh: ME002" style="font-family: 'JetBrains Mono', monospace;">
                @error('nik') <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="role" class="ocms-label">Role / Jabatan</label>
                <select id="role" name="role" class="ocms-select" required>
                    <option value="">— Pilih Role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                <p style="font-size: 0.72rem; color: var(--text-muted); margin-top: 8px; line-height: 1.5;">
                    @foreach($roleDescriptions as $roleName => $desc)
                        <strong>{{ $roleName }}:</strong> {{ $desc }}<br>
                    @endforeach
                </p>
                @error('role') <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px;">
                <div>
                    <label for="password" class="ocms-label">Password</label>
                    <input id="password" class="ocms-input" type="password" name="password" required>
                    @error('password') <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="ocms-label">Konfirmasi Password</label>
                    <input id="password_confirmation" class="ocms-input" type="password" name="password_confirmation" required>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Daftarkan User</button>
            </div>
        </form>
    </div>

</x-app-layout>
