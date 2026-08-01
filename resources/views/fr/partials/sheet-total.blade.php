            {{-- ============ TOTAL ============ --}}
            <table style="border-top:none;">
                <colgroup><col style="width:41.5%"><col style="width:12%"><col style="width:34.5%"><col style="width:12%"></colgroup>
                {{-- Alignment mengikuti form asli: dua label kiri rata TENGAH,
                     label Grand Total rata KIRI. --}}
                <tr class="fr-total">
                    <td class="fr-c">TOTAL PART / MATERIAL COST (JUMLAH BIAYA PART / MATERIAL)</td>
                    <td>Rp. <span id="fr-total-part">{{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}</span></td>
                    <td>GRAND TOTAL COST / BIAYA TOTAL (PART + LABOUR)</td>
                    <td>Rp. <span id="fr-grand"></span></td>
                </tr>
                <tr class="fr-total">
                    <td class="fr-c">TOTAL LABOUR / JUMLAH BIAYA TENAGA KERJA (PEKERJAAN)</td>
                    <td class="fr-edit">Rp. <input type="number" name="labour_cost" id="fr-labour" step="1" min="0" value="{{ $v('labour_cost') }}" style="width:74%; text-align:right;"></td>
                    <td colspan="2" class="fr-edit" style="white-space:nowrap;">
                        SAID / TERBILANG : (<input type="text" name="note" value="{{ $v('note') }}" style="width:78%;">)
                    </td>
                </tr>
            </table>
