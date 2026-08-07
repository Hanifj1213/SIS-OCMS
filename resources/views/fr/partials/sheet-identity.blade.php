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

