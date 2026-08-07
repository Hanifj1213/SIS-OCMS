{{--
    PDF Fabrication Request PLO/09/F-021 — A4 LANDSCAPE.

    Proporsi diukur dari form asli (lihat fr/_form_style.blade.php).
    Area isi asli: 736pt lebar x 510pt tinggi. Tinggi tiap blok dikunci
    eksplisit supaya blok atas tidak menelan ruang blok bawah:

      header                       48pt   ( 9.4%)
      identitas + approval        172pt   (33.7%)
      header DETAIL + isi         264pt   (51.8%)
      total                        18pt   ( 3.5%)
      legenda                      10pt   ( 2.0%)
--}}
@php
    $amount = (float) ($fr->unit_price ?? 0) * (int) ($fr->qty ?? 1);
    $labour = (float) ($fr->labour_cost ?? 0);
    $grand = $amount + $labour;
    // Form asli membiarkan kolom biaya KOSONG bila belum ada nilai (bukan "0").
    $num = fn ($v) => $v > 0 ? number_format((float) $v, 0, ',', '.') : '';
    // Samakan dengan tampilan input date browser pada form (MM/DD/YYYY).
    $dShort = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('m/d/Y') : '';
    $dSlash = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('m/d/Y') : '';
    $logo = public_path('images/brand/alamtri-logo.png');
    $roles = App\Models\FabricationRequest::SIGNATURE_ROLES;
    $approvalWidths = [
        'received_by' => '13.99%',
        'sent_by' => '14.81%',
        'approved_by' => '12.77%',
        'checked_by' => '12.77%',
        'ordered_by' => '17.12%',
    ];

    // Daftar gambar "Gambar & Dimensi" — hanya file milik FR yang lolos resolver.
    $images = $pdfImages ?? [];

    // DomPDF tidak konsisten merender SVG inline yang diposisikan absolut.
    // Renderer ini menghasilkan satu SVG data-URI yang aman dipakai sebagai
    // image layer, dengan koordinat 0..100 yang sama seperti editor web.
    $annotationSvg = app(\App\Services\FrAnnotationRenderer::class)
        ->dataUri($fr->annotationList());
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fabrication Request — {{ $fr->fr_number }}</title>
    <style>
        /* Margin dibuat rata supaya lembar tercetak di tengah dengan
           ruang putih yang konsisten di semua sisi. */
        @page { margin: 10pt 10pt; }
        body { margin: 0; padding: 0; }
    </style>
    @include('fr._form_style')
    <style>
        /* Override khusus DomPDF. Width dipasang juga langsung pada sel karena
           renderer sering mengabaikan colgroup pada tabel bertingkat. */
        .fr-sheet { font-size: 8px; line-height: 1.1; }
        .fr-sheet .fr-title { font-size: 22px; }
        .fr-sheet .fr-frno { font-size: 10.5px; }
        .fr-sheet .fr-code { height: 38pt; }
        .fr-sheet .fr-code td {
            height: 9pt; min-height: 9pt; max-height: 9pt;
            font-size: 7px; line-height: 1; vertical-align: middle;
            padding: 0 3px;
        }
        .fr-sheet .fr-mat-title { height: 10pt; vertical-align: middle; }
        .fr-sheet .fr-mat th {
            height: 17pt; line-height: 1.05; vertical-align: middle;
        }
        .fr-sheet .fr-mat .fr-mat-width-row td {
            border: none; padding: 0; height: 0; line-height: 0; font-size: 0;
        }
        .fr-sheet .fr-mat-data td {
            height: 10pt; line-height: 1.05; vertical-align: middle;
        }
        .fr-sheet .fr-total td {
            height: 10pt; min-height: 10pt; font-size: 7.5px;
            line-height: 1.05; vertical-align: middle; padding: 1pt 3px;
        }
        .fr-sheet .fr-legend td {
            height: 9pt; min-height: 9pt; font-size: 7.5px;
            line-height: 1.05; vertical-align: middle; padding: 1pt 3px;
        }
    </style>
