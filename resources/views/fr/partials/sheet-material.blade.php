            {{-- ============ DETAIL · MATERIAL · GAMBAR ============ --}}
            <table>
                <colgroup><col style="width:53.53%"><col style="width:46.47%"></colgroup>
                <tr>
                    <td class="fr-hdr">DETAIL INSTRUCTION</td>
                    <td class="fr-hdr">GAMBAR &amp; DIMENSI (BILA PERLU DIGAMBAR SESUAI UKURAN YANG DIKEHENDAKI)</td>
                </tr>
                <tr>
                    <td class="fr-detail-column">
                        <div class="fr-detail-stack">
                        <table class="nb">
                            <tr>
                                <td class="fr-instr fr-edit" style="height:96px; padding:4px 10px;">
                                    <textarea name="instruction" rows="4" style="text-align:center; height:88px;"
                                              placeholder="POLESHING AREA BEARING SEAT">{{ $v('instruction') }}</textarea>
                                </td>
                            </tr>
                        </table>

                        <table class="fr-mat" style="border-left:none; border-right:none; border-bottom:none;">
                            <colgroup>
                                <col style="width:31.4%"><col style="width:31.0%"><col style="width:7.8%">
                                <col style="width:9.1%"><col style="width:11.7%"><col style="width:9.1%">
                            </colgroup>
                            <tr><td colspan="6" class="fr-hdr">PART MATERIAL SHOULD BE DELIVERY FOR REPAIR</td></tr>
                            <tr>
                                <th>PN/Size/Dim/Mod/SN</th>
                            <th>Description</th>
                            <th>Brand</th>
                                <th>Q'ty</th>
                                <th>Unit price</th>
                                <th>Amount Price</th>
                        </tr>
                            <tr>
                                <td class="fr-edit"><input type="text" name="part_number" value="{{ $v('part_number') }}" style="text-align:center;" placeholder="561-13-71020"></td>
                                <td class="fr-edit"><input type="text" name="part_name" required value="{{ $v('part_name') }}" style="text-align:center;" placeholder="SHAFT"></td>
                                <td class="fr-edit"><input type="text" name="brand" value="{{ $v('brand') }}" style="text-align:center;" placeholder="KMT"></td>
                                <td class="fr-edit"><input type="number" name="qty" id="fr-qty" min="1" required value="{{ $v('qty', 1) }}" style="text-align:center;"></td>
                                <td class="fr-edit"><input type="number" name="unit_price" id="fr-unit" step="1" min="0" value="{{ $v('unit_price') }}" style="text-align:right;"></td>
                                <td class="fr-r" id="fr-amount">{{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}</td>
                            </tr>
                            {{-- Ruang kosong: tanpa garis mendatar, garis kolom diteruskan --}}
                            <tr class="fr-mat-fill">
                                <td style="height:172px;"></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        </table>
                        </div>
                    </td>

                    {{-- Kanvas gambar bebas. Jumlah gambar tidak dibatasi:
                         satu tombol "Tambah gambar" dipakai berulang, gambar
                         yang sudah ada tidak tertimpa. Tiap gambar bisa digeser
                         (tarik) dan diubah ukurannya (tarik sudut kanan bawah).
                         Klik kanan pada gambar untuk menghapusnya.
                         Posisi & ukuran disimpan dalam persen supaya hasil
                         cetak sama dengan tampilan layar. --}}
                    <td style="padding:0; vertical-align:top;">
                        <div id="fr-canvas" class="fr-canvas-editor" style="position:relative; width:100%; height:300px; overflow:hidden;">
                            {{-- Toolbar overlay transparan: tidak menambah tinggi
                                 tabel, sehingga gambar dapat berada di belakangnya. --}}
                            <div class="fr-toolbar fr-no-print" role="toolbar" aria-label="Alat anotasi gambar">
                                <div class="fr-toolbar-main">
                                    <label class="fr-tb-upload" title="Tambah satu atau beberapa gambar">
                                        <input type="file" id="fr-img-input" accept="image/*" multiple
                                               onchange="frAddImages(this)">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1.5" y="2.5" width="13" height="11" rx="1.5"/>
                                            <circle cx="5" cy="6" r="1.2"/>
                                            <path d="m3 11 3-3 2.2 2 1.5-1.5L13 11.5M12.5 1v4M10.5 3h4"/>
                                        </svg>
                                        <span>Tambah gambar</span>
                                    </label>
                                    <button type="button" class="fr-tb-btn" onclick="frResetLayout()" title="Tata ulang gambar">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13.5 5.5A6 6 0 1 0 14 9"/><path d="M10.5 5.5h3v-3"/>
                                        </svg>
                                    </button>
                                    <span class="fr-tb-sep"></span>
                                    <button type="button" class="fr-tb-btn fr-tb-active" data-tool="select" title="Seleksi: pilih, geser, atau ubah ukuran">
                                        <svg viewBox="0 0 16 16" fill="currentColor"><path d="M3 1.5 3 13l2.7-2.9L7.5 15l1.8-.8-1.7-4.6 3.4-.6z"/></svg>
                                    </button>
                                    <button type="button" class="fr-tb-btn" data-tool="line" title="Garis lurus">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                                            <path d="M2.5 13.5 13.5 2.5"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="fr-tb-btn" data-tool="arrow" title="Panah satu arah">
                                        <svg viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M2 7.2h8.7L8.2 4.7 9.5 3.4 14 8l-4.5 4.6-1.3-1.3 2.5-2.6H2z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="fr-tb-btn" data-tool="double-arrow" title="Panah dua arah">
                                        <svg viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M2 8 6.4 3.6 7.7 4.9 5.5 7.1h5L8.3 4.9l1.3-1.3L14 8l-4.4 4.4-1.3-1.3 2.2-2.2h-5l2.2 2.2-1.3 1.3z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="fr-tb-btn" data-tool="text" title="Insert Text">
                                        <svg viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M2 3h12v2.2h-1.5V4.5H9v7h1.8v1.8H5.2v-1.8H7v-7H3.5v.7H2z"/>
                                        </svg>
                                    </button>
                                    <span class="fr-tb-sep"></span>
                                    <label class="fr-tb-lbl" for="fr-anno-color">Warna</label>
                                    <input type="color" id="fr-anno-color" class="fr-tb-color" value="#dc2626">
                                    <label class="fr-tb-lbl" for="fr-anno-width">Tebal</label>
                                    <input type="number" id="fr-anno-width" class="fr-tb-width"
                                           min="1" max="20" step="1" value="2">
                                </div>
                            </div>
                            @foreach($existingImages as $i => $img)
                            <div class="fr-obj" data-index="{{ $i }}"
                                 style="left:{{ $img['x'] }}%; top:{{ $img['y'] }}%; width:{{ $img['w'] }}%;">
                                <img src="{{ asset($img['path']) }}" alt="Gambar {{ $i + 1 }}" draggable="false">
                                <span class="fr-obj-handle fr-no-print" title="Tarik untuk mengubah ukuran"></span>
                                <input type="hidden" name="images[{{ $i }}][path]" value="{{ $img['path'] }}">
                                <input type="hidden" name="images[{{ $i }}][x]" value="{{ $img['x'] }}">
                                <input type="hidden" name="images[{{ $i }}][y]" value="{{ $img['y'] }}">
                                <input type="hidden" name="images[{{ $i }}][w]" value="{{ $img['w'] }}">
                    </div>
                            @endforeach
                            {{-- Lapisan vektor anotasi: koordinat 0..100 (persen kanvas),
                                 sama seperti posisi gambar, jadi hasil cetak = layar. --}}
                            <svg id="fr-anno-svg" class="fr-anno-overlay" viewBox="0 0 100 100"
                                 preserveAspectRatio="none" data-annotation-id="fr-canvas" data-annotation-editor="fr-canvas-editor"></svg>
                            <script type="application/json" id="fr-annotations-data">{!! json_encode(
                                $fr ? $fr->annotationList() : [],
                                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                            ) !!}</script>
                            <input type="hidden" name="annotations_json" id="fr-annotations-json"
                                   value="{{ $fr ? json_encode($fr->annotationList()) : '' }}">
                </div>
                    </td>
                </tr>
            </table>

