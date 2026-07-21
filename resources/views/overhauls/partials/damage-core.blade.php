    {{-- Damage Core Info + QR Code --}}
    <div class="section dc-layout" style="display: grid; grid-template-columns: 1fr 240px; gap: 20px;">
        <div class="glass-card fade-up">
            <div class="section-title" style="margin-bottom: 16px;">📋 Damage Core — Informasi Komponen</div>
            <div class="dc-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
                {{-- Left Column --}}
                <div class="dc-col-l" style="border-right: 1px solid rgba(var(--ink), 0.04);">
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">EGI</span>
                        <span class="dc-value mono" style="font-size: 0.85rem; font-weight: 600;">{{ $comp->egi ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Unit Code</span>
                        <span class="dc-value mono" style="font-size: 0.85rem;">{{ $comp->unit_code ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Unit Serial No.</span>
                        <span class="dc-value mono" style="font-size: 0.85rem;">{{ $comp->unit_serial_no ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Site / District</span>
                        <span class="dc-value" style="font-size: 0.85rem;">{{ $comp->site_district ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">SMR</span>
                        <span class="dc-value mono" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-cyan);">{{ $comp->smr ? number_format($comp->smr) : '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">Manifest / Way Bill</span>
                        <span class="dc-value" style="font-size: 0.85rem;">{{ $comp->manifest ?? $comp->way_bill ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 16px 10px 0;">
                        <span class="dc-label" style="color: var(--text-muted); width: 40%; font-size: 0.8rem;">RO</span>
                        <span class="dc-value" style="font-size: 0.85rem;">{{ $comp->ro_number ?? '-' }}</span>
                    </div>
                </div>
                {{-- Right Column --}}
                <div class="dc-col-r" style="padding-left: 16px;">
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Component Model</span>
                        <span class="dc-value" style="font-size: 0.85rem;"><span class="badge badge-cyan">{{ $comp->major_category }}</span></span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Comp Serial No.</span>
                        <span class="dc-value mono" style="font-size: 0.85rem; font-weight: 600;">{{ $comp->serial_number }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">P/N Assy</span>
                        <span class="dc-value mono" style="font-size: 0.85rem;">{{ $comp->pn_assy ?? '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Status OVH</span>
                        <span class="dc-value">
                            @if($comp->status_ovh == 'SCHEDULE')
                                <span class="badge badge-green">📅 SCHEDULE</span>
                            @elseif($comp->status_ovh == 'UNSCHEDULE')
                                <span class="badge badge-gold">⚠️ UNSCHEDULE</span>
                            @else
                                <span style="font-size: 0.85rem;">-</span>
                            @endif
                        </span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Core Category</span>
                        <span class="dc-value" style="font-size: 0.85rem;">
                            @if($comp->core_category == 'A')
                                <span class="badge badge-cyan" title="Kondisi komponen running, lengkap (Schedule Overhaul)">Cat. A</span>
                            @elseif($comp->core_category == 'B')
                                <span class="badge badge-gold" title="Kondisi Main Shaft Jammed, Gear broken (Unschedule Overhaul)">Cat. B</span>
                            @elseif($comp->core_category == 'C')
                                <span class="badge badge-red" title="Kondisi Housing Jebol / Broken (Unschedule Overhaul)">Cat. C</span>
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Life Time</span>
                        <span class="dc-value mono" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-cyan);">{{ $comp->life_time ? number_format($comp->life_time) : '-' }}</span>
                    </div>
                    <div class="dc-info-row" style="display: flex; padding: 10px 0; border-bottom: 1px solid rgba(var(--ink), 0.03);">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Date Received</span>
                        <span class="dc-value" style="font-size: 0.85rem;">{{ $comp->date_defitted ? $comp->date_defitted->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="dc-info-row dc-info-row-stacked" style="display: flex; padding: 10px 0;">
                        <span class="dc-label" style="color: var(--text-muted); width: 45%; font-size: 0.8rem;">Status Overhaul</span>
                        <span class="dc-value">
                            @if($comp->status == 'On Progress')
                                <span class="badge badge-gold badge-wrap">🔧 {{ $stageNames[$comp->current_stage] ?? 'Tahap '.$comp->current_stage }}</span>
                            @else
                                <span class="badge badge-green badge-wrap">✅ Ready for Use (RFU)</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card fade-up" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="section-title">QR Code</div>
            @if($comp->qr_code_path)
                <img src="{{ asset($comp->qr_code_path) }}" alt="QR" style="width: 140px; height: 140px; border-radius: 12px; border: 1px solid var(--glass-border); padding: 6px; background: white; margin-top: 8px;">
            @else
                <div style="width: 140px; height: 140px; border-radius: 12px; background: rgba(var(--ink), 0.03); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">N/A</div>
            @endif
        </div>
    </div>
