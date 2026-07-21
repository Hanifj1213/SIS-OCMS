<x-app-layout>

@php
    // Tahap yang SEDANG DILIHAT. Saat mekanik me-review tahap lama,
    // $reviewStage terisi dan panel per-tahap (FR/MOL) harus mengikutinya —
    // memakai current_stage saja membuat panel Stage 2 ikut muncul di Stage 1.
    $viewedStage = $reviewStage ?? $comp->current_stage;
@endphp

    <div class="section fade-up">
        <div class="ocms-page-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
            <div>
            <h1>{{ $comp->serial_number }}</h1>
            <p>{{ $comp->egi ?? $comp->model_type }} — {{ $comp->major_category }}</p>
            </div>
            @ocmsDeveloper
            <a href="{{ route('components.edit', $comp->comp_id) }}" class="btn-secondary btn-sm" style="text-decoration: none;">✏️ Edit Komponen</a>
            @endocmsDeveloper
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error fade-up">
            <strong>❌ Terjadi Kesalahan:</strong>
            <ul style="list-style: disc; padding-left: 20px; margin-top: 6px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Halaman ini dipecah per section ke resources/views/overhauls/partials/.
         Variabel bersama ($viewedStage, $checksheetStage, $gsheet*EmbedUrl, dst.)
         dihitung di sini lalu diwariskan ke semua partial lewat @include. --}}

    @include('overhauls.partials.damage-core')
    @include('overhauls.partials.show-styles')
    @include('overhauls.partials.progress-bar')
    @include('overhauls.partials.timeline')
    @include('overhauls.partials.inspection-results')

    {{-- Panel per-tahap: FR/MOL (stage 2-3), output FR (3), painting (5), RFU (7) --}}
    @include('overhauls.partials.fr-mol-panel')
    @include('overhauls.partials.fr-output-stage3')
    @include('overhauls.partials.painting-stage5')
    @include('overhauls.partials.rfu-stage7')

    {{-- Checksheet Section (Inline Interactive / Review) --}}
    @php
        $checksheetStage = $reviewStage ?? $comp->current_stage;
        $currentChecksheet = $comp->checksheets->where('stage_number', $checksheetStage)->first();
        $isReviewMode = $reviewStage !== null;
    @endphp
    @include('overhauls.partials.checksheet-history')

    @php
        // Spreadsheet tahap 2: mainline + sub-assy (disassembly & measurement).
        // PC2000-8 lama tanpa salinan disassembly memakai sheet legacy.
        // Tahap 4 (Assembly) & 5 (Test Bench) memakai salinan GSheet sendiri.
        $toEmbed = function (?string $url) {
            if (!$url) {
                return null;
            }
            if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $m)) {
                $url = 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/edit';
            }

            return $url . (str_contains($url, '?') ? '&' : '?') . 'rm=minimal&usp=sharing';
        };
        $toEditLink = function (?string $url) {
            if (!$url) {
                return null;
            }
            if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $m)) {
                return 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/edit?usp=sharing';
            }

            return $url;
        };

        $gsheetEmbedUrl = null;
        $gsheetMeasurementEmbedUrl = null;
        $gsheetSubassyDisassyEmbedUrl = null;
        $gsheetSubassyMeasureEmbedUrl = null;
        $gsheetAssemblyEmbedUrl = null;
        $gsheetTestbenchEmbedUrl = null;

        if ($checksheetStage == 4 && $comp->gsheet_assembly_url) {
            $gsheetAssemblyEmbedUrl = $toEmbed($comp->gsheet_assembly_url);
        }
        if ($checksheetStage == 5 && $comp->gsheet_testbench_url) {
            $gsheetTestbenchEmbedUrl = $toEmbed($comp->gsheet_testbench_url);
        }

        if ($checksheetStage == 2) {
            $rawGsheet = $comp->gsheet_url
                ?: (
                    $comp->major_category === 'Engine' && $comp->egi === 'PC2000-8'
                        ? 'https://docs.google.com/spreadsheets/d/1kIjBP4R4MWPkpFzXIU7Smcwnyy2DoR2Pzj2oggmn3tY/edit?usp=sharing'
                        : null
                );
            if ($rawGsheet) {
                $gsheetEmbedUrl = $toEmbed($rawGsheet);
            }
            if ($comp->gsheet_measurement_url) {
                $gsheetMeasurementEmbedUrl = $toEmbed($comp->gsheet_measurement_url);
            }
            if ($comp->gsheet_subassy_disassembly_url) {
                $gsheetSubassyDisassyEmbedUrl = $toEmbed($comp->gsheet_subassy_disassembly_url);
            }
            if ($comp->gsheet_subassy_measurement_url) {
                $gsheetSubassyMeasureEmbedUrl = $toEmbed($comp->gsheet_subassy_measurement_url);
            }
        }
        $hasDisassyPanel = $gsheetEmbedUrl || $gsheetSubassyDisassyEmbedUrl;
        $hasMeasurePanel = $gsheetMeasurementEmbedUrl || $gsheetSubassyMeasureEmbedUrl;
        $hasStageGsheetPanel = $gsheetAssemblyEmbedUrl || $gsheetTestbenchEmbedUrl;
    @endphp
    @include('overhauls.partials.gsheet-panels')
    @include('overhauls.partials.checksheet-interactive')
    @include('overhauls.partials.action-panel')
    @include('overhauls.partials.fr-mol-scripts')

</x-app-layout>
