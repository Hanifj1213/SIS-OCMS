# MATERI PENJELASAN PROYEK SISI-OCMS
# Overhaul Component Management System
**PT Sapta Indra Sejati — Plant Rebuild Centre**

> Dokumen ini menjelaskan keseluruhan isi proyek SISI-OCMS secara lengkap:
> framework, arsitektur, fitur, model data, alur kerja, dan file-file penting.

---

## 1. Ringkasan Proyek

SISI-OCMS adalah aplikasi web untuk mengelola **alur kerja overhaul komponen alat berat** (Engine & Powertrain) di Plant Rebuild Centre PT SIS. Aplikasi mencakup proses dari penerimaan komponen, pembongkaran & inspeksi, fabrikasi, perakitan, uji fungsi, sampai serah terima — dilengkapi checksheet digital, approval berjenjang, dan pelacakan waktu pengerjaan.

Dibangun sebagai **proyek Kerja Praktik (KP)** menggunakan framework Laravel.

---

## 2. Tech Stack (Teknologi yang Digunakan)

| Kategori | Teknologi | Versi |
|---|---|---|
| **Framework Backend** | Laravel (PHP) | v13.17 (PHP 8.3+) |
| **Frontend Template** | Blade Templates | bawaan Laravel |
| **CSS Framework** | Tailwind CSS | v4.0+ |
| **JS Framework** | Alpine.js + Vanilla JS | v3.4+ |
| **Livewire** | Livewire + Flux UI | v4.1 / v2.13 |
| **Build Tool** | Vite | v8.0 |
| **Database** | SQLite (dev) / MySQL (produksi) | — |
| **PDF Generator** | barryvdh/laravel-dompdf | v3.1 |
| **QR Code** | endroid/qr-code | v6.1 |
| **RBAC (Role)** | spatie/laravel-permission | v8.3 |
| **Auth** | Laravel Fortify + Passkeys | v1.37 |
| **Queue** | Database queue driver | bawaan |
| **Integrasi Eksternal** | Google Sheets via Apps Script Web App | — |

---

## 3. Alur Overhaul 7 Tahap

Inti bisnis aplikasi adalah **7 tahap overhaul** dengan quality gate antar tahap:

| # | Tahap | Keterangan |
|---|---|---|
| 1 | **Receiving** | Registrasi komponen masuk + generate QR Code + duplikasi template GSheet |
| 2 | **Disassembling** | Pembongkaran, pencucian, pengukuran via checksheet digital (GSheet). Keputusan inspeksi: Reuse/Salvage/Replace (Engine) atau U/A/U/R/R/N (Powertrain) |
| 3 | **Machining & Fabrication** | Perbaikan part via Fabrication Request (FR) dan permintaan part pengganti (PR) ke gudang |
| 4 | **Assembly** | Perakitan dengan checksheet assembly per model engine + upload dokumen |
| 5 | **Test Performance & Painting** | Uji fungsi test bench + dokumentasi foto pengecatan |
| 6 | **Delivery** | Checksheet serah terima |
| 7 | **RFU (Ready for Use)** | Berita acara selesai, bisa dicetak PDF |

### Mekanisme Perpindahan Tahap
- Mechanic mengajukan perpindahan tahap → sistem menandai `is_waiting_approval = true`
- Group Leader / Supervisor / Management meng-approve → komponen naik tahap
- Jika ditolak → komponen kembali ke mekanik untuk diperbaiki
- Logika di: `StageTransitionService.php`

---

## 4. Struktur Direktori Proyek

```
sisi-ocms/
├── app/
│   ├── Actions/            # Action classes
│   ├── Casts/              # Custom Eloquent casts (CompressedJson)
│   ├── Console/            # Artisan commands
│   ├── Enums/              # PHP Enums (TeamRole, TeamPermission)
│   ├── Http/
│   │   └── Controllers/    # 13 controller utama + subfolder Auth/ dan Dev/
│   ├── Jobs/               # Queue jobs (DuplicateChecksheetGsheets)
│   ├── Livewire/Actions/   # Livewire actions (Logout)
│   ├── Models/             # 15 Eloquent models
│   ├── Notifications/      # Notification classes
│   ├── Policies/           # Authorization policies (TeamPolicy)
│   ├── Providers/          # Service providers
│   ├── Rules/              # Custom validation rules
│   ├── Services/           # 10 service classes (business logic utama)
│   ├── Support/            # Helper classes (OcmsAccess — RBAC matrix)
│   └── View/               # View composers/components
├── config/
│   ├── checksheet_gsheets.php  # Peta ID template GSheet per kategori & EGI
│   ├── worktime.php            # Jam operasional & istirahat bengkel
│   └── ...                     # Config Laravel standar
├── database/
│   ├── migrations/         # 44 migration files
│   ├── seeders/            # 4 seeder (ChecksheetTemplate, Role, Delivery, Database)
│   └── data/               # Data pendukung
├── resources/
│   ├── css/                # Stylesheet
│   ├── js/                 # JavaScript
│   └── views/              # Blade templates (15 subdirectory)
├── routes/
│   ├── web.php             # 187 baris route utama
│   ├── auth.php            # Route autentikasi
│   └── settings.php        # Route pengaturan
├── tests/
│   ├── Feature/            # 12 feature test files
│   └── Unit/               # Unit tests
├── tools/                  # 64+ skrip pendukung offline (Python, PHP, PowerShell)
└── public/                 # Asset publik (images, compiled assets)
```

---

## 5. Model Data (Eloquent Models)

### 5.1 Model Utama

