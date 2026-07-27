    <!-- ==================== NAVBAR ==================== -->
    <nav class="nav" id="mainNav">
        <div class="container nav-inner">
            <a href="{{ url('/') }}" class="nav-brand">
                <div class="nav-brand-logo">
                    <x-alamtri-logo :size="38" />
                </div>
                <span class="nav-brand-name">SIS-OCMS</span>
            </a>
            <ul class="nav-links">
                <li><a href="#features">Fitur</a></li>
                <li><a href="#workflow">Alur Kerja</a></li>
                <li><a href="#access">Akses</a></li>
                <li><a href="{{ route('login') }}" class="nav-cta">Login →</a></li>
            </ul>
        </div>
    </nav>
