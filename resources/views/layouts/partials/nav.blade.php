        <!-- Navigation -->
        <nav class="ocms-nav">
            <div class="ocms-nav-inner">
                <a href="{{ route('dashboard') }}" class="ocms-nav-brand">
                    <div class="ocms-nav-brand-logo">
                        <x-alamtri-logo :size="34" />
                    </div>
                    <div class="ocms-nav-brand-text">
                        SIS-OCMS
                        <span>Plant Rebuild Centre</span>
                    </div>
                </a>

                <div class="ocms-nav-links">
                    <a href="{{ route('dashboard') }}" class="ocms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('components.index') }}" class="ocms-nav-link {{ request()->routeIs('components.*') ? 'active' : '' }}">Komponen</a>
                    <a href="{{ route('part-requests.index') }}" class="ocms-nav-link {{ request()->routeIs('part-requests.*') ? 'active' : '' }}">Gudang</a>
                    <a href="{{ route('scan') }}" class="ocms-nav-link {{ request()->routeIs('scan') ? 'active' : '' }}">Scan QR</a>
                    @ocmsDeveloper
                    <a href="{{ route('dev.index') }}" class="ocms-nav-link {{ request()->routeIs('dev.*') ? 'active' : '' }}">Dev</a>
                    @endocmsDeveloper
                    @ocmsAdmin
                    <a href="{{ route('admin.users.index') }}" class="ocms-nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">Users</a>
                    @endocmsAdmin
                </div>

                <div class="ocms-nav-user">
                    <button type="button" class="ocms-theme-btn" onclick="ocmsToggleTheme()" title="Ganti tema terang/gelap" aria-label="Ganti tema">
                        <span class="theme-icon-dark">🌙</span><span class="theme-icon-light">☀️</span>
                    </button>
                    <div>
                        <div class="ocms-nav-username">{{ Auth::user()->name }}</div>
                        <div class="ocms-nav-role">{{ Auth::user()->roles->pluck('name')->implode(', ') }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="ocms-nav-avatar" title="Profil Saya" style="text-decoration: none;">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="ocms-nav-logout-form" style="margin:0;">
                        @csrf
                        <button type="submit" class="ocms-nav-logout">Logout</button>
                    </form>
                    <button type="button" class="mobile-menu-btn" onclick="document.getElementById('ocmsMobileMenu').classList.toggle('open')" aria-label="Menu">☰</button>
                </div>
            </div>

            {{-- Mobile menu (muncul di layar < 768px) --}}
            <div class="ocms-mobile-menu" id="ocmsMobileMenu">
                <a href="{{ route('dashboard') }}" class="ocms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('components.index') }}" class="ocms-nav-link {{ request()->routeIs('components.*') ? 'active' : '' }}">Komponen</a>
                <a href="{{ route('part-requests.index') }}" class="ocms-nav-link {{ request()->routeIs('part-requests.*') ? 'active' : '' }}">Gudang</a>
                <a href="{{ route('scan') }}" class="ocms-nav-link {{ request()->routeIs('scan') ? 'active' : '' }}">Scan QR</a>
                @ocmsDeveloper
                <a href="{{ route('dev.index') }}" class="ocms-nav-link {{ request()->routeIs('dev.*') ? 'active' : '' }}">Dev</a>
                @endocmsDeveloper
                @ocmsAdmin
                <a href="{{ route('admin.users.index') }}" class="ocms-nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">Users</a>
                @endocmsAdmin
                <a href="{{ route('profile.edit') }}" class="ocms-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profil Saya</a>
                <form method="POST" action="{{ route('logout') }}" class="ocms-mobile-logout">
                    @csrf
                    <button type="submit" class="ocms-nav-logout">Logout</button>
                </form>
            </div>
        </nav>
