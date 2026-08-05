{{--
    Form Fabrication Request PLO/09/F-021 — tampilan mengikuti lembar cetak
    A4 landscape. Proporsi kolom & tinggi blok identik dengan fr/pdf.blade.php;
    angkanya diukur dari form asli (lihat fr/_form_style.blade.php).

    Mode:
      - create : $fr = null, nilai awal dari $candidate (hasil scan spreadsheet)
      - edit   : $fr terisi
--}}
@php
    $roles = App\Models\FabricationRequest::SIGNATURE_ROLES;
    // Bulan Romawi untuk pratinjau pola nomor FR pada form baru
    $romanMonth = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'][(int) now()->format('n')];
    // Nilai awal: FR tersimpan → kandidat scan → kosong
    $v = function (string $key, $default = '') use ($fr, $candidate) {
        return old($key, $fr->{$key} ?? ($candidate[$key] ?? $default));
    };
    $dateVal = function (?string $key) use ($fr) {
        $raw = old($key, optional($fr)->{$key});
        return $raw ? \Carbon\Carbon::parse($raw)->format('Y-m-d') : '';
    };

    $activeTypes = old('work_types', $fr ? $fr->workTypes() : [($candidate['work_type'] ?? 'repair')]);
    $activeTypes = is_array($activeTypes) ? $activeTypes : [];

    // Gambar yang sudah tersimpan; jumlahnya bebas (form asli kadang 5 foto).
    $existingImages = $fr ? $fr->imageList() : [];

    $amount = (float) ($v('unit_price', 0) ?: 0) * (int) ($v('qty', 1) ?: 1);
@endphp

<x-app-layout>

@include('fr._form_style')

