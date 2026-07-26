<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fabrication Request — {{ $fr->fr_number }}</title>
    <style>
        @page { margin: 18mm 15mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.35;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: top; border: none; padding: 0; }
        .company { font-size: 11px; font-weight: bold; }
        .form-code { font-size: 9px; text-align: right; }
        .title-box {
            border: 2px solid #000;
            text-align: center;
            padding: 8px 4px;
            margin: 10px 0 12px;
        }
        .title-box h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 1px;
        }
        .title-box p { margin: 2px 0 0; font-size: 9px; }
        .meta-table, .parts-table, .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td, .meta-table th,
        .parts-table td, .parts-table th,
        .sign-table td, .sign-table th {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9px;
        }
        .meta-table th, .parts-table th { background: #eee; text-align: left; }
        .checkbox-row { margin: 8px 0 12px; }
        .checkbox-row span {
            display: inline-block;
            margin-right: 18px;
            font-size: 10px;
        }
        .box {
            border: 1px solid #000;
            min-height: 70px;
            padding: 6px;
            margin-bottom: 10px;
        }
        .box-title {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .fr-number {
            font-size: 11px;
            font-weight: bold;
            text-align: right;
            margin-bottom: 6px;
        }
        .muted { color: #555; font-size: 8px; }
        .sign-table td { height: 55px; vertical-align: bottom; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="company">PT. SURYA INTISINERGI<br>Overhaul Component Management System</td>
            <td class="form-code">No. Formulir: <strong>PLO/09/F-021</strong><br>Rev: 00</td>
        </tr>
    </table>

    <div class="title-box">
        <h1>FABRICATION REQUEST</h1>
        <p>PERMOHONAN FABRIKASI / PERBAIKAN KOMPONEN</p>
    </div>

    <div class="fr-number">No. FR: {{ $fr->fr_number }}</div>

    <table class="meta-table">
        <tr>
            <th style="width:22%">Unit Model</th>
            <td style="width:28%">{{ $component->egi ?? $component->model_type ?? '-' }}</td>
            <th style="width:22%">Component Model</th>
            <td style="width:28%">{{ $component->component_model ?? $component->major_category ?? '-' }}</td>
        </tr>
        <tr>
            <th>Unit Code</th>
            <td>{{ $component->unit_code ?? '-' }}</td>
            <th>WO / RO No.</th>
            <td>{{ $component->serial_number ?? '-' }}</td>
        </tr>
        <tr>
            <th>Site / District</th>
            <td>{{ $component->site_district ?? '-' }}</td>
            <th>P/N Assy</th>
            <td>{{ $component->pn_assy ?? '-' }}</td>
        </tr>
    </table>

    <div class="checkbox-row">
        <strong>Jenis Pekerjaan:</strong>
        <span>[{{ $fr->work_type === 'repair' ? 'X' : ' ' }}] Repair</span>
        <span>[{{ $fr->work_type === 'fabrikasi' ? 'X' : ' ' }}] Fabrikasi</span>
        <span>[{{ $fr->work_type === 'modifikasi' ? 'X' : ' ' }}] Modifikasi</span>
    </div>

    <table class="parts-table">
        <thead>
            <tr>
                <th style="width:8%">No</th>
                <th style="width:22%">Part Number</th>
                <th style="width:50%">Part Name</th>
                <th style="width:10%">Qty</th>
                <th style="width:10%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center">1</td>
                <td>{{ $fr->part_number ?? '-' }}</td>
                <td>{{ $fr->part_name }}@if($fr->section) <span style="font-size:9px">({{ $fr->section }})</span>@endif</td>
                <td style="text-align:center">{{ $fr->qty }}</td>
                <td style="text-align:center">Pcs</td>
            </tr>
            @for ($i = 0; $i < 4; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="box">
        <div class="box-title">Detail Instruction</div>
        <div>{!! $fr->instruction ? e($fr->instruction) : '&nbsp;' !!}</div>
    </div>

    <div class="box" style="min-height: 100px;">
        <div class="box-title">Gambar &amp; Dimensi</div>
        <p class="muted">(Diisi manual / lampiran sketsa)</p>
    </div>

    <table class="sign-table">
        <thead>
            <tr>
                <th>Requested By<br><span class="muted">(Mekanik / Supervisor)</span></th>
                <th>Checked By<br><span class="muted">(Supervisor)</span></th>
                <th>Approved By<br><span class="muted">(Management)</span></th>
                <th>Received By<br><span class="muted">(Fabrikasi)</span></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if($fr->creator)
                        {{ $fr->creator->name }}<br>
                        <span class="muted">{{ $fr->created_at?->format('d/m/Y') }}</span>
                    @endif
                </td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:8px;">
        Status: {{ strtoupper($fr->status) }} | Sumber data: {{ strtoupper($fr->source) }} |
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
