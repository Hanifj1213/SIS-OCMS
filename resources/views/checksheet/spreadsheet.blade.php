<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f3f5;
            font-family: Calibri, 'Segoe UI', system-ui, sans-serif;
            color: #1b1b1b;
        }

        .bar {
            position: sticky; top: 0; z-index: 20;
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
            padding: 10px 16px;
            background: #fff;
            border-bottom: 1px solid #d0d7de;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .bar h1 { margin: 0; font-size: 15px; font-weight: 700; }
        .bar .meta { font-size: 12px; color: #57606a; }
        .bar a.back { font-size: 13px; color: #0969da; text-decoration: none; }
        .bar a.back:hover { text-decoration: underline; }
        .status { font-size: 12px; color: #57606a; margin-left: auto; }
        .status.ok { color: #1a7f37; }
        .status.err { color: #cf222e; }

        .tabs { display: flex; gap: 2px; padding: 0 16px; background: #fff; border-bottom: 1px solid #d0d7de; overflow-x: auto; }
        .tabs a {
            padding: 7px 14px; font-size: 12.5px; text-decoration: none; color: #24292f;
            border: 1px solid transparent; border-bottom: none; white-space: nowrap;
        }
        .tabs a.active { background: #f6f8fa; border-color: #d0d7de; border-radius: 6px 6px 0 0; font-weight: 600; }
        .tabs a:hover:not(.active) { background: #f6f8fa; }

        .xl-wrap { overflow: auto; padding: 18px; }
        .xl-canvas { position: relative; display: inline-block; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.12); }

        table.xl-sheet {
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11pt;
            line-height: 1.15;
        }
        table.xl-sheet td {
            padding: 0 2px;
            overflow: hidden;
            white-space: nowrap;
            vertical-align: bottom;
            word-break: break-word;
        }

        .xl-img { position: absolute; object-fit: fill; pointer-events: none; }
        .xl-img-missing {
            position: absolute; display: flex; align-items: center; justify-content: center;
            font-size: 10px; color: #9a6700; background: repeating-linear-gradient(
                45deg, #fff8c5, #fff8c5 6px, #fff3bf 6px, #fff3bf 12px);
            border: 1px dashed #d4a72c; text-align: center;
        }

        .xl-decision { width: 15px; height: 15px; cursor: pointer; accent-color: #1f6feb; }
        .xl-decision:disabled { cursor: default; opacity: .55; }

        .xl-empty { padding: 24px; color: #57606a; }
        .legend { padding: 0 18px 18px; font-size: 12px; color: #57606a; }
    </style>
</head>
<body>
    <div class="bar">
        @if($component)
            <a class="back" href="{{ route('components.show', $component->comp_id) }}">&larr; Kembali</a>
        @else
            <a class="back" href="{{ route('checksheet.layouts') }}">&larr; Daftar layout</a>
        @endif

        <h1>{{ $title }}</h1>
        <span class="meta">
            {{ $layout->source_file }} &middot; {{ $layout->sheet_count }} tab &middot;
            {{ $layout->part_row_count }} baris part
            @unless($editable) &middot; hanya lihat @endunless
        </span>
        <span class="status" id="save-status"></span>
    </div>

    @if(count($sheets) > 1)
        <nav class="tabs">
            @foreach($sheets as $sheet)
                <a href="{{ request()->fullUrlWithQuery(['sheet' => $sheet['name']]) }}"
                   class="{{ $sheet['name'] === $active['name'] ? 'active' : '' }}">{{ $sheet['name'] }}</a>
            @endforeach
        </nav>
    @endif

    {!! app(\App\Services\SpreadsheetHtmlRenderer::class)->render(
        $active,
        $layout->styles(),
        $answers,
        $layout->decision_map[$active['name']] ?? null,
        $editable,
    ) !!}

    @php($decisions = $layout->decision_map[$active['name']] ?? null)
    @if($decisions)
        <p class="legend">
            Profil <strong>{{ $decisions['profile'] }}</strong> &middot;
            {{ count($decisions['parts']) }} baris part terdeteksi pada tab ini.
            @if($editable) Centang tersimpan otomatis. @endif
        </p>
    @endif

    @if($editable && $component)
    <script>
        (function () {
            const url = @json(route('components.local-checksheet.cell', [$component->comp_id, $layout->kind]));
            const csrf = @json(csrf_token());
            const sheet = @json($active['name']);
            const status = document.getElementById('save-status');
            let timer = null;

            function flash(text, cls) {
                status.textContent = text;
                status.className = 'status ' + (cls || '');
                clearTimeout(timer);
                timer = setTimeout(() => { status.textContent = ''; status.className = 'status'; }, 2500);
            }

            document.querySelectorAll('.xl-decision').forEach(function (box) {
                box.addEventListener('change', async function () {
                    const prev = !box.checked;
                    box.disabled = true;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                sheet: sheet,
                                cell_ref: box.dataset.ref,
                                value: box.checked ? '1' : '',
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok || !data.ok) throw new Error(data.message || 'Gagal menyimpan');
                        flash('Tersimpan', 'ok');
                    } catch (e) {
                        box.checked = prev;   // kembalikan ke keadaan semula
                        flash(e.message, 'err');
                    } finally {
                        box.disabled = false;
                    }
                });
            });
        })();
    </script>
    @endif
</body>
</html>
