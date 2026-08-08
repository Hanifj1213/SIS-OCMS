    <!-- ==================== CTA / LOGIN SECTION ==================== -->
    <section class="cta-section" id="access">
        <div class="cta-glow"></div>
        <div class="container">
            <div class="cta-content">
                <div class="section-label reveal">Akses Sistem</div>
                <h2 class="cta-headline reveal">
                    Masuk ke <span style="background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">SIS-OCMS</span>
                </h2>
                <p class="cta-subtitle reveal">
                    Akses terbatas untuk karyawan PT Saptaindra Sejati. 
                    Gunakan NIK dan password yang telah diberikan oleh Tim IT.
                </p>

                <div class="login-form-card reveal">
                    @if (session('status'))
                        <div style="background: var(--accent-cyan-dim); border: 1px solid rgba(72,202,228,0.2); color: var(--accent-cyan); padding: 12px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 16px;">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="nik" class="form-label">NIK / Username</label>
                            <input id="nik" class="form-input" type="text" name="nik" value="{{ old('nik') }}" required autocomplete="username" placeholder="Contoh: SA001">
                            @error('nik')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-remember">
                            <input id="remember_me" type="checkbox" name="remember">
                            <label for="remember_me">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn-login">Masuk ke Sistem →</button>
                    </form>

                    <p class="form-footer-text">
                        Hubungi Tim IT untuk pembuatan akun baru.
                    </p>
                </div>
            </div>
        </div>
    </section>
