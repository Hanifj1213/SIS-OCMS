<section>
    <div class="section-title" style="margin-bottom: 8px; color: var(--accent-red);">⚠️ Hapus Akun</div>
    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 24px;">
        Setelah akun dihapus, semua data terkait akan hilang permanen. Masukkan kata sandi untuk konfirmasi.
    </p>

    @if($errors->userDeletion->isNotEmpty())
        <div class="alert alert-error" style="margin-bottom: 16px;">
            {{ $errors->userDeletion->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('profile.destroy') }}"
          onsubmit="return confirm('Yakin ingin menghapus akun Anda secara permanen? Tindakan ini tidak bisa dibatalkan.');"
          style="display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap;">
        @csrf
        @method('delete')

        <input name="password" type="password" class="ocms-input" placeholder="Kata sandi Anda" style="max-width: 300px;" required>
        <button type="submit" class="btn-danger">Hapus Akun</button>
    </form>
</section>
