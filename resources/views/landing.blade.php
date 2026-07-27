<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIS-OCMS — Next-Gen Overhaul Component Intelligence</title>
    <meta name="description" content="Sistem manajemen overhaul komponen alat berat berbasis digital oleh PT Saptaindra Sejati. Real-time tracking, smart inventory, dan quality gate terintegrasi.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Three.js + GSAP + ScrollTrigger (self-host: cepat di LAN, tidak tergantung CDN) -->
    <script src="{{ asset('vendor/three.min.js') }}"></script>
    <script src="{{ asset('vendor/gsap.min.js') }}"></script>
    <script src="{{ asset('vendor/ScrollTrigger.min.js') }}"></script>


    {{-- Landing dipecah per section ke resources/views/landing/ --}}
    @include('landing.styles')
</head>
<body>

    @include('landing.nav')
    @include('landing.hero')
    @include('landing.features')
    @include('landing.workflow')
    @include('landing.cta')
    @include('landing.footer')

    @include('landing.scripts')

</body>
</html>
