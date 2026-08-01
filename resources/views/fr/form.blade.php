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

@include('fr.partials.form-style')

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


            @include('fr.partials.sheet-header')
            @include('fr.partials.sheet-identity')
            @include('fr.partials.sheet-material')
            @include('fr.partials.sheet-total')

            @include('fr.partials.sheet-legend')

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


@include('fr.partials.form-scripts')

</x-app-layout>
