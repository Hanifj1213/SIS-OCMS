<section>
    <div class="section-title" style="margin-bottom: 8px;">🔒 Ganti Kata Sandi</div>
    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 24px;">
        Gunakan kata sandi yang panjang dan acak agar akun tetap aman.
    </p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div style="margin-bottom: 16px;">
            <label class="ocms-label" for="update_password_current_password">Kata Sandi Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" class="ocms-input" autocomplete="current-password">
            @if($errors->updatePassword->has('current_password'))
                <p style="color: var(--accent-red); font-size: 0.75rem; margin-top: 6px;">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div style="margin-bottom: 16px;">
            <label class="ocms-label" for="update_password_password">Kata Sandi Baru</label>
            <input id="update_password_password" name="password" type="password" class="ocms-input" autocomplete="new-password">
            @if($errors->updatePassword->has('password'))
                <p style="color: var(--accent-red); font-size: 0.75rem; margin-top: 6px;">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div style="margin-bottom: 24px;">
            <label class="ocms-label" for="update_password_password_confirmation">Konfirmasi Kata Sandi Baru</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="ocms-input" autocomplete="new-password">
            @if($errors->updatePassword->has('password_confirmation'))
                <p style="color: var(--accent-red); font-size: 0.75rem; margin-top: 6px;">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <button type="submit" class="btn-primary">Perbarui Kata Sandi</button>
    </form>
</section>