| Model | Tabel | PK | Keterangan |
|---|---|---|---|
| `Component` | components | comp_id | Entitas utama: komponen yang di-overhaul |
| `OverhaulLog` | overhaul_logs | log_id | Log perpindahan tahap (start/end time, approval) |
| `FabricationRequest` | fabrication_requests | fr_id | Permintaan fabrikasi/repair part (form PLO/09/F-021) |
| `PartRequest` | part_requests | — | Permintaan part pengganti ke gudang |
| `ChecksheetTemplate` | checksheet_templates | — | Template master checksheet per kategori & EGI |
| `ComponentChecksheet` | component_checksheets | — | Snapshot checksheet per komponen per tahap |
| `StageMechanicLog` | stage_mechanic_logs | id | Log crew mekanik (clock in/out, jumlah crew) |
| `SpreadsheetLayout` | spreadsheet_layouts | layout_id | Layout Excel 1:1 (checksheet lokal) |
| `ComponentSpreadsheetAnswer` | — | — | Jawaban checksheet spreadsheet lokal |
| `GsheetTemplate` | gsheet_templates | — | Template GSheet yang dikelola Developer |
| `InspectionDetail` | inspection_details | insp_id | Detail inspeksi per part |

### 5.2 Model Pendukung

| Model | Keterangan |
|---|---|
| `User` | Pengguna sistem (login via NIK, bukan email) |
| `Team` | Tim/organisasi (fitur bawaan Laravel) |
| `TeamInvitation` | Undangan tim |
| `Membership` | Keanggotaan tim |

### 5.3 Relasi Utama Component

```
Component (1) ──→ (N) OverhaulLog         # log per tahap
Component (1) ──→ (N) FabricationRequest   # FR per part
Component (1) ──→ (N) PartRequest          # PR ke gudang
Component (1) ──→ (N) ComponentChecksheet  # checksheet per stage
Component (1) ──→ (N) StageMechanicLog     # crew per stage
Component (1) ──→ (N) InspectionDetail     # detail inspeksi
```

### 5.4 Field Penting Component

- **Identitas**: serial_number, egi, unit_code, major_category, component_model
- **Status**: current_stage (1-7), status (On Progress/Ready for Use), is_waiting_approval
- **Google Sheets URLs**: gsheet_url, gsheet_measurement_url, gsheet_subassy_*, gsheet_assembly_url, gsheet_testbench_url
- **Dokumen**: painting_images (JSON array), assembly_documents (JSON array), mol_document_path, qr_code_path

---

## 6. Sistem Role & Akses (RBAC)

Menggunakan `spatie/laravel-permission` dengan matriks akses terpusat di `app/Support/OcmsAccess.php`.

### 11 Role yang Tersedia

| Role | Deskripsi |
|---|---|
| **SuperAdmin** | Akses penuh + manajemen akun pengguna |
| **Developer** | Kelola template checksheet/GSheet + edit & hapus komponen |
| **Department Head** | Akses penuh seluruh modul |
| **CRC Head** | Akses penuh seluruh modul |
| **Section Head** | Akses penuh seluruh modul |
| **Logistic Head** | Akses penuh + kelola gudang/part request |
| **Planner** | Akses penuh + perencanaan & gudang |
| **Logistik** | Review komponen + daftarkan komponen baru saja |
| **Mechanic** | Proses overhaul, checksheet, FR/MOL — tanpa approve |
| **Group Leader** | Semua akses mekanik + approve & review |
| **Supervisor** | Sama dengan Group Leader |

### Grup Akses

- **FULL_ACCESS**: SuperAdmin, Department Head, CRC Head, Section Head, Logistic Head, Planner
- **OPERATE** (boleh proses overhaul): Mechanic, Group Leader, Supervisor + FULL_ACCESS
- **APPROVE** (boleh approve tahap): Group Leader, Supervisor + FULL_ACCESS
- **REGISTER_COMPONENT**: Mechanic, Logistik, Developer + FULL_ACCESS
- **DEVELOPER** (kelola template): Developer, SuperAdmin

---

## 7. Controller & Route

### 13 Controller Utama

