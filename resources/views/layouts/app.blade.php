<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SIS-OCMS') }}</title>

        <!-- Fonts: Inter + JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

        {{-- GSAP untuk animasi fade-up. Three.js sengaja TIDAK dimuat di
             halaman kerja internal (berat di PC/tablet lama); background 3D
             hanya ada di landing & login. Library di-self-host agar tetap
             cepat di jaringan LAN. --}}
        <script src="{{ asset('vendor/gsap.min.js') }}"></script>
        <script src="{{ asset('vendor/ScrollTrigger.min.js') }}"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            // === Helper polling status realtime ===
            // Poll otomatis berhenti saat tab tidak terlihat dan langsung
            // refresh sekali saat tab kembali aktif.
            window.ocmsPoll = function(url, intervalMs, onData) {
                let inFlight = false;

                async function tick() {
                    if (document.hidden || inFlight) return;
                    inFlight = true;
                    try {
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (res.ok) onData(await res.json());
                    } catch (e) { /* jaringan putus sementara: coba lagi di tick berikutnya */ }
                    inFlight = false;
                }

                const timer = setInterval(tick, intervalMs);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); });
                return () => clearInterval(timer);
            };

            // === Tema light/dark: terapkan sebelum render agar tidak berkedip ===
            (function() {
                const saved = localStorage.getItem('ocms-theme');
                if (saved === 'light') document.documentElement.dataset.theme = 'light';
            })();
        </script>

        {{-- CSS global & nav dipecah ke layouts/partials/ --}}
        @include('layouts.partials.styles')
    </head>
    <body class="ocms-body">

        <div class="grid-overlay"></div>

        @include('layouts.partials.nav')

        <!-- Main Content -->
        <main class="ocms-main" style="position: relative; z-index: 1;">
            {{ $slot }}
        </main>

        @include('layouts.partials.scripts')
    </body>
</html>
