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
    $dShort = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('j-M-y') : '';
    $dSlash = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '';
    $logo = public_path('images/brand/alamtri-logo.png');
    $roles = App\Models\FabricationRequest::SIGNATURE_ROLES;

    // Daftar gambar "Gambar & Dimensi" beserta posisi/ukurannya (persen).
    // Hanya file yang benar-benar ada di disk yang dirender.
    $images = [];
    foreach ($fr->imageList() as $img) {
        $file = public_path($img['path']);
        if (is_file($file)) {
            $images[] = ['file' => $file] + $img;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fabrication Request — {{ $fr->fr_number }}</title>
    <style>
        /* Margin dibuat rata supaya lembar tercetak di tengah dengan
           ruang putih yang konsisten di semua sisi. */
        @page { margin: 20pt 49pt; }
        body { margin: 0; padding: 0; }
    </style>
    @include('fr._form_style')
</head>
<body class="fr-sheet">

    {{-- ============ HEADER — 48pt ============ --}}
    <table>
        <colgroup><col style="width:13.45%"><col style="width:61.01%"><col style="width:25.54%"></colgroup>
        <tr>
            <td style="vertical-align:middle; text-align:center; height:44pt;">
                @if(file_exists($logo))
                    <img src="{{ $logo }}" style="height:32pt;">
                @else
                    <strong style="font-size:15px;">AlamTri</strong>
                @endif
            </td>
            <td style="vertical-align:middle;">
                <div class="fr-title">FABRICATION REQUEST</div>
                <div class="fr-frno">{{ $fr->fr_number }}</div>
            </td>{{-- nomor FR selalu terisi di PDF --}}
            <td style="padding:0; vertical-align:top;">
                <table class="fr-code">
                    <colgroup><col style="width:54%"><col style="width:46%"></colgroup>
                    <tr><td>No. Formulir / Form No.</td><td>{{ $fr->formCode('form_no') }}</td></tr>
                    <tr><td>No. SOP / SOP No.</td><td>{{ $fr->formCode('sop_no') }}</td></tr>
                    <tr><td>Pemilik / Owner</td><td>{{ $fr->formCode('form_owner') }}</td></tr>
                    <tr><td>Revisi Ke / Revision To</td><td>{{ $fr->formCode('form_revision') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Celah kosong antara header dan blok Sent To (form asli: 8pt,
         garis y=69 → 77). --}}
    <div style="height:8pt;"></div>

    {{-- ============ IDENTITAS + APPROVAL — 172pt ============ --}}
    <table>
        <colgroup>
            <col style="width:13.45%"><col style="width:15.08%">
            <col style="width:13.99%"><col style="width:14.81%">
            <col style="width:12.77%"><col style="width:12.77%"><col style="width:17.12%">
        </colgroup>

        <tr>
            <td class="fr-lbl" style="height:9pt;">Sent To</td>
            <td class="fr-val">{{ $fr->sent_to }}</td>
            @foreach($roles as $meta)
            <td class="fr-sh">{{ $meta['label'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="fr-lbl" style="height:16pt;">Address</td>
            <td class="fr-val">{{ $fr->address }}</td>
            {{-- Tanpa garis bawah: pada form asli baris jabatan menyatu
                 dengan ruang tanda tangan di bawahnya. --}}
            @foreach($roles as $meta)
            <td class="fr-sh" style="vertical-align:top; border-bottom:none;">{{ $meta['sub'] }}</td>
            @endforeach
        </tr>

        {{-- Requirement Date disorot kuning — satu-satunya sel berwarna.
             Ruang tanda tangan membentang sembilan baris data di sebelahnya. --}}
        <tr>
            <td class="fr-lbl" style="background:#ffff00; height:9pt;">Requirement Date</td>
            <td class="fr-val" style="background:#ffff00;">{{ $dShort($fr->estimation_date) }}</td>
            @foreach($roles as $key => $meta)
            @php $sig = $fr->signature($key); @endphp
            {{-- Gambar tanda tangan diposisikan bebas (hasil geser/resize di
                 form), dalam persen terhadap kotak approval. --}}
            <td class="fr-sign-cell" rowspan="9" style="height:81pt; border-top:none; padding:0;">
                @if($sig['image'] && is_file(public_path($sig['image'])))
                @php $sbox = $fr->signatureBox($key); @endphp
                <div style="position:relative; width:100%; height:80pt;">
                    <img src="{{ public_path($sig['image']) }}"
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
            <td class="fr-c" rowspan="2" style="vertical-align:bottom; padding:1pt 3px 0;">
                <div class="fr-sign-name">{{ $fr->signature($key)['name'] }}</div>
                <table class="fr-dotline" style="margin-top:1pt;">
                    <tr>
                        <td class="fr-dotline-edge">(</td>
                        <td class="fr-dotline-fill">{{ str_repeat('.', 60) }}</td>
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
            <td class="fr-date">Date : {{ $dSlash($fr->signature($key)['date']) }}</td>
            @endforeach
        </tr>
    </table>

    {{-- Celah antara baris Date dan blok DETAIL INSTRUCTION
         (form asli: 9pt, garis y=229 → 238). --}}
    <div style="height:8pt;"></div>

    {{-- ============ DETAIL · MATERIAL · GAMBAR — 264pt ============ --}}
    <table>
        <colgroup><col style="width:53.53%"><col style="width:46.47%"></colgroup>
        <tr>
            <td class="fr-hdr">DETAIL INSTRUCTION</td>
            <td class="fr-hdr">GAMBAR &amp; DIMENSI (BILA PERLU DIGAMBAR SESUAI UKURAN YANG DIKEHENDAKI)</td>
        </tr>
        <tr>
            {{-- kiri: instruksi rata tengah + tabel material --}}
            <td style="padding:0; vertical-align:top;">
                <table class="nb">
                    <tr>
                        <td class="fr-instr" style="height:74pt; padding:4px 10px;">
                            {!! nl2br(e($fr->instruction)) !!}
                        </td>
                    </tr>
                </table>

                <table class="fr-mat" style="border-left:none; border-right:none; border-bottom:none;">
                    <colgroup>
                        <col style="width:31.22%"><col style="width:31.22%"><col style="width:7.87%">
                        <col style="width:9.14%"><col style="width:11.68%"><col style="width:8.87%">
                    </colgroup>
                    <tr><td colspan="6" class="fr-hdr">PART MATERIAL SHOULD BE DELIVERY FOR REPAIR</td></tr>
                    <tr>
                        <th>PN/Size/Dim/Mod/SN</th>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Q'ty</th>
                        <th>Unit price</th>
                        <th>Amount Price</th>
                    </tr>
                    {{-- Satu baris data. Sisanya ruang kosong: tanpa garis
                         mendatar, garis vertikal kolom tetap diteruskan. --}}
                    <tr>
                        <td>{{ $fr->part_number }}</td>
                        <td>{{ $fr->part_name }}</td>
                        <td>{{ $fr->brand }}</td>
                        <td>{{ $fr->qty }}</td>
                        <td class="fr-r">{{ $num($fr->unit_price) }}</td>
                        <td class="fr-r">{{ $num($amount) }}</td>
                    </tr>
                    <tr class="fr-mat-fill">
                        <td style="height:144pt;"></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                </table>
            </td>

            {{-- Kanan: gambar sebanyak yang diunggah (bisa lebih dari dua),
                 posisi & ukurannya mengikuti hasil geser/resize di form.
                 Posisi absolut dalam persen; tinggi ikut rasio asli gambar. --}}
            <td style="padding:0; vertical-align:top; height:255pt;">
                @if($images !== [])
                <div style="position:relative; width:100%; height:253pt;">
                    @foreach($images as $img)
                    <img src="{{ $img['file'] }}"
                         style="position:absolute;
                                left:{{ $img['x'] }}%; top:{{ $img['y'] }}%;
                                width:{{ $img['w'] }}%;">
                    @endforeach
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
            <td class="fr-c">TOTAL PART / MATERIAL COST (JUMLAH BIAYA PART / MATERIAL)</td>
            <td>Rp. {{ $num($amount) }}</td>
            <td>GRAND TOTAL COST / BIAYA TOTAL (PART + LABOUR)</td>
            <td>Rp. {!! $grand > 0 ? $num($grand) : '___________' !!}</td>
        </tr>
        <tr class="fr-total">
            <td class="fr-c">TOTAL LABOUR / JUMLAH BIAYA TENAGA KERJA (PEKERJAAN)</td>
            <td>Rp. {{ $num($labour) }}</td>
            {{-- nowrap: deretan spasi panjang tidak boleh membungkus baris,
                 karena itu membuat blok total membengkak jauh dari aslinya --}}
            <td colspan="2" style="white-space:nowrap; overflow:hidden;">SAID / TERBILANG : ({{ $fr->note ? ' ' . $fr->note . ' ' : str_repeat(' ', 46) }})</td>
        </tr>
    </table>

    {{-- ============ LEGENDA WARNA — tersebar selebar halaman ============
         Satu baris empat kolom tanpa tabel bersarang: nesting membuat
         DomPDF menambah tinggi yang tidak ada pada form asli. --}}
    <table class="fr-legend" style="border-top:none;">
        <colgroup><col style="width:30%"><col style="width:22%"><col style="width:25%"><col style="width:23%"></colgroup>
        <tr>
            <td style="border-right:none;">White/Putih : Supplier / Bengkel Luar,</td>
            <td style="border-left:none; border-right:none;">Pink/Merah : Security</td>
            <td style="border-left:none; border-right:none;">Yellow/Kuning : Warehouse</td>
            <td style="border-left:none;">Green/Hijau : Workshop</td>
        </tr>
    </table>

</body>
</html>
