    @if(!$isReviewMode)
    {{-- Realtime: pantau perubahan status komponen (stage, approval, part request) --}}
    <script>
        (function() {
            let fingerprint = null;
            let formDirty = false;

            // Jangan auto-reload kalau user sedang mengetik di form
            // (form inspeksi, remarks, dsb.) agar isian tidak hilang.
            document.addEventListener('input', function(e) {
                if (e.target.closest('form')) formDirty = true;
            });

            ocmsPoll('{{ route('status.component', $comp->comp_id) }}', 8000, function(data) {
                if (fingerprint === null) {
                    fingerprint = data.fingerprint;
                    return;
                }
                if (data.fingerprint === fingerprint) return;

                fingerprint = data.fingerprint;

                if (formDirty) {
                    // Tampilkan banner agar user reload manual tanpa kehilangan isian
                    if (!document.getElementById('staleBanner')) {
                        const banner = document.createElement('div');
                        banner.id = 'staleBanner';
                        banner.style.cssText = 'position:fixed; top:76px; left:50%; transform:translateX(-50%); z-index:500; background:rgba(212,175,55,0.95); color:#0B2B26; padding:12px 20px; border-radius:12px; font-size:0.8rem; font-weight:700; box-shadow:0 8px 32px rgba(0,0,0,0.4); cursor:pointer; display:flex; align-items:center; gap:10px;';
                        banner.innerHTML = '🔄 Status komponen berubah — klik untuk memuat ulang';
                        banner.onclick = () => location.reload();
                        document.body.appendChild(banner);
                    }
                } else {
                    location.reload();
                }
            });
        })();
    </script>

    {{-- Action Section --}}
    <div class="section">
        <div class="section-title fade-up">Aksi</div>
        <div class="glass-card fade-up">
            @if($comp->current_stage < 7)
                @if($comp->is_waiting_approval)
                    <div style="background: var(--accent-gold-dim); border: 1px solid rgba(212,175,55,0.15); border-radius: 14px; padding: 24px; margin-bottom: 24px; text-align: center;">
                        <p style="font-size: 1rem; font-weight: 700; color: var(--accent-gold); margin-bottom: 8px;">⏳ Menunggu Approval Group Leader / Supervisor</p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Komponen ini sedang menunggu persetujuan atasan untuk lanjut ke Tahap {{ $comp->current_stage + 1 }}.</p>
                    </div>
                    
                    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                        @ocmsApprove
                        <div style="display: flex; gap: 12px;">
                            <form action="{{ route('components.rejectStage', $comp->comp_id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-secondary" style="color: var(--accent-red); border-color: rgba(248,113,113,0.3);">❌ Tolak</button>
                            </form>
                            <form action="{{ route('components.approveStage', $comp->comp_id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, var(--accent-green), #059669);">✅ Approve ke Tahap {{ $comp->current_stage + 1 }}</button>
                            </form>
                        </div>
                        @else
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Hanya Group Leader, Supervisor, atau jabatan head yang bisa memberikan approval.</span>
                        @endocmsApprove
                    </div>
                @else
                    <form action="{{ route('components.updateStage', $comp->comp_id) }}" method="POST">
                        @csrf

                        {{-- Form inspeksi digital hanya untuk komponen yang TIDAK
                             memakai spreadsheet Measurement (spreadsheet menggantikannya) --}}
                        @if($comp->current_stage == 2 && !$comp->gsheet_measurement_url && !$comp->gsheet_subassy_measurement_url)
                        <div style="background: var(--accent-purple-dim); border: 1px solid rgba(167, 139, 250, 0.15); border-radius: 14px; padding: 28px; margin-bottom: 24px;">
                            <div class="section-title" style="color: var(--accent-purple); margin-bottom: 16px;">📐 Form Inspeksi Digital (Measurement & Inspection)</div>
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
                                <input type="number" step="0.01" min="0" name="parts[{{ $index }}][actual_value]" class="ocms-input" placeholder="0.00" value="{{ old('parts.'.$index.'.actual_value') }}" required>
                                <select name="parts[{{ $index }}][decision]" class="ocms-select" required>
                                    <option value="" disabled selected>Pilih Keputusan...</option>
                                    <option value="Reused" {{ old('parts.'.$index.'.decision') == 'Reused' ? 'selected' : '' }}>🟢 Reused (Pakai Kembali)</option>
                                    <option value="Repair" {{ old('parts.'.$index.'.decision') == 'Repair' ? 'selected' : '' }}>🟡 Repair (Perbaikan)</option>
                                    <option value="Replace" {{ old('parts.'.$index.'.decision') == 'Replace' ? 'selected' : '' }}>🔴 Replace (Ganti Baru)</option>
                                </select>
                            </div>
                            @endforeach
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 12px;">*Jika ada keputusan "Replace", sistem Smart Inventory akan otomatis membuat Part Request (PR) ke Gudang. Keputusan "Repair" akan otomatis membuat draft Fabrication Request (FR).</p>
                        </div>
                        @endif

                        <div style="margin-bottom: 24px;">
                            <label class="ocms-label" style="display: block; margin-bottom: 8px;">Catatan / Remarks (Opsional)</label>
                            <textarea name="remarks" class="ocms-input" placeholder="Tambahkan catatan untuk atasan sebelum mengajukan approval..." style="width: 100%; min-height: 80px; resize: vertical;"></textarea>
                        </div>

                        {{-- Stage 2-5 wajib approval GL/Supervisor; stage 1 & 6 lanjut otomatis --}}
                        @php $needsApproval = in_array($comp->current_stage, [2, 3, 4, 5]); @endphp
                        <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                            @ocmsOperate
                            <button type="submit" class="btn-primary">
                                {{ $needsApproval
                                    ? 'Ajukan Approval ke Tahap ' . ($comp->current_stage + 1) . ' →'
                                    : 'Selesaikan Tahap & Lanjut ke Tahap ' . ($comp->current_stage + 1) . ' →' }}
                            </button>
                            @else
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Hanya role operasional (Mekanik, GL, Supervisor, Head) yang bisa mengajukan proses.</span>
                            @endocmsOperate
                        </div>
                    </form>
                @endif
            @else
                {{-- Stage 7 (RFU): panel penutup ada di atas; di sini hanya navigasi --}}
                <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ route('components.index') }}" class="btn-secondary">← Kembali</a>
                    <span style="font-size: 0.8rem; color: var(--accent-green); font-weight: 600;">🎉 Overhaul selesai — komponen berstatus RFU</span>
                </div>
            @endif
        </div>
    </div>
    @endif
