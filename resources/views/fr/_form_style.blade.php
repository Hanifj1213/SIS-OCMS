{{--
    Gaya bersama form & PDF Fabrication Request PLO/09/F-021.

    SEMUA angka di bawah diukur dari form asli
    (FABRIKASI/FR 2026/1. SALVAGE TF HD785-7 DT090-0146B ... .pdf)
    memakai PyMuPDF, bukan perkiraan. A4 landscape, 841.92 x 595.32 pt.

    Area isi  : x 25..761 (736pt), y 20..530 (510pt)

    Kolom (persen dari 736pt):
      logo 13.45 | judul 61.01 | info form 25.54
      label 13.45 | nilai 15.08 | ttd: 13.99 14.81 12.77 12.77 17.12
      pembagi detail/gambar: x=419 → kiri 53.53 / kanan 46.47
      tabel material (dari 394pt): 31.4 31.0 7.8 9.1 11.7 9.1

    Tinggi (persen dari 510pt):
      header 9.41 | identitas+approval 33.33
      detail+material+gambar 50.00 | total+legenda 5.49
--}}
<style>
    /* ---------- lembar kertas ---------- */
    .fr-sheet {
        background: #fff; color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8.5px; line-height: 1.15;
    }
    .fr-sheet table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    /* Padding sengaja tipis: baris data pada form asli padat, dan tiap
       kelebihan 2pt per baris menumpuk jadi puluhan pt di blok atas. */
    .fr-sheet td, .fr-sheet th {
        border: 0.5pt solid #000; padding: 0.4pt 3px; vertical-align: top;
    }
    .fr-sheet .nb td, .fr-sheet .nb th { border: none; }

    /* ---------- header ---------- */
    /* Judul & nomor memakai serif dan menjadi elemen paling dominan. */
    .fr-title {
        font-family: 'Times New Roman', Times, serif;
        text-align: center; font-weight: bold;
        font-size: 20px; letter-spacing: 0.3px; line-height: 1.1;
    }
    .fr-frno {
        font-family: 'Times New Roman', Times, serif;
        text-align: center; font-weight: bold;
        font-size: 11px; padding-top: 1px;
    }
    /* Tabel kode formulir bersarang di dalam sel header. Garis TEPI luarnya
       dibuang supaya tidak menumpuk dengan garis sel induk — kalau tidak,
       border kiri/atas/bawahnya terlihat dua kali lebih tebal. */
    .fr-code td { font-size: 7px; padding: 0 3px; height: 11px; }
    .fr-code tr td:first-child { border-left: none; }
    .fr-code tr td:last-child { border-right: none; }
    .fr-code tr:first-child td { border-top: none; }
    .fr-code tr:last-child td { border-bottom: none; }

    /* ---------- label & isi ---------- */
    /* Label & isi rata tengah tegak dengan sedikit ruang kiri, seperti form
       asli — bukan menempel ke garis atas sel. */
    .fr-lbl { font-size: 8.5px; white-space: nowrap; vertical-align: middle; padding-left: 4px; }
    .fr-val { vertical-align: middle; padding-left: 4px; }
    .fr-row td { height: 12px; }        /* baris data tipis seperti aslinya */
    .fr-c { text-align: center; }
    .fr-r { text-align: right; }

    /* Kotak centang jenis pekerjaan: persegi, boleh lebih dari satu */
    .fr-wt { font-size: 8.5px; }
    .fr-wt td { border: none; padding: 0 2px; height: 12px; }
    .fr-box {
        display: inline-block; width: 8px; height: 8px;
        border: 0.5pt solid #000; text-align: center;
        font-size: 7px; line-height: 8px; font-weight: bold;
        vertical-align: middle; margin-right: 3px;
    }

    /* ---------- tanda tangan ---------- */
    .fr-sh { text-align: center; font-size: 8px; height: 11px; }
    .fr-sign-cell { text-align: center; vertical-align: bottom; padding: 0 2px 1px; }
    .fr-sign-img { max-height: 52px; max-width: 96%; }
    .fr-sign-name { font-size: 8.5px; text-transform: uppercase; }
    /* Garis titik yang MELEBAR OTOMATIS selebar kolom: tanda kurung di kedua
       ujung, garis putus-putus mengisi sisanya. Lebih rapi daripada jumlah
       titik tetap, karena lebar tiap kolom approval berbeda. */
    .fr-dotline { width: 100%; border-collapse: collapse; }
    /* Memakai TITIK SUNGGUHAN yang diklip, bukan border-bottom:dotted —
       border dotted dirender berbeda oleh DomPDF dan browser, dan mudah
       hilang karena aturan border .fr-sheet td. Titik yang meluber
       dipotong oleh overflow sel, tanda kurung tetap di ujung. */
    .fr-dotline td { border: none !important; padding: 0; font-size: 8px; line-height: 1; vertical-align: bottom; }
    .fr-dotline .fr-dotline-edge { width: 4px; text-align: center; }
    .fr-dotline .fr-dotline-fill {
        white-space: nowrap; letter-spacing: -0.2px;
        max-width: 0;   /* sel menyesuaikan kolom; isi berlebih dipotong */
    }
    /* Browser tidak memotong isi td meski overflow:hidden, jadi titiknya
       dibungkus lapisan yang lebarnya dibatasi sel. DomPDF mengabaikan
       aturan ini dan tetap memotong lewat max-width di atas. */
    .fr-dotline .fr-dotline-fill > span {
        display: block; overflow: hidden; width: 100%;
    }
    .fr-date { font-size: 8px; height: 11px; }

    /* ---------- blok bawah ---------- */
    .fr-hdr { background: #fff; font-weight: bold; text-align: center; font-size: 8.5px; height: 12px; }
    .fr-instr { text-align: center; vertical-align: middle; }
    /* Header tabel material: rata tengah mendatar DAN tegak, karena judul
       "Amount Price" bisa membungkus jadi dua baris. */
    .fr-mat th { font-weight: bold; text-align: center; vertical-align: middle; font-size: 8px; height: 14px; }
    .fr-mat td { text-align: center; font-size: 8.5px; vertical-align: top; }
    /* Kolom kosong di bawah baris data: tanpa garis horizontal, garis
       vertikal tetap diteruskan sampai blok total. */
    .fr-mat-fill td { border-top: none; border-bottom: none; }
    /* Tiga baris penutup pada form asli hanya ±9pt masing-masing. */
    .fr-total td { height: 10pt; font-size: 7.5px; line-height: 1; padding: 0 3px; }
    .fr-legend td { border: 0.5pt solid #000; font-size: 7.5px; height: 9pt; line-height: 1; padding: 0 3px; }
    .fr-legend-in td { border: none; font-size: 8px; padding: 0 3px; line-height: 1.05; }

    /* ---------- kolom isian pada versi web ----------
       Input dibuat menyatu dengan sel: tanpa border, tanpa padding tebal,
       tanpa warna. Hanya Requirement Date yang berlatar kuning, sama
       seperti form asli. Warna bantu layar dihapus saat dicetak. */
    .fr-sheet input[type=text],
    .fr-sheet input[type=number],
    .fr-sheet input[type=date],
    .fr-sheet textarea {
        width: 100%; border: none; background: transparent; color: #000;
        font-family: inherit; font-size: inherit; line-height: 1.15;
        padding: 0; margin: 0; box-sizing: border-box;
    }
    /* Sel .fr-val memberi ruang lewat padding sel; input di dalamnya harus
       ikut menyisakan ruang kiri agar teksnya tidak menempel garis. */
    .fr-sheet .fr-val input { padding-left: 2px; }
    .fr-sheet textarea { resize: none; overflow: hidden; }
    .fr-sheet input[type=number] { -moz-appearance: textfield; appearance: textfield; }
    .fr-sheet input[type=number]::-webkit-outer-spin-button,
    .fr-sheet input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    /* Ikon kalender bawaan browser tidak ada pada form asli */
    .fr-sheet input[type=date]::-webkit-calendar-picker-indicator {
        opacity: 0; position: absolute; right: 0; width: 14px; cursor: pointer;
    }
    .fr-sheet input[type=date] { position: relative; }
    .fr-sheet input::placeholder, .fr-sheet textarea::placeholder { color: #b9b9b9; }
    /* Penanda area yang bisa diisi — hanya di layar */
    .fr-sheet .fr-edit input:hover, .fr-sheet .fr-edit textarea:hover { background: #f3f7ff; }
    .fr-sheet input:focus, .fr-sheet textarea:focus {
        outline: 1.5px solid #1d4ed8; background: #fff; position: relative; z-index: 2;
    }
    .fr-yellow, .fr-yellow input { background: #ffff00; }

    @media print {
        .fr-sheet input, .fr-sheet textarea { background: transparent !important; color: #000 !important; outline: none !important; }
        .fr-sheet input::placeholder, .fr-sheet textarea::placeholder { color: transparent !important; }
        .fr-sheet input[type=date]::-webkit-calendar-picker-indicator { display: none !important; }
        .fr-yellow, .fr-yellow input { background: #ffff00 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .fr-no-print { display: none !important; }
    }
</style>