| Controller | Fungsi |
|---|---|
| `ComponentController` | CRUD komponen, perpindahan tahap, approve/reject, cetak PDF |
| `FabricationRequestController` | CRUD FR, scan keputusan, cetak PDF FR |
| `ChecksheetController` | Tampilan & jawab checksheet (typeform-style) |
| `LocalChecksheetController` | Checksheet spreadsheet lokal (tampilan 1:1 Excel) |
| `MolController` | Mechanic Order List (form + upload dokumen) |
| `StageTimeController` | Metrik waktu 3 dimensi + kelola crew mekanik |
| `StatusController` | Dashboard + polling realtime status komponen |
| `PartRequestController` | Modul gudang: daftar & update status part request |
| `UserManagementController` | CRUD user (SuperAdmin only) |
| `PaintingPhotoController` | Upload/hapus foto pengecatan (Stage 5) |
| `AssemblyDocumentController` | Upload/hapus dokumen assembly (Stage 4) |
| `ProfileController` | Edit profil pengguna |
| **Dev/** | Panel Developer: kelola template GSheet & checksheet |

### Grup Route Penting

```
/dashboard                          # Dashboard utama
/components                         # Daftar semua komponen
/components/{id}                    # Detail komponen (semua tahap)
/components/{id}/update-stage       # Naikkan tahap
/components/{id}/approve-stage      # Approve perpindahan
/components/{id}/fr                 # Daftar Fabrication Request
/components/{id}/fr/scan            # Scan keputusan dari GSheet
/components/{id}/mol                # Mechanic Order List
/components/{id}/time-metrics       # Metrik waktu (polling)
/components/{id}/local-checksheet/* # Checksheet Excel lokal
/scan                               # QR Scanner
/part-requests                      # Modul gudang
/dev/*                              # Panel Developer
/admin/users/*                      # Manajemen user
```

---

## 8. Service Layer (Business Logic)

### 10 Service Classes

| Service | Ukuran | Fungsi |
|---|---|---|
| `ChecksheetGsheetService` | 35KB | Integrasi Google Sheets: duplikasi template, baca keputusan part, parsing inspeksi/disassembly |
| `FabricationRequestService` | 13KB | Penomoran FR otomatis, scan kandidat FR/PR, buat draft dari keputusan |
| `StageTimeService` | 6KB | Kalkulasi Calendar/Work/Man Hour dengan formula O(1) |
| `StageTransitionService` | 5KB | Logika perpindahan tahap: snapshot checksheet, approval, advance/reject |
| `SpreadsheetLayoutImporter` | 22KB | Import layout Excel (.xlsx) ke database untuk checksheet lokal |
| `SpreadsheetHtmlRenderer` | 13KB | Render layout spreadsheet ke HTML (tampilan 1:1 Excel) |
| `XlsxLayoutReader` | 20KB | Baca struktur & style dari file .xlsx |
| `FrAttachmentService` | 7KB | Kelola lampiran gambar FR |
| `FrAnnotationRenderer` | 5KB | Render anotasi (garis, teks) pada gambar FR untuk PDF |
| `MolExportService` | 1KB | Export MOL |

### Pelacakan Waktu 3 Dimensi

Salah satu fitur paling unik — dihitung di `StageTimeService`:

1. **Calendar Hour**: Waktu absolut 24/7 (end − start)
2. **Work Hour**: Calendar Hour dipotong jam tutup bengkel (07:30–16:30)
3. **Man Hour**: Akumulasi jam hadir tiap mekanik × jumlah crew, dipotong jam tutup DAN jam istirahat

Konfigurasi jam kerja di `config/worktime.php`:
- Jam operasional: 07:30 – 16:30
- Istirahat: 09:45–10:00 dan 11:30–12:30

---

## 9. Integrasi Google Sheets

### Alur Kerja
1. Saat komponen didaftarkan → sistem dispatch job `DuplicateChecksheetGsheets`
2. Job memanggil Google Apps Script Web App untuk menduplikasi template spreadsheet
3. URL spreadsheet baru disimpan di kolom `gsheet_*` pada tabel components
4. Saat tahap 2 (inspeksi), checksheet GSheet ditampilkan langsung di halaman
5. Keputusan inspeksi (Reuse/Salvage/Replace) dipindai dari spreadsheet → jadi kandidat FR/PR

### 7 Jenis Template GSheet
- `disassembly` — Checksheet pembongkaran
- `measurement` — Checksheet pengukuran
- `subassy_disassembly` — Sub-assembly pembongkaran
- `subassy_measurement` — Sub-assembly pengukuran
- `sdr` — Service Data Report
- `assembly` — Checksheet perakitan
- `testbench` — Checksheet uji fungsi

### Kategori Komponen yang Didukung
- **Engine**: WA800-3, GD825A-2, D155-6, D375-6, PC1250-8, PC2000-8
- **Control Valve**: 8 model EGI
- **Hydraulic Cylinder**: HD785-7
- **Front/Rear Suspension**: HD785-7

---

## 10. Fabrication Request (FR)

### Format Nomor: `FR/SIS/RC/{seq}/{bulan Romawi}/{tahun}/INT`

### Alur FR
1. **Scan otomatis** dari keputusan inspeksi GSheet (Salvage → FR, Replace → PR)
2. **Buat manual** via form create
3. **Edit** detail: part, jenis pekerjaan, instruksi, gambar dimensi + anotasi
4. **Cetak PDF** sesuai form PLO/09/F-021 (dengan DomPDF)

### Fitur Form FR
- Multi work type (Repair, Fabrikasi, Modifikasi, Others)
- Upload hingga 5 gambar dengan posisi drag & resize
- Anotasi pada gambar (garis, panah, teks)
- 5 kolom tanda tangan (Received by, Sent by, Approved by, Checked by, Ordered by)
- Kode formulir yang bisa diedit (form_no, sop_no, dll)

---

## 11. View / Blade Templates

### Struktur Views

```
resources/views/
├── landing.blade.php              # Landing page (pre-login)
├── dashboard.blade.php            # Dashboard utama
├── overhauls/
│   ├── index.blade.php            # Daftar komponen
│   ├── create.blade.php           # Form registrasi komponen (22KB)
│   ├── show.blade.php             # Detail komponen (semua stage)
│   ├── edit.blade.php             # Edit komponen
│   ├── scan.blade.php             # QR Code scanner
│   ├── pdf.blade.php              # Template PDF berita acara
│   └── partials/                  # 14 partial views
│       ├── action-panel.blade.php
│       ├── gsheet-panels.blade.php     # Panel GSheet (27KB)
│       ├── fr-mol-panel.blade.php      # Panel FR & MOL
│       ├── checksheet-interactive.blade.php  # Checksheet slider (38KB)
│       ├── timeline.blade.php          # Timeline tahap (15KB)
│       └── ...
├── fr/
│   ├── form.blade.php             # Form edit FR
│   ├── pdf.blade.php              # Template PDF FR (16KB)
│   └── _form_style.blade.php     # Style khusus form FR
├── checksheet/
│   ├── spreadsheet.blade.php      # Tampilan Excel 1:1
│   └── layouts.blade.php          # Daftar layout checksheet
├── mol/form.blade.php             # Form Mechanic Order List
├── admin/                         # Manajemen user
├── dev/                           # Panel developer
├── warehouse/                     # Modul gudang
└── layouts/                       # Layout utama
```

---

## 12. Database Migrations

Total **44 migration files** yang mencakup:

### Tabel Inti
- `users` — Pengguna (dengan two-factor auth)
- `components` — Komponen overhaul (dengan 20+ kolom tambahan dari migration iteratif)
- `overhaul_logs` — Log tahap overhaul + approval tracking
- `inspection_details` — Detail inspeksi per part
- `part_requests` — Permintaan part ke gudang
- `fabrication_requests` — FR (dengan 10+ kolom tambahan: signature, images, annotations, dll)
- `checksheet_templates` — Template checksheet master
- `component_checksheets` — Checksheet per komponen
- `spreadsheet_layouts` — Layout Excel lokal (compressed JSON)
- `stage_mechanic_logs` — Log crew mekanik
- `gsheet_templates` — Template GSheet yang dikelola Developer
- `permission_tables` — Tabel Spatie permission

---

## 13. Seeders

| Seeder | Ukuran | Fungsi |
|---|---|---|
| `ChecksheetTemplateSeeder` | 126KB | Master data checksheet receiving untuk semua EGI & kategori |
| `DeliveryChecksheetTemplateSeeder` | 6KB | Template checksheet delivery |
| `RoleAndUserSeeder` | 2KB | 11 role + 11 user default |
| `DatabaseSeeder` | 0.5KB | Orchestrator |

---

## 14. Testing

### 12 Feature Test Files

| Test | Cakupan |
|---|---|
| `FullStageWalkthroughTest` | Alur lengkap tahap 1→7 |
| `DecisionScanTest` | Parser keputusan GSheet (Engine & Powertrain) |
| `FrMolFormTest` | CRUD FR + MOL |
| `StageTimeTrackingTest` | Kalkulasi Calendar/Work/Man Hour |
| `ComponentStageReviewTest` | Approval & reject tahap |
| `LocalChecksheetTest` | Checksheet spreadsheet lokal |
| `ReceivingChecksheetTemplateTest` | Template checksheet receiving |
| `StageFourToSevenPanelsTest` | Panel UI tahap 4–7 |
| `FrPanelStageVisibilityTest` | Visibilitas panel FR per tahap |
| `AutoScanAndMolDocumentTest` | Auto-scan FR + upload dokumen MOL |
| `DeveloperRoleTest` | Akses role Developer |
| `FrFormSpacingTest` | Layout/spacing form FR |

### Menjalankan Test
```bash
php artisan test
php vendor/bin/phpunit --filter FullStageWalkthroughTest
```

---

## 15. Tools & Skrip Pendukung

Folder `tools/` berisi **64+ skrip** yang digunakan offline (tidak dipakai runtime):

| Kategori | Contoh File | Fungsi |
|---|---|---|
| **Google Sheets** | `gsheet_copy_webapp.gs` (39KB) | Apps Script: duplikasi, baca, format template |
| **Upload Template** | `upload_engine_stage_gsheets.php` | Upload template GSheet per model engine |
| **Audit** | `audit_components_gsheet.php` | Audit konsistensi GSheet vs database |
| **Excel Processing** | `merge_measurement.ps1`, `merge_subassy.ps1` | Merge sheet pengukuran dari file Excel |
| **Data Extraction** | `build_cv_receiving_items.py` | Ekstrak item checksheet dari PDF |
| **Migration** | `migrate_sqlite_to_mysql.php` | Migrasi SQLite ke MySQL |
| **Deploy** | `deploy.ps1` | Script deployment |

---

## 16. Konfigurasi Environment

File `.env` yang perlu dikonfigurasi:

```env
# Database
DB_CONNECTION=sqlite          # atau mysql untuk produksi

# Queue (WAJIB untuk duplikasi GSheet)
QUEUE_CONNECTION=database

# Google Sheets Integration
GSHEET_COPY_WEBAPP_URL=       # URL Apps Script Web App
GSHEET_COPY_SECRET=           # Secret key untuk autentikasi

# Session
SESSION_DRIVER=database
```

### Menjalankan Aplikasi
```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ChecksheetTemplateSeeder
php artisan db:seed --class=DeliveryChecksheetTemplateSeeder

php artisan serve              # Web server
php artisan queue:work         # WAJIB: proses duplikasi GSheet
```

---

## 17. Fitur UI Penting

- **Dashboard**: Statistik komponen per tahap, komponen menunggu approval, realtime polling
- **QR Scanner**: Scan QR code komponen langsung dari HP
- **Checksheet Interaktif**: Slider-style (typeform) + spreadsheet 1:1 Excel
- **Live Timer**: Polling setiap beberapa detik untuk update Calendar/Work/Man Hour
- **Dark/Light Mode**: Toggle tema
- **Responsive**: Layout bisa dipakai di HP dari lantai workshop
- **Glassmorphism UI**: Custom CSS aesthetic

---

## 18. Deployment

Panduan produksi tersedia di:
- `DEPLOYMENT.md` — Checklist umum
- `DEPLOY_LARAGON_IIS.md` — Deploy di Windows dengan Laragon/IIS
- `DEPLOY_SELF_HOSTED_RUNNER.md` — CI/CD dengan GitHub Actions self-hosted runner

**Poin penting**: Queue worker (`php artisan queue:work --tries=3`) harus berjalan sebagai service di produksi.

---

## 19. Diagram Arsitektur

```
┌─────────────────────────────────────────────────┐
│                    Browser                       │
│  (Blade + Tailwind + Alpine.js + Vanilla JS)    │
└────────────────────┬────────────────────────────┘
                     │ HTTP
┌────────────────────▼────────────────────────────┐
│              Laravel 13 (PHP 8.3+)              │
│  ┌──────────┐ ┌──────────┐ ┌──────────────────┐│
│  │Controllers│ │Middleware│ │ Fortify + RBAC   ││
│  └─────┬────┘ └──────────┘ │(Spatie Permission)││
│        │                   └──────────────────┘│
│  ┌─────▼────────────────────────────────────┐  │
│  │           Service Layer                   │  │
│  │ StageTransition │ StageTime │ FR Service  │  │
│  │ GsheetService   │ SpreadsheetRenderer     │  │
│  └─────┬────────────────────┬───────────────┘  │
│        │                    │                   │
│  ┌─────▼──────┐  ┌─────────▼───────────────┐  │
│  │  Eloquent   │  │    Queue (Database)      │  │
│  │  15 Models  │  │ DuplicateChecksheetJob   │  │
│  └─────┬──────┘  └─────────┬───────────────┘  │
│        │                    │                   │
└────────┼────────────────────┼───────────────────┘
         │                    │
┌────────▼──────┐  ┌─────────▼───────────────────┐
│  SQLite/MySQL  │  │  Google Apps Script Web App  │
│   Database     │  │  (Duplikasi & Baca GSheet)   │
└───────────────┘  └──────────────────────────────┘
```

---

## 20. Detail Method per Controller

### 20.1 ComponentController (23KB — controller terbesar)

| Method | Route | Fungsi |
|---|---|---|
| `index()` | GET /components | Daftar semua komponen, filter by status/stage, pagination |
| `create()` | GET /components/create | Form registrasi komponen baru (dropdown EGI dinamis per kategori) |
| `store()` | POST /components | Simpan komponen → generate QR Code → snapshot checksheet stage 1 → dispatch job duplikasi GSheet |
| `show()` | GET /components/{id} | Halaman detail komponen: menampilkan panel berbeda per stage (checksheet, GSheet, FR/MOL, painting, dll) |
| `edit()` | GET /components/{id}/edit | Form edit komponen (Developer/SuperAdmin only) |
| `update()` | PUT /components/{id} | Update data komponen |
| `destroy()` | DELETE /components/{id} | Hapus komponen + semua relasi (Developer/SuperAdmin only) |
| `updateStage()` | POST /components/{id}/update-stage | Mechanic mengajukan perpindahan tahap → set `is_waiting_approval` |
| `approveStage()` | POST /components/{id}/approve-stage | Atasan approve → panggil `StageTransitionService::advance()` |
| `rejectStage()` | POST /components/{id}/reject-stage | Atasan tolak → panggil `StageTransitionService::reject()` |
| `printPdf()` | GET /components/{id}/print-pdf | Generate PDF berita acara overhaul via DomPDF |

**Konstanta penting di ComponentController:**
```php
STAGE_NAMES = [
    1 => 'Receiving (Penerimaan DC)',
    2 => 'Disassembling',
    3 => 'Machining & Fabrication',
    4 => 'Assembly',
    5 => 'Test Performance & Painting',
    6 => 'Delivery',
    7 => 'RFU (Ready for Use)',
];
```

### 20.2 FabricationRequestController (22KB)

| Method | Route | Fungsi |
|---|---|---|
| `index()` | GET /components/{id}/fr | Daftar FR milik komponen |
| `scan()` | POST /components/{id}/fr/scan | Scan keputusan dari GSheet → tampilkan kandidat FR/PR |
| `create()` | GET /components/{id}/fr/create | Form buat FR manual |
| `storeSingle()` | POST /components/{id}/fr/single | Simpan 1 FR manual |
| `store()` | POST /components/{id}/fr | Batch create FR dari kandidat scan |
| `edit()` | GET /components/{id}/fr/{fr}/edit | Form edit FR (detail, gambar, anotasi, tanda tangan) |
| `update()` | PUT /components/{id}/fr/{fr} | Update FR |
| `updateStatus()` | PATCH /components/{id}/fr/{fr}/status | Ubah status FR (draft→printed→done) |
| `pdf()` | GET /components/{id}/fr/{fr}/pdf | Generate PDF form PLO/09/F-021 |

### 20.3 ChecksheetController (7KB)

| Method | Route | Fungsi |
|---|---|---|
| `show()` | GET /components/{id}/checksheet/{stage} | Tampilkan checksheet typeform-style per stage |
| `saveAnswer()` | POST /components/{id}/checksheet/{stage}/answer | Simpan jawaban per item (AJAX) |
| `saveSpreadsheet()` | POST /components/{id}/spreadsheet-checksheet/{stage} | Simpan jawaban checksheet format spreadsheet |
| `addItem()` | POST /components/{id}/checksheet/{stage}/add-item | Tambah item checksheet custom |
| `removeItem()` | DELETE /components/{id}/checksheet/{stage}/remove-item | Hapus item checksheet |

### 20.4 StageTimeController (3KB)

| Method | Route | Fungsi |
|---|---|---|
| `metrics()` | GET /components/{id}/time-metrics | Return JSON metrik waktu semua tahap (untuk polling) |
| `addMechanic()` | POST /components/{id}/crew | Catat crew mulai kerja (clock in + jumlah + nama) |
| `removeMechanic()` | DELETE /components/{id}/crew/{log} | Clock out crew (tutup segmen kerja) |

### 20.5 StatusController (4KB)

| Method | Route | Fungsi |
|---|---|---|
| `dashboardMetrics()` | static | Hitung statistik untuk dashboard: total komponen per stage, menunggu approval |
| `dashboard()` | GET /status/dashboard | JSON polling dashboard |
| `components()` | GET /status/components | JSON polling daftar komponen |
| `component()` | GET /status/components/{id} | JSON polling detail 1 komponen |
| `partRequests()` | GET /status/part-requests | JSON polling part requests |

---

## 21. Detail Field Lengkap Model Component

### Tabel `components` — Semua Kolom

| Kolom | Tipe | Keterangan |
|---|---|---|
| `comp_id` | PK auto | Primary key komponen |
| `serial_number` | string | Nomor seri komponen |
| `egi` | string | Kode EGI (mis. PC2000-8, D155-6) |
| `unit_code` | string | Kode unit alat berat |
| `unit_serial_no` | string | Serial number unit |
| `site_district` | string | Lokasi site/district asal |
| `model_type` | string | Tipe model unit |
| `major_category` | string | Kategori utama: Engine, Control Valve, Hydraulic Cylinder, dll |
| `component_model` | string | Model komponen spesifik |
| `pn_assy` | string | Part number assembly |
| `status_ovh` | string | Status overhaul |
| `core_category` | string | Kategori core (Engine/Powertrain) |
| `smr` | integer | Service Meter Reading |
| `life_time` | integer | Lifetime komponen (jam) |
| `date_defitted` | date | Tanggal komponen dicopot dari unit |
| `manifest` | string | Nomor manifest pengiriman |
| `way_bill` | string | Nomor way bill |
| `ro_number` | string | Nomor Repair Order |
| `date_delivery` | date | Tanggal pengiriman |
| `current_stage` | integer | Tahap saat ini (1-7) |
| `is_waiting_approval` | boolean | Sedang menunggu approval atasan |
| `status` | string | On Progress / Ready for Use |
| `qr_code_path` | string | Path file QR code |
| `gsheet_url` | string | URL GSheet Disassembly |
| `gsheet_measurement_url` | string | URL GSheet Measurement |
| `gsheet_subassy_disassembly_url` | string | URL GSheet Sub-assembly Disassembly |
| `gsheet_subassy_measurement_url` | string | URL GSheet Sub-assembly Measurement |
| `gsheet_sdr_url` | string | URL GSheet SDR |
| `gsheet_assembly_url` | string | URL GSheet Assembly |
| `gsheet_testbench_url` | string | URL GSheet Testbench |
| `painting_images` | JSON array | Path foto-foto pengecatan (Stage 5) |
| `assembly_documents` | JSON array | Path dokumen assembly (Stage 4) |
| `mol_wo_number` | string | Nomor Work Order MOL |
| `mol_order_type` | string | Tipe order MOL |
| `mol_order_date` | string | Tanggal order MOL |
| `mol_ir_number` | string | Nomor IR MOL |
| `mol_ir_date` | string | Tanggal IR MOL |
| `mol_note` | string | Catatan MOL |
| `mol_document_path` | string | Path dokumen MOL yang diupload |

---

## 22. Detail Field Model FabricationRequest

### Tabel `fabrication_requests` — Semua Kolom

| Kolom | Tipe | Keterangan |
|---|---|---|
| `fr_id` | PK auto | Primary key FR |
| `comp_id` | FK → components | Komponen pemilik FR |
| `fr_number` | string | Nomor resmi: `FR/SIS/RC/0001/VIII/2026/INT` |
| `form_no` | string | Default: `PLO/09/F-021` |
| `sop_no` | string | Default: `PLO/09/000/SOP` |
| `form_owner` | string | Default: `Plant Operation Dept.` |
| `form_revision` | string | Default: `1` |
| `ro_number` | string | Nomor Repair Order |
| `pr_number` | string | Nomor Purchase Request |
| `request_date` | date | Tanggal permintaan |
| `estimation_date` | date | Estimasi selesai |
| `location_site` | string | Lokasi site |
| `unit_model` | string | Model unit (fallback ke komponen) |
| `component_model` | string | Model komponen (fallback ke komponen) |
| `unit_code` | string | Kode unit (fallback ke komponen) |
| `work_order_for` | string | Ditujukan untuk |
| `sent_to` | string | Dikirim ke |
| `address` | string | Alamat tujuan |
| `attn` | string | Attention |
| `part_number` | string | Part number |
| `part_name` | string | Nama part yang dikerjakan |
| `section` | string | Tab/section asal di GSheet |
| `qty` | integer | Jumlah |
| `brand` | string | Merek |
| `unit_price` | decimal | Harga satuan |
| `labour_cost` | decimal | Biaya jasa |
| `work_type` | string | Jenis kerja tunggal (legacy) |
| `work_types` | JSON array | Multi work type: repair, fabrikasi, modifikasi, others |
| `instruction` | text | Instruksi pekerjaan |
| `image_path` | string | Gambar dimensi 1 (legacy) |
| `image_path_2` | string | Gambar dimensi 2 (legacy) |
| `image_layout` | JSON | Posisi & ukuran gambar (x, y, w dalam persen) |
| `images` | JSON array | Daftar gambar baru (hingga 5, dengan posisi) |
| `annotations` | JSON array | Anotasi: line, arrow, double_arrow, text |
| `signature_layout` | JSON | Posisi gambar tanda tangan per role |
| `signatures` | JSON | Data tanda tangan per role (name, date, image path) |
| `source` | string | Asal FR: form, gsheet, manual |
| `status` | string | draft → printed → done |
| `completed_at` | datetime | Waktu selesai |
| `completion_notes` | text | Catatan penyelesaian |
| `created_by` | FK → users | User pembuat |
| `note` | text | Catatan tambahan |

### Konstanta Penting di Model FR

**5 Kolom Tanda Tangan:**
| Key | Label | Sub-label |
|---|---|---|
| `received_by` | Received by, | External Workshop, |
| `sent_by` | Sent by, | Warehouse Keeper, |
| `approved_by` | Approved by, | Plant Sect. Head, |
| `checked_by` | Checked by, | Group Leader |
| `ordered_by` | Ordered by, | Mechanic |

---

## 23. Algoritma Scan Keputusan GSheet

### Dua Profil Scan

**1. Engine (Disassembly Profile):**
- Baca GSheet disassembly + subassy_disassembly
- Cari header: NO, PART NAME, kolom REUSE/SALVAGE/REPLACE
- SALVAGE → kandidat FR (butuh repair)
- REPLACE → kandidat PR (butuh part baru dari gudang)
- REUSE → skip (part masih layak pakai)

**2. Powertrain (Inspection Profile):**
- Baca GSheet measurement/inspection
- Cari header: NO, PARTS NAME, kolom DECISION (U/A | U/R | R/N)
- U/A (Usable/Accept) → skip
- U/R (Usable/Repair) → kandidat FR
- R/N (Replace/New) → kandidat PR
- Jika komponen juga punya GSheet disassembly → gabungkan hasil scan (profile: `inspection+disassembly`)

### Algoritma Deduplikasi
1. Normalisasi nama part: lowercase + trim + collapse whitespace
2. Part key = `nama_part` atau `nama_part@section` (jika ada section/tab)
3. Cek apakah sudah ada FR/PR di database dengan key yang sama → skip jika sudah ada
4. Cek apakah sudah ada di kandidat batch saat ini → skip duplikat

### Parsing Header GSheet (Detail Teknis)

**Inspection Parser:**
- Scan 45 baris pertama untuk menemukan header (NO, PARTS NAME, PART NUMBER)
- Cari sub-header U/R dan R/N (bisa di baris yang sama atau baris bawah header)
- Prioritas: exact match ("U/R") > loose match (contains "U/R")
- Fallback posisional: kolom DECISION + 1 = U/R, + 2 = R/N
- Jika U/R dan R/N di kolom sama → reset R/N (hanya U/R yang valid)

**Disassembly Parser:**
- Dukung multiple section (header berulang) dalam satu sheet
- Deteksi header: NO, PART NAME/DESCRIPTION, REUSE/SALVAGE/REPLACE
- Support Cylinder Head khusus (ada boundary measurement sub-table)
- Checkbox detection: TRUE/false/"✓"/"v"/"V"/1

---

## 24. Alur Detail StageTransitionService

### Method `advance()` — Naikkan Tahap

```
1. Ambil currentStage dari komponen
2. Hitung nextStage = currentStage + 1
3. Tutup log tahap saat ini:
   - Set end_time = now()
   - Jika via approval: set approved_by + approved_at
4. Update komponen:
   - current_stage = nextStage
   - is_waiting_approval = false
   - status = 'Ready for Use' jika nextStage == 7, else 'On Progress'
5. Snapshot checksheet untuk stage baru:
   - Cari ChecksheetTemplate yang cocok (EGI-spesifik > generic)
   - Buat ComponentChecksheet baru (items dari template, answers kosong)
6. Buat OverhaulLog baru untuk stage baru:
   - start_time = now()
   - Jika tahap akhir (7): langsung tutup (end_time = now())
7. Return true jika sudah RFU
```

### Method `requestApproval()` — Ajukan Approval

```
1. Set komponen is_waiting_approval = true
2. Update log berjalan:
   - approval_requested_by = user ID pengaju
   - approval_requested_at = now()
   - Reset approved_by & approved_at (null)
```

### Method `reject()` — Tolak Approval

```
1. Set komponen is_waiting_approval = false
2. Update log berjalan:
   - Append catatan "Approval ditolak oleh {nama} ({waktu})"
   - Reset approval_requested_by & at (null)
   → Mekanik bisa mengajukan ulang setelah perbaikan
```

---

## 25. Google Apps Script Web App (tools/gsheet_copy_webapp.gs)

File terbesar di folder tools (39KB) — ini adalah backend Google Apps Script yang di-deploy sebagai Web App.

### Fungsi Utama

| Action | Fungsi |
|---|---|
| `doPost(action=copy)` | Duplikasi template spreadsheet → return URL baru |
| `doPost(action=read)` | Baca isi spreadsheet (semua tab yang cocok keyword) → return values JSON |
| `doPost(action=format)` | Format ulang template (header, border, checkbox) |

### Alur Duplikasi Template
1. Laravel POST ke Apps Script dengan `template_id` + `name` + `secret`
2. Apps Script copy spreadsheet via `SpreadsheetApp.openById().copy()`
3. Rename file sesuai format: `DISASSY Engine PC2000-8 - SN 123456`
4. Return `{ ok: true, url: "https://docs.google.com/spreadsheets/d/..." }`
5. Laravel simpan URL ke kolom `gsheet_*` di tabel components

### Alur Baca Spreadsheet
1. Laravel POST ke Apps Script dengan `spreadsheet_id` + `sheet_keywords`
2. Apps Script cari tab yang namanya cocok keyword (mis. "disassy", "inspection")
3. Baca seluruh values dari tab-tab yang cocok
4. Return `{ ok: true, sheets: [{ name: "...", values: [[...], ...] }] }`

### Autentikasi
- Menggunakan `secret` yang harus cocok dengan `GSHEET_COPY_SECRET` di `.env`
- Apps Script deployed as "Execute as: Me" + "Anyone" access

---

## 26. Timeline Migration (Kronologis Pengembangan)

| Tanggal | Migration | Fitur yang Ditambahkan |
|---|---|---|
| Awal | `create_users_table` | User, cache, jobs, passkeys |
| 07 Jul | `create_permission_tables` | RBAC Spatie |
| 07 Jul | `create_components_table` | Komponen + overhaul logs + inspection + part requests |
| 14 Jul | `remap_overhaul_stages` | Refaktor dari 8→9 tahap menjadi 7 tahap |
| 14 Jul | `add_major_category` | Kategori Engine/Powertrain |
| 14 Jul | `create_checksheet_templates` | Template checksheet per EGI |
| 15 Jul | `add_crc_fields` | Field CRC (smr, life_time, manifest, dll) |
| 22 Jul | `add_gsheet_url` | Integrasi Google Sheets |
| 24 Jul | `create_fabrication_requests` | Modul FR |
| 26 Jul | `add_section_to_fr_and_pr` | Section per tab GSheet |
| 27 Jul | `create_spreadsheet_layouts` | Checksheet lokal (layout Excel 1:1) |
| 30 Jul | `add_mol_and_fr_tracking` | MOL + tracking FR |
| 31 Jul | `add_image_and_plo_fields` | Gambar + field form PLO/09/F-021 |
| 04 Ags | `add_mol_header_fields` | Header MOL |
| 04 Ags | `add_signature_and_multi_worktype` | Multi tanda tangan + multi jenis kerja |
| 04 Ags | `add_form_code_fields` | Kode formulir editable |
| 04 Ags | `add_editable_identity_and_image_layout` | Layout gambar drag & resize |
| 05 Ags | `add_images_and_signature_layout` | Multi gambar + layout tanda tangan |
| 05 Ags | `add_mol_document_path` | Upload dokumen MOL |
| 05 Ags | `add_stage_gsheets_and_painting` | GSheet assembly/testbench + foto painting |
| 06 Ags | `create_stage_mechanic_logs` | Crew mekanik + Man Hour |
| 11 Ags | `add_ro_approval_tracking` | Tracking approval + dokumen assembly |
| 11 Ags | `add_developer_role` | Role Developer |
| 11 Ags | `create_gsheet_templates` | Template GSheet di database (kelola via UI) |
| 12 Ags | `add_annotations_to_fr` | Anotasi garis/teks pada gambar FR |

---

## 27. Custom Cast & Helper Classes

### CompressedJson Cast (`app/Casts/CompressedJson.php`)

Custom Eloquent cast untuk menyimpan data JSON besar dalam format terkompresi (gzip). Digunakan oleh `SpreadsheetLayout.layout` yang bisa mencapai 30MB dalam bentuk JSON polos → ~4.5MB setelah dikompresi.

```php
// Saat baca dari database:
json_decode(gzuncompress($value))

// Saat simpan ke database:
gzcompress(json_encode($value))
```

### OcmsAccess Helper (`app/Support/OcmsAccess.php`)

Satu-satunya sumber kebenaran untuk RBAC. Semua pengecekan akses di controller dan view merujuk ke class ini:

```php
OcmsAccess::canOperateOverhaul($user)    // Boleh proses overhaul?
OcmsAccess::canApproveStages($user)      // Boleh approve tahap?
OcmsAccess::canRegisterComponents($user) // Boleh daftar komponen?
OcmsAccess::canManageTemplates($user)    // Boleh kelola template?
OcmsAccess::canManageComponents($user)   // Boleh edit/hapus komponen?
OcmsAccess::isLogisticsReviewOnly($user) // Logistik (review only)?
```

---

## 28. Known Issues & Status Terkini

### Masalah yang Diketahui
1. **Template Powertrain OCR**: Beberapa checksheet Powertrain masih menggunakan template generik karena PDF sumbernya adalah scan gambar tanpa text layer → perlu OCR atau input manual
2. **HD1500-7 Rear Axle**: Di-skip untuk Receiving Inspection karena PDF sumber hanya berisi item "Disassembly"
3. **DomPDF Limitation**: Nested table dalam PDF tidak selalu render sempurna → solusi: flatten table structure

### Status per Kategori Komponen

| Kategori | Receiving | Disassembly | Measurement | Assembly | Testbench |
|---|---|---|---|---|---|
| Engine (8 EGI) | ✅ Complete | ✅ GSheet | ✅ GSheet | ✅ GSheet | ✅ GSheet |
| Control Valve (8 EGI) | ✅ Complete | ✅ GSheet | ✅ GSheet | ✅ GSheet | ⚠️ Partial |
| Hydraulic Cylinder | ✅ Complete | ✅ GSheet | ✅ GSheet | ✅ GSheet | — |
| Front Suspension | ✅ Complete | ✅ GSheet | ✅ GSheet | ✅ GSheet | — |
| Rear Suspension | ✅ Complete | ✅ GSheet | ✅ GSheet | ✅ GSheet | — |

---

*Dokumen ini di-generate pada 13 Agustus 2026 berdasarkan inspeksi langsung terhadap seluruh file proyek.*