<style>
    /* Kertas: rasio A4 landscape, lebar tetap agar proporsi tidak melar */
    .fr-page { max-width: 1180px; margin: 0 auto; }
    .fr-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .fr-sheet {
        width: 1120px; padding: 16px 20px; box-sizing: border-box;
        box-shadow: 0 24px 48px rgba(0,0,0,0.35);
        font-size: 11px;
    }
    .fr-sheet .fr-title { font-size: 26px; }
    .fr-sheet .fr-frno { font-size: 14px; }
    /* Nomor FR: tetap tampil seperti teks judul walau berupa input */
    .fr-sheet .fr-frno-input {
        font-family: 'Times New Roman', Times, serif;
        font-size: 14px; font-weight: bold; text-align: center;
    }
    .fr-sheet .fr-code td { font-size: 9px; height: 14px; }
    .fr-sheet .fr-lbl, .fr-sheet .fr-sh, .fr-sheet .fr-date,
    .fr-sheet .fr-hdr, .fr-sheet .fr-total td, .fr-sheet .fr-legend-in td { font-size: 10px; }
    /* Di layar, sel diberi padding lebih lega daripada versi cetak supaya
       teks tidak terlihat menempel ke garis tabel. */
    .fr-sheet td, .fr-sheet th { padding: 2px 5px; }
    .fr-sheet .fr-lbl, .fr-sheet .fr-val { padding-left: 6px; }
    .fr-sheet .fr-sh { padding-top: 3px; padding-bottom: 3px; }
    .fr-sheet .fr-hdr { padding-top: 3px; padding-bottom: 3px; }
    .fr-sheet .fr-mat th { font-size: 10px; }
    .fr-sheet .fr-mat td { font-size: 11px; }
    .fr-sheet .fr-wt { font-size: 11px; }
    .fr-sheet .fr-box { width: 11px; height: 11px; line-height: 11px; font-size: 9px; }
    .fr-actions { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
    .fr-hint { font-size: 0.7rem; color: var(--text-muted); margin: 6px 0 0; }

    /* Kotak unggah gambar: hanya penanda di layar, tak tercetak */
    .fr-up {
        display: block; cursor: pointer; text-align: center; color: #9aa4b2;
        font-size: 9px; border: 1px dashed #cbd5e1; border-radius: 3px;
        padding: 6px 4px; margin: 2px;
    }
    .fr-up:hover { border-color: #1d4ed8; color: #1d4ed8; }
    .fr-up img { max-width: 100%; max-height: 150px; object-fit: contain; display: block; margin: 0 auto; }

    /* Objek gambar bebas di kanvas: bisa digeser & diubah ukurannya */
    .fr-obj { position: absolute; cursor: move; }
    .fr-obj img { width: 100%; height: auto; display: block; user-select: none; }
    .fr-obj:hover { outline: 1px dashed #1d4ed8; }
    .fr-obj.fr-obj-active { outline: 1.5px solid #1d4ed8; }
    .fr-obj-handle {
        position: absolute; right: -5px; bottom: -5px;
        width: 11px; height: 11px; background: #1d4ed8;
        border: 1.5px solid #fff; border-radius: 2px; cursor: nwse-resize;
    }
    .fr-obj:not(:hover):not(.fr-obj-active) .fr-obj-handle { opacity: 0; }
    @media print {
        .fr-obj { outline: none !important; }
        .fr-obj-handle { display: none !important; }
    }
    .fr-sig-up {
        display: block; cursor: pointer; text-align: center; color: #b9c2ce;
        font-size: 8px; border: 1px dashed #dbe2ea; border-radius: 2px; padding: 2px;
    }
    .fr-sig-up:hover { border-color: #1d4ed8; color: #1d4ed8; }
    .fr-sig-up img { max-height: 46px; max-width: 100%; display: block; margin: 0 auto; }
</style>

<div class="section fade-up fr-page">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
        <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary btn-sm" style="text-decoration:none;">← Kembali ke Komponen</a>
        <span class="badge badge-cyan">{{ $fr ? 'Edit' : 'Buat' }} Form PLO/09/F-021 · A4 Landscape</span>
    </div>

    @if($errors->any())
    <div class="alert alert-error">
        <strong>Periksa isian:</strong>
        <ul style="margin:6px 0 0 16px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $fr
              ? route('components.fr.update', [$comp->comp_id, $fr->fr_id])
              : route('components.fr.storeSingle', $comp->comp_id) }}">
            @csrf
            @if($fr) @method('PUT') @endif
        <input type="hidden" name="section" value="{{ $v('section') }}">
        <input type="hidden" name="source" value="{{ $v('source', 'manual') }}">

        <div class="fr-scroll">
        <div class="fr-sheet">

            {{-- ============ HEADER ============ --}}
            <table>
                <colgroup><col style="width:13.45%"><col style="width:61.01%"><col style="width:25.54%"></colgroup>
                <tr>
                    <td style="vertical-align:middle; text-align:center; height:56px;">
                        <img src="{{ asset('images/brand/alamtri-logo.png') }}" alt="AlamTri" style="height:40px;">
                    </td>
                    <td style="vertical-align:middle;">
                        <div class="fr-title">FABRICATION REQUEST</div>
                        {{-- Nomor FR bisa disunting. Dibiarkan kosong pada FR
                             baru = sistem yang memberi nomor urut berikutnya. --}}
                        <div class="fr-frno fr-edit">
                            <input type="text" name="fr_number" class="fr-frno-input"
                                   value="{{ old('fr_number', $fr->fr_number ?? '') }}"
                                   placeholder="FR/SIS/RC/____/{{ $romanMonth }}/{{ now()->format('Y') }}/INT">
                    </div>
                    </td>
                    <td style="padding:0; vertical-align:top;">
                        {{-- Kode formulir bisa disunting; kosong = nilai bawaan --}}
                        <table class="fr-code">
                            <colgroup><col style="width:54%"><col style="width:46%"></colgroup>
                            @foreach([
                                'form_no' => 'No. Formulir / Form No.',
                                'sop_no' => 'No. SOP / SOP No.',
                                'form_owner' => 'Pemilik / Owner',
                                'form_revision' => 'Revisi Ke / Revision To',
                            ] as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="fr-edit">
                                    <input type="text" name="{{ $key }}"
                                           value="{{ old($key, $fr ? $fr->formCode($key) : App\Models\FabricationRequest::FORM_DEFAULTS[$key]) }}">
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Celah kosong antara header dan blok Sent To.
                 Pada form asli ada jarak 8pt di sini (garis y=69 → 77). --}}
            <div style="height:10px;"></div>

            {{-- ============ IDENTITAS + APPROVAL ============ --}}
            <table>
                <colgroup>
                    <col style="width:13.45%"><col style="width:15.08%">
                    <col style="width:13.99%"><col style="width:14.81%">
                    <col style="width:12.77%"><col style="width:12.77%"><col style="width:17.12%">
                </colgroup>

                <tr>
                    <td class="fr-lbl" style="height:13px;">Sent To</td>
                    <td class="fr-edit fr-val"><input type="text" name="sent_to" value="{{ $v('sent_to') }}" placeholder="LOKAL"></td>
                    @foreach($roles as $meta)
                    <td class="fr-sh">{{ $meta['label'] }}</td>
                    @endforeach
                </tr>
                {{-- Baris jabatan (External Workshop, Warehouse Keeper, dst.)
                     tanpa garis bawah: pada form asli menyatu dengan ruang
                     tanda tangan di bawahnya. --}}
                <tr>
                    <td class="fr-lbl" style="height:24px;">Address</td>
                    <td class="fr-edit fr-val"><input type="text" name="address" value="{{ $v('address') }}"></td>
                    @foreach($roles as $meta)
                    <td class="fr-sh" style="vertical-align:top; border-bottom:none;">{{ $meta['sub'] }}</td>
                    @endforeach
                </tr>

                {{-- Requirement Date: satu-satunya sel kuning, seperti aslinya --}}
                <tr>
                    <td class="fr-lbl fr-yellow" style="height:14px;">Requirement Date</td>
                    <td class="fr-yellow fr-edit fr-val"><input type="date" name="estimation_date" value="{{ $dateVal('estimation_date') }}"></td>

                    {{-- Ruang tanda tangan: unggah gambar + nama + FOR --}}
                    @foreach($roles as $key => $meta)
                    @php $sig = $fr ? $fr->signature($key) : ['name'=>'','date'=>null,'image'=>null]; @endphp
                    {{-- Tanda tangan: gambar bisa digeser & diubah ukurannya
                         di dalam kotak approval, sama seperti gambar part. --}}
                    @php $sbox = $fr ? $fr->signatureBox($key) : ['x'=>12.0,'y'=>10.0,'w'=>74.0]; @endphp
                    <td class="fr-sign-cell fr-sig-cell" rowspan="9"
                        style="height:132px; vertical-align:top; border-top:none; padding:0; position:relative;">
                        <div class="fr-sig-canvas" data-role="{{ $key }}" style="position:relative; width:100%; height:130px; overflow:hidden;">
                            <div class="fr-obj fr-sig-obj" data-role="{{ $key }}"
                                 style="left:{{ $sbox['x'] }}%; top:{{ $sbox['y'] }}%; width:{{ $sbox['w'] }}%;
                                        {{ $sig['image'] ? '' : 'display:none;' }}">
                                <img src="{{ $sig['image'] ? asset($sig['image']) : '' }}" alt="ttd" draggable="false">
                                <span class="fr-obj-handle fr-no-print" title="Tarik untuk mengubah ukuran"></span>
                </div>
                        </div>
                        <label class="fr-sig-up fr-no-print" style="position:absolute; left:2px; right:2px; bottom:2px;">
                            <input type="file" name="signatures[{{ $key }}][image]" accept="image/*" style="display:none;"
                                   data-role="{{ $key }}" onchange="frSigPick(this)">
                            <span>{{ $sig['image'] ? 'ganti tanda tangan' : '+ tanda tangan' }}</span>
                </label>
                        <input type="hidden" name="signature_layout[{{ $key }}][x]" value="{{ $sbox['x'] }}">
                        <input type="hidden" name="signature_layout[{{ $key }}][y]" value="{{ $sbox['y'] }}">
                        <input type="hidden" name="signature_layout[{{ $key }}][w]" value="{{ $sbox['w'] }}">
                    </td>
                    @endforeach
                </tr>
                <tr><td class="fr-lbl" style="height:14px;">Attn</td><td class="fr-edit fr-val"><input type="text" name="attn" value="{{ $v('attn') }}"></td></tr>
                <tr><td class="fr-lbl" style="height:14px;">WO No.</td><td class="fr-edit fr-val"><input type="text" name="ro_number" value="{{ $v('ro_number', $comp->mol_wo_number ?? '') }}" placeholder="2700046897"></td></tr>
                <tr><td class="fr-lbl" style="height:14px;">PR. No.</td><td class="fr-edit fr-val"><input type="text" name="pr_number" value="{{ $v('pr_number') }}"></td></tr>
                <tr><td class="fr-lbl" style="height:14px;">Date</td><td class="fr-edit fr-val"><input type="date" name="request_date" value="{{ $dateVal('request_date') ?: now()->format('Y-m-d') }}"></td></tr>
                <tr><td class="fr-lbl" style="height:14px;">Location / Site</td><td class="fr-edit fr-val"><input type="text" name="location_site" value="{{ $v('location_site', $comp->site_district ?? '') }}" placeholder="ADMO"></td></tr>
                {{-- Identitas unit terisi otomatis dari data komponen, tetapi
                     tetap bisa disunting; nilai suntingan disimpan di FR. --}}
                @foreach([
                    'unit_model' => ['Unit Model', $comp->model_type ?: $comp->egi],
                    'component_model' => ['Component model', $comp->component_model ?: $comp->major_category],
                    'unit_code' => ['Unit Code', $comp->unit_code ?: $comp->serial_number],
                ] as $field => [$label, $auto])
                <tr>
                    <td class="fr-lbl" style="height:14px;">{{ $label }}</td>
                    <td class="fr-edit fr-val">
                        <input type="text" name="{{ $field }}"
                               value="{{ old($field, $fr ? $fr->identity($field, $comp) : $auto) }}">
                    </td>
                </tr>
                @endforeach

                {{-- "Work Order For" tanpa garis bawah, menyatu dengan band jenis
                     pekerjaan. Sel nama memakai rowspan 2 agar tinggi kotaknya
                     sejajar band Repair/Fabrikasi: nama di atas, garis titik
                     menempel di dasar sel. --}}
                <tr>
                    <td colspan="2" class="fr-c fr-lbl" style="font-weight:bold; height:13px; border-bottom:none;">Work Order For</td>
                    @foreach($roles as $key => $meta)
                    @php $sig = $fr ? $fr->signature($key) : ['name'=>'']; @endphp
                    {{-- Isi didorong ke dasar sel agar rapat ke baris Date --}}
                    <td class="fr-c fr-edit" rowspan="2" style="vertical-align:bottom; padding:2px 4px 1px;">
                        <input type="text" name="signatures[{{ $key }}][name]" value="{{ $sig['name'] }}"
                               class="fr-sign-name" style="text-align:center; text-transform:uppercase;" placeholder="NAMA">
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
                {{-- Jenis pekerjaan: KOTAK CENTANG, boleh lebih dari satu --}}
                <tr>
                    <td colspan="2" style="padding:1px 2px; height:34px; border-top:none;">
                        <table class="fr-wt">
                            <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
                            <tr>
                                @foreach(['repair' => 'Repair', 'fabrikasi' => 'Fabrikasi'] as $val => $label)
                                <td><label style="cursor:pointer;"><input type="checkbox" name="work_types[]" value="{{ $val }}"
                                    {{ in_array($val, $activeTypes, true) ? 'checked' : '' }}> {{ $label }}</label></td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach(['modifikasi' => 'Modifikasi', 'others' => 'Others'] as $val => $label)
                                <td><label style="cursor:pointer;"><input type="checkbox" name="work_types[]" value="{{ $val }}"
                                    {{ in_array($val, $activeTypes, true) ? 'checked' : '' }}> {{ $label }}</label></td>
                                @endforeach
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="height:12px;"></td>
                    @foreach($roles as $key => $meta)
                    @php $sig = $fr ? $fr->signature($key) : ['date'=>null]; @endphp
                    <td class="fr-date fr-edit" style="white-space:nowrap;">
                        Date : <input type="date" name="signatures[{{ $key }}][date]" value="{{ $sig['date'] }}" style="width:74%;">
                    </td>
                    @endforeach
                </tr>
            </table>

            {{-- Celah antara baris Date dan blok DETAIL INSTRUCTION
                 (form asli: 9pt, garis y=229 → 238). --}}
            <div style="height:10px;"></div>

            {{-- ============ DETAIL · MATERIAL · GAMBAR ============ --}}
            <table>
                <colgroup><col style="width:53.53%"><col style="width:46.47%"></colgroup>
                <tr>
                    <td class="fr-hdr">DETAIL INSTRUCTION</td>
                    <td class="fr-hdr">GAMBAR &amp; DIMENSI (BILA PERLU DIGAMBAR SESUAI UKURAN YANG DIKEHENDAKI)</td>
                </tr>
                <tr>
                    <td style="padding:0; vertical-align:top;">
                        <table class="nb">
                            <tr>
                                <td class="fr-instr fr-edit" style="height:96px; padding:4px 10px;">
                                    <textarea name="instruction" rows="4" style="text-align:center; height:88px;"
                                              placeholder="POLESHING AREA BEARING SEAT">{{ $v('instruction') }}</textarea>
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
                            <tr>
                                <td class="fr-edit"><input type="text" name="part_number" value="{{ $v('part_number') }}" style="text-align:center;" placeholder="561-13-71020"></td>
                                <td class="fr-edit"><input type="text" name="part_name" required value="{{ $v('part_name') }}" style="text-align:center;" placeholder="SHAFT"></td>
                                <td class="fr-edit"><input type="text" name="brand" value="{{ $v('brand') }}" style="text-align:center;" placeholder="KMT"></td>
                                <td class="fr-edit"><input type="number" name="qty" id="fr-qty" min="1" required value="{{ $v('qty', 1) }}" style="text-align:center;"></td>
                                <td class="fr-edit"><input type="number" name="unit_price" id="fr-unit" step="1" min="0" value="{{ $v('unit_price') }}" style="text-align:right;"></td>
                                <td class="fr-r" id="fr-amount">{{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}</td>
                            </tr>
                            {{-- Ruang kosong: tanpa garis mendatar, garis kolom diteruskan --}}
                            <tr class="fr-mat-fill">
                                <td style="height:172px;"></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                </table>
                    </td>

                    {{-- Kanvas gambar bebas. Jumlah gambar tidak dibatasi:
                         satu tombol "Tambah gambar" dipakai berulang, gambar
                         yang sudah ada tidak tertimpa. Tiap gambar bisa digeser
                         (tarik) dan diubah ukurannya (tarik sudut kanan bawah).
                         Klik kanan pada gambar untuk menghapusnya.
                         Posisi & ukuran disimpan dalam persen supaya hasil
                         cetak sama dengan tampilan layar. --}}
                    <td style="padding:0; vertical-align:top;">
                        <div id="fr-canvas" style="position:relative; width:100%; height:300px; overflow:hidden;">
                            @foreach($existingImages as $i => $img)
                            <div class="fr-obj" data-index="{{ $i }}"
                                 style="left:{{ $img['x'] }}%; top:{{ $img['y'] }}%; width:{{ $img['w'] }}%;">
                                <img src="{{ asset($img['path']) }}" alt="Gambar {{ $i + 1 }}" draggable="false">
                                <span class="fr-obj-handle fr-no-print" title="Tarik untuk mengubah ukuran"></span>
                                <input type="hidden" name="images[{{ $i }}][path]" value="{{ $img['path'] }}">
                                <input type="hidden" name="images[{{ $i }}][x]" value="{{ $img['x'] }}">
                                <input type="hidden" name="images[{{ $i }}][y]" value="{{ $img['y'] }}">
                                <input type="hidden" name="images[{{ $i }}][w]" value="{{ $img['w'] }}">
                    </div>
                            @endforeach
                </div>

                        <div class="fr-no-print" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; padding:3px 4px; border-top:1px dashed #dbe2ea;">
                            <label class="fr-up" style="flex:1; min-width:110px; margin:0;">
                                {{-- multiple: boleh pilih beberapa file sekaligus --}}
                                <input type="file" id="fr-img-input" accept="image/*" multiple style="display:none;"
                                       onchange="frAddImages(this)">
                                <span>+ Tambah gambar</span>
                            </label>
                            <button type="button" class="btn-secondary btn-sm" style="font-size:0.65rem; padding:3px 8px;" onclick="frResetLayout()">↺ Tata ulang</button>
                            <span style="font-size:0.62rem; color:#94a3b8;">klik kanan gambar = hapus</span>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- ============ TOTAL ============ --}}
            <table style="border-top:none;">
                <colgroup><col style="width:41.5%"><col style="width:12%"><col style="width:34.5%"><col style="width:12%"></colgroup>
                {{-- Alignment mengikuti form asli: dua label kiri rata TENGAH,
                     label Grand Total rata KIRI. --}}
                <tr class="fr-total">
                    <td class="fr-c">TOTAL PART / MATERIAL COST (JUMLAH BIAYA PART / MATERIAL)</td>
                    <td>Rp. <span id="fr-total-part">{{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}</span></td>
                    <td>GRAND TOTAL COST / BIAYA TOTAL (PART + LABOUR)</td>
                    <td>Rp. <span id="fr-grand"></span></td>
                </tr>
                <tr class="fr-total">
                    <td class="fr-c">TOTAL LABOUR / JUMLAH BIAYA TENAGA KERJA (PEKERJAAN)</td>
                    <td class="fr-edit">Rp. <input type="number" name="labour_cost" id="fr-labour" step="1" min="0" value="{{ $v('labour_cost') }}" style="width:74%; text-align:right;"></td>
                    <td colspan="2" class="fr-edit" style="white-space:nowrap;">
                        SAID / TERBILANG : (<input type="text" name="note" value="{{ $v('note') }}" style="width:78%;">)
                    </td>
                </tr>
            </table>

            {{-- ============ LEGENDA WARNA ============ --}}
            <table class="fr-legend" style="border-top:none;">
                <tr>
                    <td style="padding:0;">
                        <table class="fr-legend-in">
                            <colgroup><col style="width:30%"><col style="width:22%"><col style="width:25%"><col style="width:23%"></colgroup>
                            <tr>
                                <td>White/Putih : Supplier / Bengkel Luar,</td>
                                <td>Pink/Merah : Security</td>
                                <td>Yellow/Kuning : Warehouse</td>
                                <td>Green/Hijau : Workshop</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

                </div>
            </div>

        <p class="fr-hint">Kolom bertanda garis putus-putus hanya penanda di layar — tidak ikut tercetak. Jenis pekerjaan boleh dicentang lebih dari satu.</p>

        <div class="fr-actions">
            <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary">Batalkan</a>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    @if($fr)
                <a href="{{ route('components.fr.pdf', [$comp->comp_id, $fr->fr_id]) }}" target="_blank" class="btn-secondary">🖨 Pratinjau PDF</a>
                    @endif
                <button type="submit" class="btn-primary">{{ $fr ? '💾 Simpan Perubahan' : '✅ Buat Form FR' }}</button>
                </div>
            </div>
        </form>
</div>

<script>
// Amount, total part, dan grand total dihitung di klien sebagai umpan balik
// cepat. Nilai final dihitung ulang dari qty x unit_price saat render PDF.
// Nol tidak ditampilkan, mengikuti form asli yang membiarkannya kosong.
(function () {
    const qty = document.getElementById('fr-qty');
    const unit = document.getElementById('fr-unit');
    const labour = document.getElementById('fr-labour');
    const amountCell = document.getElementById('fr-amount');
    const totalPart = document.getElementById('fr-total-part');
    const grand = document.getElementById('fr-grand');
    const fmt = new Intl.NumberFormat('id-ID');
    const show = v => (v > 0 ? fmt.format(v) : '');

    function recalc() {
        const amount = (parseFloat(qty.value) || 0) * (parseFloat(unit.value) || 0);
        amountCell.textContent = show(amount);
        totalPart.textContent = show(amount);
        grand.textContent = show(amount + (parseFloat(labour.value) || 0));
    }

    [qty, unit, labour].forEach(el => el && el.addEventListener('input', recalc));
    recalc();
})();

// ===== Objek gambar bebas: geser + ubah ukuran, seperti di Word =====
// Posisi & ukuran disimpan dalam PERSEN terhadap kanvasnya, bukan piksel,
// supaya tata letak di layar sama dengan hasil cetak PDF yang lebarnya beda.
(function () {
    const MIN_W = 5, MAX_W = 100;
    const IMG_DEFAULTS = [
        { x: 2, y: 3, w: 46 }, { x: 50, y: 3, w: 46 },
        { x: 2, y: 40, w: 46 }, { x: 50, y: 40, w: 46 },
        { x: 26, y: 70, w: 46 },
    ];

    const partCanvas = document.getElementById('fr-canvas');
    let drag = null;
    let nextIndex = partCanvas ? partCanvas.querySelectorAll('.fr-obj').length : 0;

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

    // Simpan posisi objek ke input tersembunyi miliknya.
    function store(obj) {
        const x = parseFloat(obj.style.left).toFixed(2);
        const y = parseFloat(obj.style.top).toFixed(2);
        const w = parseFloat(obj.style.width).toFixed(2);

        if (obj.classList.contains('fr-sig-obj')) {
            const role = obj.dataset.role;
            const set = (k, v) => {
                const el = document.querySelector(`input[name="signature_layout[${role}][${k}]"]`);
                if (el) el.value = v;
            };
            set('x', x); set('y', y); set('w', w);
            return;
        }

        obj.querySelector('input[name$="[x]"]').value = x;
        obj.querySelector('input[name$="[y]"]').value = y;
        obj.querySelector('input[name$="[w]"]').value = w;
    }

    // Satu mesin drag/resize dipakai kanvas gambar part maupun tanda tangan.
    function bind(canvas) {
        canvas.addEventListener('pointerdown', function (e) {
            const obj = e.target.closest('.fr-obj');
            if (!obj || e.button !== 0) return;

            document.querySelectorAll('.fr-obj').forEach(o => o.classList.remove('fr-obj-active'));
            obj.classList.add('fr-obj-active');

            drag = {
                obj,
                canvas,
                mode: e.target.classList.contains('fr-obj-handle') ? 'resize' : 'move',
                startX: e.clientX,
                startY: e.clientY,
                rect: canvas.getBoundingClientRect(),
                origX: parseFloat(obj.style.left) || 0,
                origY: parseFloat(obj.style.top) || 0,
                origW: parseFloat(obj.style.width) || 40,
            };
            obj.setPointerCapture(e.pointerId);
            e.preventDefault();
        });

        canvas.addEventListener('pointermove', function (e) {
            if (!drag || drag.canvas !== canvas) return;
            const dx = ((e.clientX - drag.startX) / drag.rect.width) * 100;
            const dy = ((e.clientY - drag.startY) / drag.rect.height) * 100;

            if (drag.mode === 'move') {
                drag.obj.style.left = clamp(drag.origX + dx, -5, 98) + '%';
                drag.obj.style.top = clamp(drag.origY + dy, -5, 98) + '%';
            } else {
                drag.obj.style.width = clamp(drag.origW + dx, MIN_W, MAX_W) + '%';
            }
            store(drag.obj);
        });

        ['pointerup', 'pointercancel'].forEach(ev =>
            canvas.addEventListener(ev, () => { drag = null; })
        );

        // Klik kanan = hapus gambar
        canvas.addEventListener('contextmenu', function (e) {
            const obj = e.target.closest('.fr-obj');
            if (!obj) return;
            e.preventDefault();

            if (obj.classList.contains('fr-sig-obj')) {
                if (!confirm('Hapus tanda tangan ini?')) return;
                obj.style.display = 'none';
                obj.querySelector('img').src = '';
                // Tandai supaya server menghapus gambar yang tersimpan
                const role = obj.dataset.role;
                let flag = document.querySelector(`input[name="signatures[${role}][remove_image]"]`);
                if (!flag) {
                    flag = document.createElement('input');
                    flag.type = 'hidden';
                    flag.name = `signatures[${role}][remove_image]`;
                    obj.closest('td').appendChild(flag);
                }
                flag.value = '1';
                return;
            }

            if (!confirm('Hapus gambar ini?')) return;
            obj.remove();
        });
    }

    if (partCanvas) bind(partCanvas);
    document.querySelectorAll('.fr-sig-canvas').forEach(bind);

    // ===== Tambah gambar part: berkali-kali, tanpa menimpa yang sudah ada =====
    window.frAddImages = function (input) {
        const files = Array.from(input.files || []);
        if (!files.length || !partCanvas) return;

        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const i = nextIndex++;
                const d = IMG_DEFAULTS[i] || IMG_DEFAULTS[IMG_DEFAULTS.length - 1];

                const obj = document.createElement('div');
                obj.className = 'fr-obj';
                obj.dataset.index = i;
                obj.style.cssText = `left:${d.x}%;top:${d.y}%;width:${d.w}%;`;
                obj.innerHTML =
                    '<img draggable="false">' +
                    '<span class="fr-obj-handle fr-no-print" title="Tarik untuk mengubah ukuran"></span>' +
                    `<input type="hidden" name="images[${i}][data]">` +
                    `<input type="hidden" name="images[${i}][x]" value="${d.x}">` +
                    `<input type="hidden" name="images[${i}][y]" value="${d.y}">` +
                    `<input type="hidden" name="images[${i}][w]" value="${d.w}">`;

                obj.querySelector('img').src = e.target.result;
                // Gambar baru dikirim sebagai data URL agar satu tombol bisa
                // dipakai berulang tanpa mengganggu berkas yang sudah ada.
                obj.querySelector('input[name$="[data]"]').value = e.target.result;

                partCanvas.appendChild(obj);
            };
            reader.readAsDataURL(file);
        });

        input.value = '';   // siap dipakai lagi
    };

    // Tanda tangan: pasang gambar ke objek bebasnya
    window.frSigPick = function (input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const obj = document.querySelector(`.fr-sig-obj[data-role="${input.dataset.role}"]`);
    const reader = new FileReader();
        reader.onload = function (e) {
            obj.querySelector('img').src = e.target.result;
            obj.style.display = '';
            store(obj);
        };
        reader.readAsDataURL(file);
    };

    // Tata ulang gambar part ke komposisi bawaan
    window.frResetLayout = function () {
        if (!partCanvas) return;
        partCanvas.querySelectorAll('.fr-obj').forEach((obj, i) => {
            const d = IMG_DEFAULTS[i] || IMG_DEFAULTS[IMG_DEFAULTS.length - 1];
            obj.style.left = d.x + '%';
            obj.style.top = d.y + '%';
            obj.style.width = d.w + '%';
            store(obj);
        });
    };
})();
</script>

</x-app-layout>
