# SISI-OCMS — Overhaul Component Management System

Aplikasi web untuk mengelola alur kerja overhaul komponen alat berat
(Engine & Powertrain) di Plant Rebuild Centre: mulai dari penerimaan komponen,
pembongkaran & inspeksi, fabrikasi, perakitan, uji fungsi, sampai serah terima —
dengan checksheet digital, approval berjenjang, dan pelacakan waktu pengerjaan.

Dibangun dengan Laravel sebagai proyek Kerja Praktik.

## Fitur Utama

**Alur overhaul 7 tahap** dengan quality gate antar tahap:

| Tahap | Nama | Keterangan |
|---|---|---|
| 1 | Receiving | Registrasi komponen + QR Code |
| 2 | DIS Assembling | Pembongkaran, pencucian & pengukuran via checksheet digital |
| 3 | Machining & Fabrication | Perbaikan part (FR) dan permintaan part pengganti (PR) |
| 4 | Assembly | Perakitan dengan checksheet assembly per model engine |
| 5 | Test Performance & Painting | Uji fungsi test bench + dokumentasi pengecatan |
| 6 | Delivery | Checksheet serah terima |
| 7 | RFU | Ready for Use — berita acara dapat dicetak PDF |

- **Checksheet Google Sheets per komponen** — template master per EGI/model
  diduplikasi otomatis saat komponen didaftarkan (via Google Apps Script),
  lalu ditampilkan langsung di halaman tahap terkait.
- **Auto Fabrication Request (FR)** — hasil keputusan inspeksi tahap 2
  (Reuse / Salvage / Replace, atau U/A / U/R / R/N untuk Powertrain) dipindai
  dari spreadsheet dan otomatis menjadi draft FR (form PLO/09/F-021) atau
  Part Request ke gudang. Satu FR per part, penomoran otomatis.
- **Pelacakan waktu 3 dimensi per tahap** — Calendar Hour (24/7),
  Work Hour (jam operasional bengkel, otomatis memotong jam tutup),
  dan Man Hour (jumlah crew × jam kerja efektif, otomatis berhenti saat
  jam istirahat). Crew dicatat cukup dengan daftar nama, tanpa akun per mekanik.
- **RBAC** (spatie/laravel-permission): Mechanic, Supervisor, SuperAdmin,
  Management — tahap 2–5 membutuhkan approval Management.
- **Realtime polling** untuk dashboard & status komponen, dark/light mode,
  serta layout responsif untuk penggunaan di HP dari lantai workshop.
- **Ekspor PDF** (DomPDF): Fabrication Request dan Berita Acara overhaul.

## Teknologi

- **Backend:** PHP 8.3+, Laravel, SQLite (dev) / MySQL (produksi), queue database
- **Integrasi:** Google Sheets via Apps Script Web App (duplikasi + pembacaan multi-tab)
- **Frontend:** Blade, CSS custom (glassmorphism), vanilla JS (polling, live timer)
- **PDF:** barryvdh/laravel-dompdf
- **Testing:** PHPUnit — feature test untuk alur tahap, parser keputusan, FR/MOL, dan time tracking

## Menjalankan Secara Lokal

```bash
git clone https://github.com/MuhammadRezqyRobiansyah/SISI-OCMS.git
cd SISI-OCMS

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

# .env: isi GSHEET_COPY_WEBAPP_URL & GSHEET_COPY_SECRET (integrasi Google Sheets)

php artisan migrate
php artisan db:seed --class=ChecksheetTemplateSeeder
php artisan db:seed --class=DeliveryChecksheetTemplateSeeder

php artisan serve
php artisan queue:work   # wajib: memproses duplikasi checksheet GSheet
```

Registrasi publik dinonaktifkan — user dibuat oleh SuperAdmin melalui menu Users.

### Menjalankan test

```bash
php artisan test
# atau spesifik:
php vendor/bin/phpunit --filter FullStageWalkthroughTest
```

## Deploy

Panduan produksi Windows (Laragon / IIS) ada di [`DEPLOY_LARAGON_IIS.md`](DEPLOY_LARAGON_IIS.md),
checklist umum di [`DEPLOYMENT.md`](DEPLOYMENT.md). Poin penting: worker antrian
(`php artisan queue:work`) harus berjalan sebagai service.

## Struktur Direktori Penting

```
app/
  Http/Controllers/   ComponentController, FabricationRequestController,
                      StageTimeController, ChecksheetController, ...
  Services/           FabricationRequestService (penomoran & scan FR),
                      ChecksheetGsheetService (integrasi GSheet),
                      StageTimeService (kalkulasi Calendar/Work/Man Hour)
  Jobs/               DuplicateChecksheetGsheets (duplikasi template per komponen)
config/
  checksheet_gsheets.php  Peta ID template GSheet per kategori & EGI
  worktime.php            Jam operasional & jam istirahat bengkel
tools/                Skrip pendukung offline: upload template ke GSheet,
                      konversi dokumen, audit checksheet (tidak dipakai runtime)
tests/Feature/        Alur tahap 1-7, parser keputusan, FR/MOL, time tracking
```
