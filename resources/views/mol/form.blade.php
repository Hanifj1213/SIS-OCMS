{{--
    Form MECHANIC ORDER LIST (MOL) — mengikuti template MOL.xlsx tab "ADD 1".
    Urutan blok: header WO/unit + note, legenda reason code A–K,
    lalu grid part (NO, FIGURE, INDEX, PART NUMBER, DESCRIPTION, QTY,
    COMP GROUP, REASON CODE, REMARK).
--}}
<x-app-layout>

<style>
    .mol-wrap { max-width: 1240px; margin: 0 auto; }
    .mol-paper {
        background: #fff; color: #111; border-radius: 10px; overflow: hidden;
        box-shadow: 0 24px 48px rgba(0,0,0,0.35); font-size: 11px; line-height: 1.3;
    }
    .mol-paper table { width: 100%; border-collapse: collapse; }
    .mol-paper td, .mol-paper th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
    .mol-title { text-align: center; font-weight: 900; font-size: 16px; letter-spacing: 1px; }
    .mol-label { font-weight: 700; font-size: 9.5px; text-transform: uppercase; }
    .mol-legend td { border: none; font-size: 8.5px; padding: 1px 6px; }
    .mol-paper input[type=text],
    .mol-paper input[type=number],
    .mol-paper input[type=date],
    .mol-paper select,
    .mol-paper textarea {
        width: 100%; border: none; background: #fffbe8; color: #111;
        font: inherit; padding: 2px 3px; box-sizing: border-box; border-radius: 2px;
    }
    .mol-paper input:focus, .mol-paper select:focus, .mol-paper textarea:focus { outline: 2px solid #2563eb; background: #fff; }
    .mol-paper input[readonly] { background: #f1f5f9; color: #475569; }
    .mol-grid th { background: #e5e7eb; font-size: 8.5px; text-transform: uppercase; text-align: center; }
    .mol-grid td { padding: 1px 2px; }
    .mol-center { text-align: center; }
    .mol-existing td { background: #f8fafc; color: #334155; font-size: 10px; }
    .mol-actions { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 20px; }
    .mol-scroll { overflow-x: auto; }
    @media (max-width: 900px) { .mol-paper table { min-width: 900px; } }
</style>

<div class="section fade-up mol-wrap">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary btn-sm" style="text-decoration:none;">← Kembali ke Komponen</a>
        <span class="badge badge-purple">Mechanic Order List · Tab ADD 1</span>
    </div>

    @if(session('success'))
    <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-error">
        <strong>Periksa isian:</strong>
        <ul style="margin:6px 0 0 16px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('components.mol.store', $comp->comp_id) }}">
        @csrf

        <div class="mol-paper mol-scroll">
            {{-- ===== HEADER ===== --}}
            <table>
                <tr>
                    <td style="width:50%; border:none;">
                        <strong>PT SAPTAINDRA SEJATI</strong><br>
                        <span style="font-size:9px;">NAROGONG REBUILD CENTER</span>
                    </td>
                    <td style="width:50%; border:none; text-align:right; font-size:9px;">
                        DOCUMENT NO: <strong>MOL/SIS/NRC/{{ date('Y') }}</strong>
                    </td>
                </tr>
            </table>

            <div class="mol-title" style="padding:4px 0 8px;">MECHANIC ORDER LIST</div>

            <table>
                <tr>
                    <td style="width:12%;" class="mol-label">WO No.</td>
                    <td style="width:21%;"><input type="text" name="mol_wo_number" value="{{ old('mol_wo_number', $comp->mol_wo_number) }}" placeholder="2600027492"></td>
                    <td style="width:12%;" class="mol-label">Order Type</td>
                    <td style="width:21%;"><input type="text" name="mol_order_type" value="{{ old('mol_order_type', $comp->mol_order_type ?: 'APL / ADD 1') }}"></td>
                    <td style="width:12%;" class="mol-label" rowspan="4" style="vertical-align:top;">Note</td>
                    <td style="width:22%;" rowspan="4">
                        <textarea name="mol_note" rows="6" placeholder="Code A, B, C, dan D khusus APL &amp; ADD 1&#10;Code E s/d K khusus ADD 2 dst">{{ old('mol_note', $comp->mol_note) }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="mol-label">Unit Code</td>
                    <td><input type="text" value="{{ $comp->unit_code ?: '-' }}" readonly></td>
                    <td class="mol-label">Order Date</td>
                    <td><input type="date" name="mol_order_date" value="{{ old('mol_order_date', $comp->mol_order_date ? \Carbon\Carbon::parse($comp->mol_order_date)->format('Y-m-d') : now()->format('Y-m-d')) }}"></td>
                </tr>
                <tr>
                    <td class="mol-label">Unit Model</td>
                    <td><input type="text" value="{{ $comp->model_type ?: $comp->egi }}" readonly></td>
                    <td class="mol-label">IR No.</td>
                    <td><input type="text" name="mol_ir_number" value="{{ old('mol_ir_number', $comp->mol_ir_number) }}"></td>
                </tr>
                <tr>
                    <td class="mol-label">Comp Model</td>
                    <td><input type="text" value="{{ $comp->component_model ?: $comp->major_category }} (SN: {{ $comp->serial_number }})" readonly></td>
                    <td class="mol-label">IR Date</td>
                    <td><input type="date" name="mol_ir_date" value="{{ old('mol_ir_date', $comp->mol_ir_date ? \Carbon\Carbon::parse($comp->mol_ir_date)->format('Y-m-d') : '') }}"></td>
                </tr>
            </table>

            {{-- ===== LEGENDA REASON CODE ===== --}}
            <table>
                <tr>
                    <td colspan="3" style="padding:0;">
                        <table class="mol-legend">
                            @foreach(array_chunk($orderCodes, 4, true) as $chunk)
                            <tr>
                                @foreach($chunk as $code => $desc)
                                <td style="width:25%;">{{ $desc }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>

            {{-- ===== GRID PART ===== --}}
            <table class="mol-grid">
                <thead>
                    <tr>
                        <th style="width:4%;">No</th>
                        <th style="width:8%;">Figure</th>
                        <th style="width:7%;">Index</th>
                        <th style="width:16%;">Part Number</th>
                        <th style="width:26%;">Description / Part Name</th>
                        <th style="width:6%;">Qty</th>
                        <th style="width:13%;">Comp Group</th>
                        <th style="width:8%;">Reason Code</th>
                        <th style="width:12%;">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris yang sudah tersimpan: read-only, hanya sebagai konteks --}}
                    @foreach($existing as $idx => $pr)
                    <tr class="mol-existing">
                        <td class="mol-center">{{ $idx + 1 }}</td>
                        <td>{{ $pr->figure ?: '-' }}</td>
                        <td>{{ $pr->index_no ?: '-' }}</td>
                        <td>{{ $pr->part_number ?: '-' }}</td>
                        <td>{{ $pr->part_name }}</td>
                        <td class="mol-center">{{ $pr->qty }}</td>
                        <td>{{ $pr->section ?: '-' }}</td>
                        <td class="mol-center"><strong>{{ $pr->order_code ?: 'A' }}</strong></td>
                        <td>{{ $pr->remarks ?: $pr->status }}</td>
                    </tr>
                    @endforeach

                    {{-- Baris kosong untuk pengisian baru --}}
                    @for($i = 0; $i < $blankRows; $i++)
                    <tr>
                        <td class="mol-center">{{ $existing->count() + $i + 1 }}</td>
                        <td><input type="text" name="rows[{{ $i }}][figure]" value="{{ old("rows.$i.figure") }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][index_no]" value="{{ old("rows.$i.index_no") }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][part_number]" value="{{ old("rows.$i.part_number") }}" placeholder="6211-12-4570"></td>
                        <td><input type="text" name="rows[{{ $i }}][part_name]" value="{{ old("rows.$i.part_name") }}" placeholder="AIR CLEANER"></td>
                        <td><input type="number" name="rows[{{ $i }}][qty]" min="1" class="mol-center" value="{{ old("rows.$i.qty") }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][section]" value="{{ old("rows.$i.section") }}" placeholder="OIL PAN"></td>
                        <td>
                            <select name="rows[{{ $i }}][order_code]">
                                @foreach(array_keys($orderCodes) as $code)
                                <option value="{{ $code }}" {{ old("rows.$i.order_code", 'A') === $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="rows[{{ $i }}][remarks]" value="{{ old("rows.$i.remarks") }}"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="mol-actions">
            <a href="{{ route('components.show', $comp->comp_id) }}" class="btn-secondary">Batalkan</a>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="btn-primary">💾 Simpan MOL</button>
            </div>
        </div>
    </form>
</div>

</x-app-layout>
