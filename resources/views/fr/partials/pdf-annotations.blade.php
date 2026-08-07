{{--
    Render anotasi vektor kanvas "Gambar & Dimensi" pada PDF.
    Koordinat 0..100 (persen) sama seperti di form, jadi overlay menutupi
    tepat di atas gambar. DomPDF merender SVG inline dasar (line, polyline,
    circle, text). Ketebalan & ukuran font memakai satuan viewBox agar
    konsisten dengan layar (stroke*0.35, font 2.2*stroke).
--}}
@if(!empty($annotations))
<svg viewBox="0 0 100 100" preserveAspectRatio="none"
     style="position:absolute; left:0; top:0; width:100%; height:100%;">
    @foreach($annotations as $a)
        @php
            $type = str_replace('_', '-', (string) ($a['type'] ?? ''));
            $color = $a['color'] ?? '#dc2626';
            $stroke = (float) ($a['stroke'] ?? 2);
            $sw = $stroke * 0.35;
            $arrowPoints = function (array $line, bool $atStart) use ($stroke) {
                $tipX = (float) ($atStart ? $line['x1'] : $line['x2']);
                $tipY = (float) ($atStart ? $line['y1'] : $line['y2']);
                $tailX = (float) ($atStart ? $line['x2'] : $line['x1']);
                $tailY = (float) ($atStart ? $line['y2'] : $line['y1']);
                $dx = $tipX - $tailX;
                $dy = $tipY - $tailY;
                $length = max(0.001, sqrt($dx * $dx + $dy * $dy));
                $ux = $dx / $length;
                $uy = $dy / $length;
                $size = min($length * 0.30, 5 + $stroke * 0.35);
                $spread = $size * 0.62;
                $baseX = $tipX - $ux * $size;
                $baseY = $tipY - $uy * $size;

                return implode(' ', [
                    ($baseX - $uy * $spread).','.($baseY + $ux * $spread),
                    $tipX.','.$tipY,
                    ($baseX + $uy * $spread).','.($baseY - $ux * $spread),
                ]);
            };
        @endphp
        @if($type === 'text')
            <text x="{{ $a['x'] }}" y="{{ $a['y'] }}"
                  font-family="Arial, Helvetica, sans-serif"
                  font-size="{{ $a['font_size'] ?? 2.2 * $stroke }}" fill="{{ $color }}">{{ $a['text'] ?? '' }}</text>
        @elseif($type === 'brush' && !empty($a['points']))
            <polyline fill="none" stroke="{{ $color }}" stroke-width="{{ $sw }}"
                      stroke-linecap="round" stroke-linejoin="round"
                      points="{{ collect($a['points'])->map(fn($p) => $p['x'].','.$p['y'])->implode(' ') }}" />
        @elseif(in_array($type, ['line', 'arrow', 'connector', 'double-arrow'], true))
            <line x1="{{ $a['x1'] }}" y1="{{ $a['y1'] }}" x2="{{ $a['x2'] }}" y2="{{ $a['y2'] }}"
                  stroke="{{ $color }}" stroke-width="{{ $sw }}" stroke-linecap="round" />
            @if($type === 'connector')
                <circle cx="{{ $a['x1'] }}" cy="{{ $a['y1'] }}" r="{{ max(0.5, $sw * 0.9) }}" fill="{{ $color }}" />
                <circle cx="{{ $a['x2'] }}" cy="{{ $a['y2'] }}" r="{{ max(0.5, $sw * 0.9) }}" fill="{{ $color }}" />
            @endif
            @if(in_array($type, ['arrow', 'double-arrow'], true))
                <polygon fill="{{ $color }}"
                         points="{{ $arrowPoints($a, false) }}" />
            @endif
            @if($type === 'double-arrow')
                <polygon fill="{{ $color }}"
                         points="{{ $arrowPoints($a, true) }}" />
            @endif
        @endif
    @endforeach
</svg>
@endif
