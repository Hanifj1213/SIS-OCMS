<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checksheet — {{ $comp->serial_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap"
        rel="stylesheet">

    <script>
        // Tema light/dark — ikut pilihan yang sama dengan halaman utama
        (function() {
            if (localStorage.getItem('ocms-theme') === 'light') {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>


    {{-- Halaman standalone: CSS/markup/JS dipecah ke partials/checksheet-page/ --}}
    @include('overhauls.partials.checksheet-page.styles')
</head>

<body>

    @include('overhauls.partials.checksheet-page.body')

    @include('overhauls.partials.checksheet-page.scripts')
</body>

</html>
