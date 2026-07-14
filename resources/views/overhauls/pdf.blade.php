<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Serah Terima Overhaul — {{ $component->serial_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .header h2 { margin: 4px 0 0; font-size: 12px; font-weight: normal; color: #555; }
        .header p { margin: 2px 0; font-size: 10px; color: #888; }
        .doc-number { text-align: right; font-size: 10px; color: #888; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; font-size: 10px; }
        th { background-color: #e8e8e8; font-weight: bold; }
        h3 { font-size: 12px; margin: 16px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-green { background: #d4edda; color: #155724; }
        .badge-yellow { background: #fff3cd; color: #856404; }
        .badge-red { background: #f8d7da; color: #721c24; }
        .signature-section { margin-top: 40px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 33%; text-align: center; vertical-align: top; padding: 0 10px; }
        .signature-box p { margin: 0; font-size: 10px; }
        .signature-line { margin-top: 50px; border-top: 1px solid #000; display: inline-block; width: 120px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>

    <div class="doc-number">
        No. Dokumen: BAST/PRC/{{ $component->comp_id }}/{{ $component->created_at->format('m/Y') }}
    </div>

    <div class="header">
        <h1>BERITA ACARA SERAH TERIMA</h1>
        <h2>OVERHAUL KOMPONEN ALAT BERAT</h2>
        <p>PT Saptaindra Sejati (SIS) — Plant Rebuild Centre (PRC)</p>
    </div>

    <h3>A. Data Komponen</h3>
    <table>
        <tr>
            <th width="30%">Serial Number</th>
            <td>{{ $component->serial_number }}</td>
        </tr>
        <tr>
            <th>Model / Tipe Alat</th>
            <td>{{ $component->model_type }}</td>
        </tr>
        <tr>
            <th>Status Akhir</th>
            <td>
                @if($component->status == 'Ready for Use')
                    <span class="badge badge-green">Ready for Use (RFU)</span>
                @else
                    <span class="badge badge-yellow">{{ $component->status }} — Tahap {{ $component->current_stage }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Tanggal Masuk PRC</th>
            <td>{{ $component->created_at->format('d M Y, H:i') }} WIB</td>
        </tr>
        <tr>
            <th>Tanggal Selesai</th>
            <td>{{ $component->updated_at->format('d M Y, H:i') }} WIB</td>
        </tr>
    </table>

    <h3>B. Riwayat Pengerjaan (Timeline)</h3>
    <table>
        <thead>
            <tr>
                <th width="8%">Tahap</th>
                <th width="22%">Nama Tahap</th>
                <th width="20%">Petugas</th>
                <th width="18%">Waktu Mulai</th>
                <th width="18%">Waktu Selesai</th>
                <th width="14%">Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($component->overhaulLogs->sortBy('stage_number') as $log)
            <tr>
                <td style="text-align: center;">{{ $log->stage_number }}</td>
                <td>{{ $stageNames[$log->stage_number] ?? '-' }}</td>
                <td>{{ $log->mechanic ? $log->mechanic->name : 'Sistem' }}</td>
                <td>{{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d/m/Y H:i') : '-' }}</td>
                <td>
                    @if($log->start_time && $log->end_time)
                        {{ \Carbon\Carbon::parse($log->start_time)->diffForHumans(\Carbon\Carbon::parse($log->end_time), true) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>C. Hasil Inspeksi & Pengukuran (Measuring)</h3>
    <table>
        <thead>
            <tr>
                <th width="40%">Nama Part / Komponen</th>
                <th width="25%">Nilai Aktual (mm)</th>
                <th width="35%">Keputusan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($component->inspectionDetails as $insp)
            <tr>
                <td>{{ $insp->part_name }}</td>
                <td style="text-align: center;">{{ $insp->actual_value }}</td>
                <td>
                    @if($insp->decision == 'Replace')
                        <span class="badge badge-red">Replace (Ganti Baru)</span>
                    @elseif($insp->decision == 'Repair')
                        <span class="badge badge-yellow">Repair (Machining)</span>
                    @else
                        <span class="badge badge-green">Reused (Pakai Ulang)</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #999;">Tidak ada data inspeksi tercatat.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <h3>D. Permintaan Suku Cadang (Parts Request)</h3>
    <table>
        <thead>
            <tr>
                <th width="40%">Nama Part</th>
                <th width="15%">Qty</th>
                <th width="25%">Status Gudang</th>
                <th width="20%">Tanggal Request</th>
            </tr>
        </thead>
        <tbody>
            @forelse($component->partRequests as $pr)
            <tr>
                <td>{{ $pr->part_name }}</td>
                <td style="text-align: center;">{{ $pr->qty }}</td>
                <td>
                    @if($pr->status == 'Available')
                        <span class="badge badge-green">Available</span>
                    @elseif($pr->status == 'Out of Stock')
                        <span class="badge badge-red">Out of Stock</span>
                    @else
                        <span class="badge badge-yellow">Pending</span>
                    @endif
                </td>
                <td>{{ $pr->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">Tidak ada permintaan suku cadang.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <p>Mekanik,</p>
            <div class="signature-line"></div>
            <p style="margin-top: 4px;">( ________________________ )</p>
        </div>
        <div class="signature-box">
            <p>QC Inspector,</p>
            <div class="signature-line"></div>
            <p style="margin-top: 4px;">( ________________________ )</p>
        </div>
        <div class="signature-box">
            <p>Supervisor,</p>
            <div class="signature-line"></div>
            <p style="margin-top: 4px;">( ________________________ )</p>
        </div>
    </div>

    <div class="footer">
        Dokumen ini di-generate secara otomatis oleh sistem SIS-OCMS pada {{ now()->format('d M Y, H:i') }} WIB.<br>
        &copy; {{ date('Y') }} PT Saptaindra Sejati — Plant Rebuild Centre
    </div>

</body>
</html>