</head>
<body class="fr-sheet">

    {{-- ============ HEADER — 48pt ============ --}}
    <table>
        <colgroup><col style="width:13.45%"><col style="width:61.01%"><col style="width:25.54%"></colgroup>
        <tr>
            <td width="13.45%" style="width:13.45%; vertical-align:middle; text-align:center; height:38pt;">
                @if(file_exists($logo))
                    <img src="{{ $logo }}" style="height:23pt;">
                @else
                    <strong style="font-size:15px;">AlamTri</strong>
                @endif
            </td>
            <td width="61.01%" style="width:61.01%; vertical-align:middle;">
                <div class="fr-title">FABRICATION REQUEST</div>
                <div class="fr-frno">{{ $fr->fr_number }}</div>
            </td>{{-- nomor FR selalu terisi di PDF --}}
            <td width="25.54%" style="width:25.54%; padding:0; vertical-align:top;">
                <table class="fr-code">
                    <colgroup><col style="width:54%"><col style="width:46%"></colgroup>
                    <tr><td width="54%" style="width:54%;">No. Formulir / Form No.</td><td width="46%" style="width:46%;">{{ $fr->formCode('form_no') }}</td></tr>
                    <tr><td width="54%" style="width:54%;">No. SOP / SOP No.</td><td width="46%" style="width:46%;">{{ $fr->formCode('sop_no') }}</td></tr>
                    <tr><td width="54%" style="width:54%;">Pemilik / Owner</td><td width="46%" style="width:46%;">{{ $fr->formCode('form_owner') }}</td></tr>
                    <tr><td width="54%" style="width:54%;">Revisi Ke / Revision To</td><td width="46%" style="width:46%;">{{ $fr->formCode('form_revision') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Celah kosong antara header dan blok Sent To (form asli: 8pt,
         garis y=69 → 77). --}}
    <div style="height:7pt;"></div>

    {{-- ============ IDENTITAS + APPROVAL — 172pt ============ --}}
    <table>
        <colgroup>
            <col style="width:13.45%"><col style="width:15.08%">
            <col style="width:13.99%"><col style="width:14.81%">
            <col style="width:12.77%"><col style="width:12.77%"><col style="width:17.12%">
        </colgroup>

        <tr>
            <td width="13.45%" class="fr-lbl" style="width:13.45%; height:9pt;">Sent To</td>
            <td width="15.08%" class="fr-val" style="width:15.08%;">{{ $fr->sent_to }}</td>
            @foreach($roles as $key => $meta)
            <td width="{{ $approvalWidths[$key] }}" class="fr-sh"
                style="width:{{ $approvalWidths[$key] }};">{{ $meta['label'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td width="13.45%" class="fr-lbl" style="width:13.45%; height:16pt;">Address</td>
            <td width="15.08%" class="fr-val" style="width:15.08%;">{{ $fr->address }}</td>
            {{-- Tanpa garis bawah: pada form asli baris jabatan menyatu
                 dengan ruang tanda tangan di bawahnya. --}}
            @foreach($roles as $key => $meta)
            <td width="{{ $approvalWidths[$key] }}" class="fr-sh"
                style="width:{{ $approvalWidths[$key] }}; vertical-align:top; border-bottom:none;">{{ $meta['sub'] }}</td>
            @endforeach
        </tr>

        {{-- Requirement Date disorot kuning — satu-satunya sel berwarna.
             Ruang tanda tangan membentang sembilan baris data di sebelahnya. --}}
        <tr>
            <td width="13.45%" class="fr-lbl" style="width:13.45%; background:#ffff00; height:9pt;">Requirement Date</td>
            <td width="15.08%" class="fr-val" style="width:15.08%; background:#ffff00;">{{ $dShort($fr->estimation_date) }}</td>
            @foreach($roles as $key => $meta)
            @php $sig = $fr->signature($key); @endphp
            {{-- Gambar tanda tangan diposisikan bebas (hasil geser/resize di
                 form), dalam persen terhadap kotak approval. --}}
            <td width="{{ $approvalWidths[$key] }}" class="fr-sign-cell" rowspan="9"
                style="width:{{ $approvalWidths[$key] }}; height:92pt; border-top:none; padding:0;">
                @php $sigFile = $signatureFiles[$key] ?? null; @endphp
                @if($sigFile && is_file($sigFile))
                @php $sbox = $fr->signatureBox($key); @endphp
                <div style="position:relative; width:100%; height:91pt;">
                    <img src="{{ $sigFile }}"
                         style="position:absolute;
                                left:{{ $sbox['x'] }}%; top:{{ $sbox['y'] }}%;
                                width:{{ $sbox['w'] }}%;">
                </div>
                @endif
            </td>
            @endforeach
        </tr>
        <tr><td class="fr-lbl" style="height:9pt;">Attn</td><td class="fr-val">{{ $fr->attn }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">WO No.</td><td class="fr-val">{{ $fr->ro_number ?: $component->mol_wo_number }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">PR. No.</td><td class="fr-val">{{ $fr->pr_number }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">Date</td><td class="fr-val">{{ $dShort($fr->request_date ?: $fr->created_at) }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">Location / Site</td><td class="fr-val">{{ $fr->location_site ?: $component->site_district }}</td></tr>
        {{-- Identitas unit: nilai yang disunting pada FR menang, kosong =
             ikut data komponen (identity()). --}}
        <tr><td class="fr-lbl" style="height:9pt;">Unit Model</td><td class="fr-val">{{ $fr->identity('unit_model', $component) }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">Component model</td><td class="fr-val">{{ $fr->identity('component_model', $component) }}</td></tr>
        <tr><td class="fr-lbl" style="height:9pt;">Unit Code</td><td class="fr-val">{{ $fr->identity('unit_code', $component) }}</td></tr>

        {{-- "Work Order For" tanpa garis bawah, menyatu dengan band jenis
             pekerjaan di bawahnya. Sel nama memakai rowspan 2 sehingga tinggi
             kotaknya sejajar dengan band Repair/Fabrikasi: nama di atas,
             garis titik menempel di dasar sel. --}}
        <tr>
            <td colspan="2" class="fr-c fr-lbl" style="font-weight:bold; height:10pt; border-bottom:none;">Work Order For</td>
            @foreach($roles as $key => $meta)
            {{-- Isi didorong ke dasar sel supaya nama + garis titik rapat ke
                 baris Date di bawahnya, tanpa ruang kosong menggantung. --}}
            <td width="{{ $approvalWidths[$key] }}" class="fr-c" rowspan="2"
                style="width:{{ $approvalWidths[$key] }}; vertical-align:bottom; padding:1pt 3px 0; overflow:hidden;">
                <div class="fr-sign-name">{{ $fr->signature($key)['name'] }}</div>
                <table class="fr-dotline" style="margin-top:1px;">
                    <tr>
                        <td class="fr-dotline-edge">(</td>
                        <td class="fr-dotline-fill"><span>{{ str_repeat('.', 60) }}</span></td>
                        <td class="fr-dotline-edge">)</td>
                    </tr>
                </table>
            </td>
            @endforeach
        </tr>
        {{-- Jenis pekerjaan: kotak centang, boleh lebih dari satu --}}
        <tr>
            <td colspan="2" style="padding:0.5pt 2px; height:14pt; border-top:none;">
                <table class="fr-wt">
                    <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
                    <tr>
                        <td><span class="fr-box">{{ $fr->hasWorkType('repair') ? 'X' : '' }}</span>Repair</td>
                        <td><span class="fr-box">{{ $fr->hasWorkType('fabrikasi') ? 'X' : '' }}</span>Fabrikasi</td>
                    </tr>
                    <tr>
                        <td><span class="fr-box">{{ $fr->hasWorkType('modifikasi') ? 'X' : '' }}</span>Modifikasi</td>
                        <td><span class="fr-box">{{ $fr->hasWorkType('others') ? 'X' : '' }}</span>Others</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="height:8pt;"></td>
            @foreach($roles as $key => $meta)
            <td width="{{ $approvalWidths[$key] }}" class="fr-date"
                style="width:{{ $approvalWidths[$key] }}; overflow:hidden; white-space:nowrap;">
                Date : {{ $dSlash($fr->signature($key)['date']) }}
            </td>
            @endforeach
        </tr>
    </table>

    {{-- Celah antara baris Date dan blok DETAIL INSTRUCTION
         (form asli: 9pt, garis y=229 → 238). --}}
    <div style="height:7pt;"></div>

    {{-- ============ DETAIL · MATERIAL · GAMBAR — 264pt ============ --}}
    <table>
        <colgroup><col style="width:53.53%"><col style="width:46.47%"></colgroup>
        <tr>
            <td width="53.53%" class="fr-hdr" style="width:53.53%;">DETAIL INSTRUCTION</td>
            <td width="46.47%" class="fr-hdr" style="width:46.47%;">GAMBAR &amp; DIMENSI (BILA PERLU DIGAMBAR SESUAI UKURAN YANG DIKEHENDAKI)</td>
        </tr>
        <tr>
            {{-- kiri: instruksi rata tengah + tabel material --}}
            <td width="53.53%" style="width:53.53%; padding:0; vertical-align:top;">
                <table class="nb">
                    <tr>
                        <td class="fr-instr" style="height:62pt; padding:4px 10px;">
                            {!! nl2br(e($fr->instruction)) !!}
                        </td>
                    </tr>
                </table>

                <table class="fr-mat" style="border-left:none; border-right:none; border-bottom:none;">
                    <colgroup>
                        {{-- DomPDF mengabaikan persentase pada colgroup ketika
                             baris pertama memakai colspan. Lebar absolut ini
                             setara dengan 31.4/31.0/7.8/9.1/11.7/9.1%
                             dari lebar tabel material 435pt. --}}
                        <col width="136.6pt" style="width:136.6pt"><col width="134.9pt" style="width:134.9pt"><col width="34.0pt" style="width:34.0pt">
                        <col width="39.6pt" style="width:39.6pt"><col width="50.9pt" style="width:50.9pt"><col width="39.6pt" style="width:39.6pt">
                    </colgroup>
                    {{-- Baris pertama berisi enam sel agar DomPDF memakai
                         lebar kolom ini sebelum menemukan colspan judul. --}}
                    <tr class="fr-mat-width-row" aria-hidden="true">
                        <td width="31.4%" style="width:31.4%;">&nbsp;</td>
                        <td width="31.0%" style="width:31.0%;">&nbsp;</td>
                        <td width="7.8%" style="width:7.8%;">&nbsp;</td>
                        <td width="9.1%" style="width:9.1%;">&nbsp;</td>
                        <td width="11.7%" style="width:11.7%;">&nbsp;</td>
                        <td width="9.1%" style="width:9.1%;">&nbsp;</td>
                    </tr>
                    <tr><td colspan="6" class="fr-hdr fr-mat-title">PART MATERIAL SHOULD BE DELIVERY FOR REPAIR</td></tr>
                    <tr>
                        <th width="31.4%" style="width:31.4%;">PN/Size/Dim/Mod/SN</th>
                        <th width="31.0%" style="width:31.0%;">Description</th>
                        <th width="7.8%" style="width:7.8%;">Brand</th>
                        <th width="9.1%" style="width:9.1%;">Q'ty</th>
                        <th width="11.7%" style="width:11.7%;">Unit price</th>
                        <th width="9.1%" style="width:9.1%;">Amount Price</th>
                    </tr>
                    {{-- Satu baris data. Sisanya ruang kosong: tanpa garis
                         mendatar, garis vertikal kolom tetap diteruskan. --}}
                    <tr class="fr-mat-data">
                        <td width="31.4%" style="width:31.4%;">{{ $fr->part_number }}</td>
                        <td width="31.0%" style="width:31.0%; white-space:nowrap; overflow:hidden; word-break:keep-all;">{{ $fr->part_name }}</td>
                        <td width="7.8%" style="width:7.8%;">{{ $fr->brand }}</td>
                        <td width="9.1%" style="width:9.1%;">{{ $fr->qty }}</td>
                        <td width="11.7%" class="fr-r" style="width:11.7%;">{{ $num($fr->unit_price) }}</td>
                        <td width="9.1%" class="fr-r" style="width:9.1%;">{{ $num($amount) }}</td>
                    </tr>
                    <tr class="fr-mat-fill">
                        <td width="31.4%" style="width:31.4%; height:88pt;"></td>
                        <td width="31.0%" style="width:31.0%;"></td>
                        <td width="7.8%" style="width:7.8%;"></td>
                        <td width="9.1%" style="width:9.1%;"></td>
                        <td width="11.7%" style="width:11.7%;"></td>
                        <td width="9.1%" style="width:9.1%;"></td>
                    </tr>
                </table>
            </td>

            {{-- Kanan: gambar sebanyak yang diunggah (bisa lebih dari dua),
                 posisi & ukurannya mengikuti hasil geser/resize di form.
                 Posisi absolut dalam persen; tinggi ikut rasio asli gambar. --}}
            <td width="46.47%" style="width:46.47%; padding:0; vertical-align:top; height:200pt;">
                @if($images !== [] || $annotationSvg !== null)
                <div style="position:relative; width:100%; height:198pt;">
                    @foreach($images as $img)
                    <img src="{{ $img['file'] }}"
                         style="position:absolute;
                                left:{{ $img['x'] }}%; top:{{ $img['y'] }}%;
                                width:{{ $img['w'] }}%;">
                    @endforeach
                    @if($annotationSvg)
                    <img src="{{ $annotationSvg }}"
                         style="position:absolute; left:0; top:0; width:100%; height:100%;">
                    @endif
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ============ TOTAL — 18pt ============ --}}
    <table style="border-top:none;">
        <colgroup>
            <col style="width:41.5%"><col style="width:12%">
            <col style="width:34.5%"><col style="width:12%">
        </colgroup>
        {{-- Alignment mengikuti form asli: dua label kiri rata TENGAH,
             label Grand Total rata KIRI. --}}
        <tr class="fr-total">
            <td width="41.5%" class="fr-c" style="width:41.5%;">TOTAL PART / MATERIAL COST (JUMLAH BIAYA PART / MATERIAL)</td>
            <td width="12%" style="width:12%;">Rp. {{ $num($amount) }}</td>
            <td width="34.5%" style="width:34.5%;">GRAND TOTAL COST / BIAYA TOTAL (PART + LABOUR)</td>
            <td width="12%" style="width:12%;">Rp. {!! $grand > 0 ? $num($grand) : '___________' !!}</td>
        </tr>
        <tr class="fr-total">
            <td width="41.5%" class="fr-c" style="width:41.5%;">TOTAL LABOUR / JUMLAH BIAYA TENAGA KERJA (PEKERJAAN)</td>
            <td width="12%" style="width:12%;">Rp. {{ $num($labour) }}</td>
            {{-- nowrap: deretan spasi panjang tidak boleh membungkus baris,
                 karena itu membuat blok total membengkak jauh dari aslinya --}}
            <td width="46.5%" colspan="2" style="width:46.5%; white-space:nowrap; overflow:hidden;">SAID / TERBILANG : ({{ $fr->note ? ' ' . $fr->note . ' ' : str_repeat(' ', 46) }})</td>
        </tr>
    </table>

    {{-- ============ LEGENDA WARNA — tersebar selebar halaman ============
         Satu baris empat kolom tanpa tabel bersarang: nesting membuat
         DomPDF menambah tinggi yang tidak ada pada form asli. --}}
    <table class="fr-legend" style="border-top:none;">
        <colgroup><col style="width:30%"><col style="width:22%"><col style="width:25%"><col style="width:23%"></colgroup>
        <tr>
            <td width="30%" style="width:30%; border-right:none;">White/Putih : Supplier / Bengkel Luar,</td>
            <td width="22%" style="width:22%; border-left:none; border-right:none;">Pink/Merah : Security</td>
            <td width="25%" style="width:25%; border-left:none; border-right:none;">Yellow/Kuning : Warehouse</td>
            <td width="23%" style="width:23%; border-left:none;">Green/Hijau : Workshop</td>
        </tr>
    </table>

</body>
</html>
