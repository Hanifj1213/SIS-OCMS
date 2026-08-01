            {{-- ============ HEADER ============ --}}
            <table>
                <colgroup><col style="width:13.45%"><col style="width:61.01%"><col style="width:25.54%"></colgroup>
                <tr>
                    <td style="vertical-align:middle; text-align:center; height:56px;">
                        <img src="{{ asset('images/brand/alamtri-logo.png') }}" alt="AlamTri" style="height:40px;">
                    </td>
                    <td style="vertical-align:middle;">
                        <div class="fr-title">FABRICATION REQUEST</div>
                        {{-- Nomor FR bisa disunting. Dibiarkan kosong pada FR
                             baru = sistem yang memberi nomor urut berikutnya. --}}
                        <div class="fr-frno fr-edit">
                            <input type="text" name="fr_number" class="fr-frno-input"
                                   value="{{ old('fr_number', $fr->fr_number ?? '') }}"
                                   placeholder="FR/SIS/RC/____/{{ $romanMonth }}/{{ now()->format('Y') }}/INT">
                    </div>
                    </td>
                    <td style="padding:0; vertical-align:top;">
                        {{-- Kode formulir bisa disunting; kosong = nilai bawaan --}}
                        <table class="fr-code">
                            <colgroup><col style="width:54%"><col style="width:46%"></colgroup>
                            @foreach([
                                'form_no' => 'No. Formulir / Form No.',
                                'sop_no' => 'No. SOP / SOP No.',
                                'form_owner' => 'Pemilik / Owner',
                                'form_revision' => 'Revisi Ke / Revision To',
                            ] as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="fr-edit">
                                    <input type="text" name="{{ $key }}"
                                           value="{{ old($key, $fr ? $fr->formCode($key) : App\Models\FabricationRequest::FORM_DEFAULTS[$key]) }}">
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Celah kosong antara header dan blok Sent To.
                 Pada form asli ada jarak 8pt di sini (garis y=69 → 77). --}}
            <div style="height:10px;"></div>

