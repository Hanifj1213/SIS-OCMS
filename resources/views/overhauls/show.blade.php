<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>{{ $comp->serial_number }}</h1>
            <p>{{ $comp->model_type }}</p>
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

    {{-- Info + QR Code --}}
    <div class="section" style="display: grid; grid-template-columns: 1fr 240px; gap: 20px;">
        <div class="glass-card fade-up">
            <div class="section-title" style="margin-bottom: 16px;">Informasi Utama</div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); width: 35%; font-size: 0.85rem;">Serial Number</td>
                    <td class="mono" style="padding: 10px 0; font-size: 0.95rem;">{{ $comp->serial_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Model Alat</td>
                    <td style="padding: 10px 0; font-size: 0.85rem;">{{ $comp->model_type }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Status</td>
                    <td style="padding: 10px 0;">
                        @if($comp->status == 'On Progress')
                            <span class="badge badge-gold">🔧 {{ $stageNames[$comp->current_stage] ?? 'Tahap '.$comp->current_stage }}</span>
                        @else
                            <span class="badge badge-green">✅ Ready for Use (RFU)</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted); font-size: 0.85rem;">Terdaftar</td>
                    <td style="padding: 10px 0; font-size: 0.85rem; color: var(--text-secondary);">{{ $comp->created_at->format('d M Y H:i') }} WIB</td>
                </tr>
            </table>
        </div>
        <div class="glass-card fade-up" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="section-title">QR Code</div>
            @if($comp->qr_code_path)
                <img src="{{ asset($comp->qr_code_path) }}" alt="QR" style="width: 140px; height: 140px; border-radius: 12px; border: 1px solid var(--glass-border); padding: 6px; background: white; margin-top: 8px;">
            @else
                <div style="width: 140px; height: 140px; border-radius: 12px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">N/A</div>
            @endif
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="section">
        <div class="section-title fade-up">Progress Overhaul</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div class="stage-bar">
                @for($i = 1; $i <= 8; $i++)
                    <div class="stage-node {{ $i < $comp->current_stage ? 'completed' : ($i == $comp->current_stage ? 'active' : 'pending') }}">
                        <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                        <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                    </div>
                    @if($i < 8)
                        <div class="stage-connector {{ $i < $comp->current_stage ? 'done' : 'undone' }}"></div>
                    @endif
                @endfor
            </div>
        </div>
    </div>

    {{-- Timeline Log --}}
    <div class="section">
        <div class="section-title fade-up">Riwayat Pengerjaan</div>
        <div class="glass-card fade-up">
            @forelse($comp->overhaulLogs->sortBy('stage_number') as $log)
            <div style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.03); {{ $loop->last ? 'border: none;' : '' }}">
                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
                    {{ $log->end_time ? 'background: var(--accent-green-dim); color: var(--accent-green);' : 'background: var(--accent-cyan-dim); color: var(--accent-cyan);' }}
                ">{{ $log->stage_number }}</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">{{ $stageNames[$log->stage_number] ?? 'Tahap '.$log->stage_number }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Petugas: {{ $log->mechanic ? $log->mechanic->name : 'Sistem' }}</div>
                        </div>
                        @if($log->end_time)
                            <span class="badge badge-green" style="font-size: 0.6rem;">Selesai</span>
                        @else
                            <span class="badge badge-cyan" style="font-size: 0.6rem;">● Aktif</span>
                        @endif
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; font-family: 'JetBrains Mono', monospace;">
                        {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('d/m/Y H:i') : '-' }}
                        → {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d/m/Y H:i') : 'Sekarang' }}
                    </div>
                    @if($log->notes)
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; font-style: italic;">"{{ $log->notes }}"</div>
                    @endif
                </div>
            </div>
            @empty
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 24px;">Belum ada riwayat.</p>
            @endforelse
        </div>
    </div>

    {{-- Inspection Results --}}
    @if($comp->inspectionDetails->count() > 0)
    <div class="section">
        <div class="section-title fade-up">Hasil Inspeksi & Pengukuran</div>
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden;">
            <table class="ocms-table">
                <thead>
                    <tr>
                        <th>Nama Part</th>
                        <th>Nilai Aktual (mm)</th>
                        <th>Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comp->inspectionDetails as $insp)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $insp->part_name }}</td>
                        <td class="mono">{{ $insp->actual_value }}</td>
                        <td>
                            @if($insp->decision == 'Replace')
                                <span class="badge badge-red">Replace</span>
                            @elseif($insp->decision == 'Repair')
                                <span class="badge badge-gold">Repair</span>
                            @else
                                <span class="badge badge-green">Reused</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Part Requests --}}
    @if($comp->partRequests->count() > 0)
    <div class="section">
        <div class="section-title fade-up">Permintaan Suku Cadang</div>
        <div class="glass-card fade-up" style="padding: 0; overflow: hidden;">
            <table class="ocms-table">
                <thead>
                    <tr>
                        <th>Part</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comp->partRequests as $pr)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $pr->part_name }}</td>
                        <td class="mono">{{ $pr->qty }}</td>
                        <td>
                            @if($pr->status == 'Pending')
                                <span class="badge badge-gold">⏳ Pending</span>
                            @elseif($pr->status == 'Available')
                                <span class="badge badge-green">✅ Available</span>
                            @else
                                <span class="badge badge-red">❌ Out of Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Action Section --}}
    <div class="section">
        <div class="section-title fade-up">Aksi</div>
        <div class="glass-card fade-up">
            @if($comp->current_stage < 8)
                <form action="{{ route('components.updateStage', $comp->comp_id) }}" method="POST">
                    @csrf

                    @if($comp->current_stage == 3)
                    <div style="background: var(--accent-purple-dim); border: 1px solid rgba(167, 139, 250, 0.15); border-radius: 14px; padding: 28px; margin-bottom: 24px;">
                        <div class="section-title" style="color: var(--accent-purple); margin-bottom: 16px;">📐 Form Inspeksi Digital (Measuring)</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 8px;">
                            <div class="ocms-label" style="margin: 0;">Nama Part</div>
                            <div class="ocms-label" style="margin: 0;">Nilai Aktual (mm)</div>
                            <div class="ocms-label" style="margin: 0;">Keputusan</div>
                        </div>
                        @php $parts = ['Crankshaft', 'Piston Ring', 'Cylinder Liner']; @endphp
                        @foreach($parts as $index => $part)
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 10px; align-items: center;">
                            <div>
                                <input type="hidden" name="parts[{{ $index }}][name]" value="{{ $part }}">
                                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">{{ $part }}</span>
                            </div>
                            <div>
                                <input type="number" step="0.01" min="0" name="parts[{{ $index }}][actual_value]" class="ocms-input" placeholder="0.00" required style="font-family: 'JetBrains Mono', monospace; padding: 10px 14px;">
                            </div>
                            <div>
                                <select name="parts[{{ $index }}][decision]" class="ocms-select" required style="padding: 10px 14px;">
                                    <option value="Reused">✅ Reused</option>
                                    <option value="Repair">🔧 Repair</option>
                                    <option value="Replace">🔴 Replace</option>
                                </select>
                            </div>
                        </div>
                        @endforeach
                        <p style="font-size: 0.7rem; color: var(--accent-red); margin-top: 12px; font-weight: 500;">⚠ "Replace" otomatis memicu permintaan parts ke gudang.</p>
                    </div>
                    @endif

                    @if($comp->current_stage == 6)
                    <div style="background: var(--accent-purple-dim); border: 1px solid rgba(167, 139, 250, 0.15); border-radius: 14px; padding: 28px; margin-bottom: 24px;">
                        <div class="section-title" style="color: var(--accent-purple); margin-bottom: 16px;">🧪 Quality Gate — Test Bench</div>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">Standar Tekanan Oli: <strong style="color: var(--text-primary);">40 – 50 psi</strong></p>
                        <label class="ocms-label">Tekanan Oli Aktual (psi)</label>
                        <input type="number" step="0.1" min="0" name="oil_pressure" class="ocms-input" placeholder="45.0" value="{{ old('oil_pressure') }}" required style="max-width: 300px; font-family: 'JetBrains Mono', monospace;">
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Nilai di luar 40-50 psi akan ditolak oleh Quality Gate.</p>
                    </div>
                    @endif

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                        <button type="submit" class="btn-primary">Proses ke Tahap {{ $comp->current_stage + 1 }} →</button>
                    </div>
                </form>
            @else
                <div style="background: var(--accent-green-dim); border: 1px solid rgba(52, 211, 153, 0.15); border-radius: 14px; padding: 24px; margin-bottom: 24px; text-align: center;">
                    <p style="font-size: 1rem; font-weight: 700; color: var(--accent-green);">🎉 Komponen telah selesai overhaul — Ready for Use (RFU)</p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                    <a href="{{ route('components.printPdf', $comp->comp_id) }}" target="_blank" class="btn-danger">🖨 Cetak Berita Acara (PDF)</a>
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
