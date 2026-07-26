<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layout Checksheet Lokal</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f3f5; font-family: 'Segoe UI', system-ui, sans-serif; color: #1b1b1b; }
        h1 { font-size: 19px; margin: 0 0 4px; }
        p.sub { margin: 0 0 20px; color: #57606a; font-size: 13px; }
        table { border-collapse: collapse; background: #fff; width: 100%; max-width: 1100px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th, td { padding: 8px 12px; text-align: left; font-size: 13px; border-bottom: 1px solid #e6e8ea; }
        th { background: #f6f8fa; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: #57606a; }
        tr:last-child td { border-bottom: none; }
        a { color: #0969da; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #8b949e; }
        .empty { padding: 40px; text-align: center; color: #57606a; background: #fff; }
        code { background: #f6f8fa; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Layout Checksheet Lokal</h1>
    <p class="sub">
        Hasil impor dari file Excel SIAP. Klik untuk melihat tampilannya — dirender dari database, tanpa Google Sheets.
    </p>

    @if($layouts->isEmpty())
        <div class="empty">
            Belum ada layout. Jalankan <code>php artisan checksheet:import-layouts</code> lebih dulu.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>EGI</th>
                    <th>Jenis</th>
                    <th class="num">Tab</th>
                    <th class="num">Baris part</th>
                    <th>File asal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($layouts as $l)
                    <tr>
                        <td><a href="{{ route('checksheet.layouts.preview', $l->layout_id) }}">{{ $l->major_category }}</a></td>
                        <td>{{ $l->egi_model ?: '—' }}</td>
                        <td>{{ $l->kind }}</td>
                        <td class="num">{{ $l->sheet_count }}</td>
                        <td class="num {{ $l->part_row_count ? '' : 'muted' }}">{{ $l->part_row_count }}</td>
                        <td class="muted">{{ $l->source_file }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
