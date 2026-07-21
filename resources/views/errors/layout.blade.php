<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusCode ?? 500 }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#0f172a; color:#e2e8f0; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0; }
        .box { text-align:center; padding:2rem; max-width:32rem; }
        h1 { font-size:3rem; margin:0 0 .5rem; color:#38bdf8; }
        p { color:#94a3b8; line-height:1.6; }
        a { color:#38bdf8; }
    </style>
</head>
<body>
<div class="box">
    <h1>{{ $statusCode ?? 500 }}</h1>
    <p>{{ $message ?? 'Terjadi kesalahan. Silakan coba lagi atau hubungi administrator.' }}</p>
    <p><a href="{{ url('/dashboard') }}">← Kembali ke Dashboard</a></p>
</div>
</body>
</html>
