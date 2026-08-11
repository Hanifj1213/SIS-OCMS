<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Ganti Password</h1>
            <p>Setel ulang password untuk <strong>{{ $user->name }}</strong> ({{ $user->nik }})</p>
        </div>
    </div>

    <div class="glass-card fade-up" style="max-width: 640px;">
        <form method="POST" action="{{ route('admin.users.password.update', $user->id) }}">
            @csrf
            @method('PATCH')

            <div style="margin-bottom: 20px;">
                <label class="ocms-label">User</label>
                <div style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;">
                    <span style="font-weight: 700;">{{ $user->name }}</span>
                    <span class="mono" style="color: var(--text-muted); font-size: 0.8rem;">{{ $user->nik }}</span>
                    @foreach($user->roles as $role)
                        <span class="badge badge-purple">{{ $role->name }}</span>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px;">
                <div>
                    <label for="password" class="ocms-label">Password Baru</label>
                    <input id="password" class="ocms-input" type="password" name="password" required autofocus autocomplete="new-password">
                    @error('password') <p style="font-size: 0.75rem; color: var(--accent-red); margin-top: 6px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="ocms-label">Konfirmasi Password</label>
                    <input id="password_confirmation" class="ocms-input" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan Password Baru</button>
            </div>
        </form>
    </div>

</x-app-layout>
