# Standar Keputusan Part — SIAP Upload GSheet

Dokumen acuan untuk merapikan template checksheet Engine & Powertrain agar otomasi FR/PR di OCMS konsisten.

## 1. Tiga keputusan resmi (semua kategori)

| Label standar | Alias lama (toleransi parser) | Arti | OCMS |
|---------------|-------------------------------|------|------|
| **REUSE** | U/A, Reused, Good | Part layak dipakai kembali | — |
| **SALVAGE** | U/R, Repair, SALVG | Perlu fabrikasi/perbaikan | **Fabrication Request** |
| **REPLACE** | R/N, Replace New | Ganti part baru | **Part Request** |

**Aturan:** satu part = satu centang (mutually exclusive).

## 2. Sumber data per kategori

| Kategori | Sheet sumber keputusan | URL field OCMS | Measurement |
|----------|------------------------|----------------|-------------|
| **Engine** | **DISASSEMBLY** | `gsheet_url`, `gsheet_subassy_disassembly_url` | Hanya data ukuran (mm, repair limit) |
| **Powertrain** | **INSPECTION** | `gsheet_measurement_url` | Boleh digabung (sudah ada U/A U/R R/N) |

Jangan pakai workbook Measurement Engine sebagai sumber FR — format per tab tidak seragam.

## 3. Layout kolom standar (Disassembly & Inspection)

```
NO | PART NUMBER | PART NAME | ... | REUSE | SALVAGE | REPLACE | REMARKS
```

Inspection Powertrain (setara):

```
NO | PARTS NAME | ... | DECISION → U/A | U/R | R/N | REMARKS
```

- Centang di **baris part utama** (ada NO + nama part), bukan sub-baris check point.
- Header boleh diulang per section (Engine disassembly punya 4 blok) — parser OCMS mendukung multi-header.

## 4. Naming file & tab

**File:** `{STAGE} {ComponentType} {EGI}.xlsx`  
Contoh: `DISASSEMBLY ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx`

**Tab sheet (auto-read OCMS):**

| Stage | Keyword tab |
|-------|-------------|
| Disassembly | `disassy`, `diss`, `disassembly`, `engine` |
| Inspection | `inspeksi`, `inspection` |
| Measurement | `measurement` (Engine: ukuran saja) |

OCMS membaca **semua** tab yang namanya cocok keyword, bukan hanya tab pertama.
Jadi workbook multi-unit (`INSPEKSI NO1/NO2/NO3`, `DISASSY LH/RH`,
`CYL HEAD DISASSY` + `TURBO DISASSY`) terbaca seluruhnya. Nama tab disimpan
sebagai **section** pada FR/PR, sehingga part bernama sama pada unit berbeda
tetap menghasilkan FR sendiri-sendiri.

Konsekuensi penamaan: **tab yang berisi tabel keputusan wajib mengandung salah
satu keyword di atas.** Tab bernama `TM`, `PM`, atau `LEMBAR 2` tidak akan
terbaca.

## 5. Outlier — status

| File | Masalah | Status |
|------|---------|--------|
| CV D375-6 DISASSEMBLY | `REPAIR \| SALVG \| REPAIR` | ✅ REUSE \| SALVAGE \| REPLACE |
| CV HD785-7 DISASSEMBLY | Tanpa kolom keputusan | ✅ 3 kolom decision ditambah |
| 8 file Powertrain DISASSEMBLY | `REUSE \| SALVG \| REPAIR` — kolom ketiga tak dikenali parser, PR tidak pernah terpicu | ✅ `normalize_decision_headers.py` (22 sel header) |
| SUBASSY DISASSEMBLY (SUPPLY PUMP) | Header kolom nama `PART` (tunggal) ditolak parser | ✅ regex parser diperluas |
| Engine MEASUREMENT (semua EGI) | 5–8 tab format campur | Dibiarkan — bukan sumber FR |
| Engine DISASSEMBLY | File 9–14 MB (banyak gambar) | ⏳ belum dikompres |

Cek ulang kondisi seluruh template kapan saja:

```bash
python tools/verify_siap_state.py
```

Skrip ini melaporkan per file: tab mana yang dibaca OCMS, label keputusan yang
terdeteksi, jumlah baris part, dan sel keputusan yang masih berupa teks.
Target: **MASALAH (0)**.

## 6. Alur OCMS (Stage 2+)

1. Mekanik isi spreadsheet sesuai profil kategori.
2. Di halaman komponen → **Scan Spreadsheet**.
3. Pilih kandidat FR (SALVAGE/U/R) dan PR (REPLACE/R/N) → **Simpan**.
4. Download PDF FR per nomor resmi `FR/SIS/RC/xxxx/Bulan/Tahun/INT`.

## 7. Audit template

Jalankan ulang audit:

```bash
python tools/audit_siap_sheets.py
```

Output: `tools/siap_sheets_audit.json`

## 8. JANGAN edit template dengan openpyxl

`tools/format_siap_templates.py` dan `tools/normalize_decision_headers.py`
dijalankan 2026-07-26 dan **merusak 30 file**. openpyxl tidak mendukung gambar
vektor **EMF/WMF**; saat workbook disimpan ulang, gambar itu dibuang dan
relasi drawing ikut hilang:

| File | Sebelum | Sesudah | Gambar |
|------|---------|---------|--------|
| DISASSEMBLY ENGINE SAA6D170E-5 | 14,6 MB | 85 KB | 53 → 0 |
| DISASSEMBLY ENGINE SAA6D140E-5 | 11,1 MB | 80 KB | 42 → 0 |
| DISASSEMBLY ENGINE SA6D140E-2 | 10,1 MB | 82 KB | 42 → 0 |

Semua file sudah **dipulihkan** dari `_SIAP_BACKUP/20260726_182618/`, dan kedua
skrip openpyxl kini menolak berjalan tanpa flag paksa.

**Cara yang benar — Excel COM (format aman):**

```bash
pwsh -File tools/normalize_headers_excel.ps1 -DryRun
pwsh -File tools/normalize_headers_excel.ps1
```

Hanya menyentuh 2–3 sel label header per sheet. Terakhir dijalankan: **9 file,
25 sel**, gambar utuh.

**Sel keputusan tidak perlu diisi `FALSE`.** Sel kosong sudah dibaca sebagai
"tidak dicentang" oleh parser, dan Google Sheets mengubah sel kosong jadi
checkbox tak tercentang saat **Insert → Checkbox**. Mengisi ribuan sel plus
data validation per baris justru yang dulu membengkakkan file.

## 9. Perintah pemeriksaan

```bash
python tools/verify_siap_state.py      # kesiapan otomasi FR/PR  -> target MASALAH (0)
python tools/inspect_xlsx_damage.py    # kerusakan format vs backup -> target rusak 0
python tools/restore_siap_from_backup.py --dry-run   # pulihkan kalau ada yang rusak lagi
```

`verify_siap_state.py` memisahkan **MASALAH** (menghambat otomasi) dari
**CATATAN** (tidak menghambat, mis. sel teks di kolom keputusan yang tetap
diabaikan parser).
