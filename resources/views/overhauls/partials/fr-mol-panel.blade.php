    {{-- FR & MOL Panel (scan + daftar) — hanya Stage 2 (Disassembly) & 3 (Machining).
         Stage 4+ memakai panel Assembly / Test Bench / Painting / Delivery sendiri. --}}
    @if(in_array($viewedStage, [2, 3], true))
    <div class="section" id="fr-panel">
        <div class="section-title fade-up">Dokumen FR & MOL</div>
        <div class="glass-card fade-up">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px;">
                @if($comp->major_category === 'Engine')
                    Scan spreadsheet <strong>Disassembly</strong> (SALVAGE → FR, REPLACE → Part Request/MOL).
                @elseif($comp->gsheet_url)
                    Scan spreadsheet <strong>Inspection &amp; Disassembly</strong> (U/R / SALVAGE → FR, R/N / REPLACE → Part Request/MOL).
                @else
                    Scan spreadsheet <strong>Inspection</strong> (U/R → FR, R/N → Part Request/MOL).
                @endif
                Form internal: Repair → FR, Replace → PR/MOL.
            </p>
            <p id="fr-scan-profile" style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 16px;"></p>

            @ocmsOperate
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <button type="button" id="fr-scan-btn" class="btn-primary">🔍 Scan Spreadsheet (FR & MOL)</button>
                <a href="{{ route('components.fr.create', $comp->comp_id) }}" class="btn-secondary" style="text-decoration:none;">📝 Form FR Kosong (PLO/09/F-021)</a>
                <a href="{{ $comp->gsheet_sdr_url ?: 'https://docs.google.com/spreadsheets/d/1HvxiqXGEvH_nscYugPjOEfgIdq9Ps9nEyKqt_vNrd_8/edit?usp=sharing' }}" target="_blank" class="btn-secondary" style="text-decoration:none;">📊 SDR</a>
                <a href="https://llk-parts.ru/#" target="_blank" class="btn-secondary" style="text-decoration:none;">🔗 LLK Parts Catalog</a>
                <span id="fr-scan-status" style="font-size: 0.75rem; color: var(--text-muted); align-self: center;"></span>
            </div>
            @endocmsOperate

            {{-- Tab Toggle Buttons --}}
            <div style="display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid rgba(var(--ink), 0.06);">
                <button type="button" id="tab-fr-btn" class="btn-primary" style="border-radius: 8px 8px 0 0; padding: 8px 24px; font-size: 0.85rem;">
                    📋 Fabrication Request ({{ $comp->fabricationRequests->count() }})
                </button>
                <button type="button" id="tab-mol-btn" class="btn-secondary" style="border-radius: 8px 8px 0 0; padding: 8px 24px; font-size: 0.85rem; margin-left: 4px;">
                    📦 Material Order List ({{ $comp->partRequests->count() }})
                </button>
            </div>

            {{-- TAB: FR List --}}
            <div id="tab-fr-content" style="padding-top: 16px;">
                <div id="fr-list-wrap">
                    @if($comp->fabricationRequests->count() > 0)
                    <div class="table-scroll" style="padding: 0;">
                        <table class="ocms-table" id="fr-table">
                            <thead>
                                <tr>
                                    <th>No. FR</th>
                                    <th>Part</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comp->fabricationRequests as $fr)
                                <tr data-fr-id="{{ $fr->fr_id }}">
                                    <td class="mono" style="font-size: 0.75rem;">{{ $fr->fr_number }}</td>
                                    <td style="font-weight: 600;">
                                        {{ $fr->part_name }}
                                        @if($fr->section)
                                            <span style="font-size:0.65rem; font-weight:600; padding:1px 6px; border-radius:6px; background:rgba(96,165,250,0.15); color:#93c5fd;">{{ $fr->section }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fr->status === 'done')
                                            <span class="badge badge-green">Done</span>
                                        @elseif($fr->status === 'printed')
                                            <span class="badge badge-cyan">Printed</span>
                                        @else
                                            <span class="badge badge-gold">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <a href="{{ route('components.fr.edit', [$comp->comp_id, $fr->fr_id]) }}" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">✏️ Edit Form PLO/09/F-021</a>
                                            <a href="{{ route('components.fr.pdf', [$comp->comp_id, $fr->fr_id]) }}" target="_blank" class="btn-secondary" style="padding: 4px 10px; font-size: 0.7rem; text-decoration: none;">🖨 PDF</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p id="fr-empty-msg" style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                        Belum ada Fabrication Request untuk komponen ini.
                    </p>
                    @endif
                </div>
            </div>

            {{-- TAB: MOL List --}}
            <div id="tab-mol-content" style="display: none; padding-top: 16px;">
                {{-- Upload Dokumen MOL --}}
                @ocmsOperate
                <div style="margin-bottom: 16px; padding: 12px; border: 1px solid rgba(var(--ink), 0.08); border-radius: 10px; background: rgba(0,0,0,0.1);">
                    <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">📄 Dokumen MOL</div>
                    @if($comp->mol_document_path)
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <a href="{{ asset($comp->mol_document_path) }}" target="_blank" class="btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none;">👁 Lihat Dokumen MOL</a>
                            <form id="mol-upload-form" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
                                @csrf
                                <input type="file" name="mol_document" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.75rem; max-width: 220px;" id="mol-doc-input">
                                <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 0.75rem;">🔄 Upload Ulang</button>
                            </form>
                            <button type="button" id="mol-doc-delete-btn" class="btn-secondary" style="padding: 6px 14px; font-size: 0.75rem; color: #f87171;">🗑 Hapus</button>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">File: {{ basename($comp->mol_document_path) }}</div>
                    @else
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <form id="mol-upload-form" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
                                @csrf
                                <input type="file" name="mol_document" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.75rem; max-width: 220px;" id="mol-doc-input">
                                <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 0.75rem;">📤 Upload Dokumen MOL</button>
                            </form>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Format: PDF, JPG, PNG (maks. 10 MB)</div>
                    @endif
                    <span id="mol-upload-status" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;"></span>
                </div>
                @else
                {{-- Non-mechanic: hanya bisa lihat dokumen --}}
                @if($comp->mol_document_path)
                <div style="margin-bottom: 16px;">
                    <a href="{{ asset($comp->mol_document_path) }}" target="_blank" class="btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none;">👁 Lihat Dokumen MOL</a>
                    <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 8px;">{{ basename($comp->mol_document_path) }}</span>
                </div>
                @endif
                @endocmsOperate

                {{-- MOL Part Request List --}}
                @if($comp->partRequests->count() > 0)
                <div class="table-scroll" style="padding: 0;">
                    <table class="ocms-table">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Section</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comp->partRequests as $pr)
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);">{{ $pr->part_name }}</td>
                                <td>
                                    @if($pr->section)
                                        <span style="font-size:0.65rem; font-weight:600; padding:1px 6px; border-radius:6px; background:rgba(96,165,250,0.15); color:#93c5fd;">{{ $pr->section }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
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
                @else
                <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                    Belum ada Part Request / MOL untuk komponen ini.
                </p>
                @endif
            </div>
        </div>
    </div>
    @endif
