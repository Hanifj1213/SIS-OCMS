<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Profil Saya</h1>
            <p>Kelola informasi akun dan kata sandi Anda</p>
        </div>
    </div>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success fade-up">✅ Profil berhasil diperbarui.</div>
    @endif
    @if(session('status') === 'password-updated')
        <div class="alert alert-success fade-up">✅ Kata sandi berhasil diperbarui.</div>
    @endif

    <div class="section grid-2" style="align-items: start;">
        <div class="glass-card fade-up">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="glass-card fade-up">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="section">
        <div class="glass-card fade-up" style="border-color: rgba(248,113,113,0.15);">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</x-app-layout>
