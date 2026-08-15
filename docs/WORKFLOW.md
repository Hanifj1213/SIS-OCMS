# SISI-OCMS — Dokumen Workflow Sistem

**Overhaul Component Management System (OCMS)**
Dokumen ini menjelaskan secara detail cara kerja aplikasi: alur overhaul komponen stage 1–7, otomasi Fabrication Request (FR) / Part Request (PR/MOL), dan integrasi Google Sheets (Apps Script).

Ditujukan untuk tim Head Office sebagai referensi konversi ke .NET Core + SQL Server.

**Stack saat ini (bukan target konversi):** Laravel (PHP) + **MySQL 8** Laragon (`sisi_ocms` di `127.0.0.1:3306`). Antrian job GSheet: `QUEUE_CONNECTION=database` — butuh `php artisan queue:work`. Tes otomatis memakai SQLite di memori (`phpunit.xml`), terpisah dari app yang jalan.

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Aktor & Hak Akses (Role)](#2-aktor--hak-akses-role)
3. [Workflow Utama: Overhaul Stage 1–7](#3-workflow-utama-overhaul-stage-17)
4. [Detail Per Stage](#4-detail-per-stage)
5. [Workflow Auto-FR / PR (Stage 2)](#5-workflow-auto-fr--pr-stage-2)
6. [Cara Kerja Parser Keputusan (Dinamis)](#6-cara-kerja-parser-keputusan-dinamis)
7. [Integrasi Google Sheets — Apps Script](#7-integrasi-google-sheets--apps-script)
8. [Penomoran Dokumen](#8-penomoran-dokumen)
9. [Skema Database](#9-skema-database)
10. [Peta File Kode (untuk tim konversi)](#10-peta-file-kode-untuk-tim-konversi)

---

## 1. Gambaran Umum Sistem

OCMS mengelola siklus **overhaul komponen alat berat** (Engine, Powertrain: Control Valve, Hydraulic Cylinder, Suspension, dll.) dari penerimaan sampai siap pakai, melalui **7 stage** dengan gerbang approval.

Tiga pilar utama:

| Pilar | Fungsi |
|---|---|
| **Workflow Stage 1–7** | Tracking posisi komponen, log pengerjaan, approval atasan per stage |
| **Checksheet Google Sheets** | Tiap komponen otomatis dibuatkan salinan spreadsheet checksheet (disassembly, measurement, assembly, testbench) dari template master sesuai EGI/model unit |
| **Auto-FR / PR** | Sistem membaca centang keputusan mekanik di spreadsheet dan otomatis menerbitkan dokumen **Fabrication Request (form PLO/09/F-021)** untuk part yang diperbaiki, dan **Part Request (MOL)** untuk part yang diganti baru |

Arsitektur saat ini:

```mermaid
flowchart LR
    subgraph Client["Browser (Mechanic / Supervisor / Management)"]
        UI["Web UI (Blade)"]
    end
    subgraph Server["Laravel (PHP)"]
        CC["ComponentController"]
        FRC["FabricationRequestController"]
        STS["StageTransitionService"]
        FRS["FabricationRequestService"]
        CGS["ChecksheetGsheetService"]
        Q["Queue Job: DuplicateChecksheetGsheets"]
    end
    DB[("MySQL (Laragon: sisi_ocms)")]
    subgraph Google["Google Workspace"]
        WA["Apps Script Webapp (doPost)"]
        GS[("Google Sheets & Drive")]
    end
    PDF["PDF FR (DomPDF, PLO/09/F-021)"]

    UI --> CC & FRC
    CC --> STS
    CC --> Q
    FRC --> FRS
    FRS --> CGS
    Q --> CGS
    CGS -- "HTTP POST + secret" --> WA
    WA --> GS
    STS & FRS & CGS --> DB
    FRC --> PDF
```

> Pada konversi .NET: kotak **Laravel** diganti ASP.NET Core, **MySQL** diganti SQL Server, penyimpanan file (PDF/foto/xlsx) pindah ke Azure Blob Storage, login lokal dihapus (SSO Microsoft di depan aplikasi). Kotak **Google Workspace tidak berubah** — webapp dipanggil via HTTP biasa.

---

## 2. Aktor & Hak Akses (Role)

Sumber: `app/Support/OcmsAccess.php`, dicek lewat method di model `User`.

| Role | Daftar komponen | Proses stage | Approve/Reject stage | FR / MOL | Kelola user & template |
|---|:--:|:--:|:--:|:--:|:--:|
| **Mechanic** | ✔ | ✔ | ✖ | ✔ | ✖ |
| **Group Leader** | ✔ | ✔ | ✔ | ✔ | ✖ |
| **Supervisor** | ✔ | ✔ | ✔ | ✔ | ✖ |
| **Dept/CRC/Section/Logistic Head, Planner** (FULL_ACCESS) | ✔ | ✔ | ✔ | ✔ | ✖ |
| **Logistik** | ✔ | ✖ (review saja) | ✖ | ✖ | ✖ |
| **Developer** | ✔ | ✖ | ✖ | ✖ | ✔ (template, edit/hapus komponen) |
| **SuperAdmin** | ✔ | ✔ | ✔ | ✔ | ✔ |

Autentikasi saat ini: login **NIK + password** (Laravel Fortify + Spatie Permission).
Target konversi: **tanpa login lokal** — identitas & role disuntik lewat SSO Microsoft (Entra ID); pemetaan role di atas nantinya ke grup AD.

---

## 3. Workflow Utama: Overhaul Stage 1–7

Nama stage (sumber: `ComponentController::STAGE_NAMES`):

| Stage | Nama | Approval untuk lanjut? |
|:--:|---|:--:|
| 1 | Receiving (Penerimaan DC) | Tidak — auto lanjut |
| 2 | DIS Assembling (Pembongkaran, Pencucian & Pengukuran) | **Ya** |
| 3 | Machining & Fabrication (Perbaikan) | **Ya** |
| 4 | Assembly (Perakitan) | **Ya** |
| 5 | Test Performance & Painting (Uji Fungsi & Pengecatan) | **Ya** |
| 6 | Delivery (Serah Terima) | Tidak — auto lanjut |
| 7 | RFU (Ready for Use) | — (status akhir) |

### Flowchart siklus hidup komponen

```mermaid
flowchart TD
    A["Pendaftaran komponen<br/>(POST /components — ComponentController@store)"] --> B["current_stage = 1, status On Progress<br/>+ QR code + log stage 1"]
    B --> C["Job antrian: DuplicateChecksheetGsheets<br/>copy template GSheet sesuai EGI →<br/>isi kolom gsheet_*_url"]
    C --> S1

    S1["Stage 1 — Receiving"] -->|"Selesaikan tahap"| S2
    S2["Stage 2 — DIS Assembling<br/>(checksheet GSheet salinan,<br/>scan FR & MOL)"] -->|"Ajukan penyelesaian"| AP2{"Approval<br/>GL / Supervisor?"}
    AP2 -->|Approve| S3
    AP2 -->|Reject| S2
    S3["Stage 3 — Machining & Fabrication<br/>(pengerjaan FR, output foto hasil)"] -->|"Ajukan"| AP3{Approval?}
    AP3 -->|Approve| S4
    AP3 -->|Reject| S3
    S4["Stage 4 — Assembly<br/>(GSheet assembly + dokumen)"] -->|"Ajukan"| AP4{Approval?}
    AP4 -->|Approve| S5
    AP4 -->|Reject| S4
    S5["Stage 5 — Test & Painting<br/>(GSheet testbench + foto painting)"] -->|"Ajukan"| AP5{Approval?}
    AP5 -->|Approve| S6
    AP5 -->|Reject| S5
    S6["Stage 6 — Delivery"] -->|"Selesaikan tahap"| S7
    S7["Stage 7 — RFU<br/>status = Ready for Use"]
```

### Mekanisme transisi stage

Semua transisi lewat `StageTransitionService`:

1. **Selesaikan tahap** (`POST /components/{comp}/update-stage`, `ComponentController@updateStage`):
   - Stage **1 dan 6** → langsung `advance()` (naik 1 stage, tanpa approval).
   - Stage **2–5** → `requestApproval()`: set flag `is_waiting_approval`, catat siapa yang mengajukan. Stage **belum naik**.
2. **Approve** (`POST /components/{comp}/approve-stage`, hanya role approver) → `advance(approvedBy: user)`:
   - Tutup `overhaul_logs` & `stage_mechanic_logs` stage berjalan (clock-out crew).
   - `current_stage + 1`, buat log baru, siapkan checksheet stage berikut (`ensureChecksheetForStage`).
   - Jika sampai stage 7 → status komponen `Ready for Use`.
3. **Reject** (`POST /components/{comp}/reject-stage`) → hapus flag waiting, stage tetap, mekanik memperbaiki lalu mengajukan ulang.

Semua operasi dibungkus transaksi DB + lock baris komponen (`lockComponent`) agar dua user tidak memproses bersamaan.

### Sequence diagram approval (stage 2–5)

```mermaid
sequenceDiagram
    actor M as Mechanic
    actor SPV as GL / Supervisor
    participant App as OCMS
    participant DB as Database

    M->>App: Selesaikan tahap (update-stage)
    App->>DB: is_waiting_approval = true,<br/>approval_requested_by/at di overhaul_logs
    App-->>M: "Menunggu approval"
    SPV->>App: Approve stage
    App->>DB: Tutup log & crew stage N,<br/>current_stage = N+1, log baru
    App-->>SPV: Komponen naik ke stage N+1
    Note over SPV,App: Jika Reject: flag dihapus,<br/>stage tetap, mekanik revisi
```

---

## 4. Detail Per Stage

### Stage 1 — Receiving

- Input identitas komponen: `serial_number`, `egi`, `unit_code`, `major_category` (Engine / Powertrain sub-kategori), SMR, manifest, way bill, RO number, dsb.
- Sistem membuat **QR code** komponen dan checksheet Receiving.
- Di belakang layar, job antrian menyalin **semua template GSheet** yang relevan untuk EGI tersebut (disassembly, measurement, subassy, SDR, assembly, testbench) — hasil salinannya disimpan sebagai URL di kolom `gsheet_*_url` komponen. Kalau job belum selesai/gagal, halaman komponen akan men-dispatch ulang otomatis saat dibuka.

### Stage 2 — DIS Assembling (jantung sistem)

Satu jalur resmi: **Google Sheets salinan per komponen** (bukan master template, bukan form inspeksi digital).

Mekanik membuka checksheet GSheet yang di-embed di halaman komponen, mengisi hasil bongkar/ukur, dan **mencentang checkbox keputusan** di baris part:

| Kategori | Spreadsheet yang discan | Keputusan | Akibat |
|---|---|---|---|
| Engine | Disassembly (+ subassy disassembly) | REUSE | tidak ada dokumen |
| | | **SALVAGE** | **FR** (part diperbaiki) |
| | | **REPLACE** | **PR/MOL** (minta part baru) |
| Powertrain | Inspection/Measurement | U/A (use again) | tidak ada dokumen |
| | | **U/R** (use with repair) | **FR** |
| | | **R/N** (replace new) | **PR/MOL** |

Lalu operator menekan **Scan Spreadsheet** → sistem membaca semua tab relevan dan langsung membuat FR + PR (detail di §5).

Jika `gsheet_*_url` masih kosong (job copy belum selesai / secret Apps Script salah): halaman menampilkan banner **“Salinan sedang disiapkan”**. Iframe **tidak** boleh mengarah ke master template. Form Inspeksi Digital (Crankshaft / Piston Ring / Cylinder Liner) **sudah dihapus**.

Catatan: **Engine Measurement bukan sumber FR** — hanya data ukuran. FR Engine tetap dari Disassembly (SALVAGE).

### Stage 3 — Machining & Fabrication

- Daftar FR hasil stage 2 dikerjakan (fabrikasi/machining). Tombol scan FR/MOL juga masih tersedia di stage ini.
- FR bisa dicetak PDF (form PLO/09/F-021); status FR: `draft` → `printed` (otomatis saat PDF dibuka) → `done`.
- Output pekerjaan (foto hasil) dilampirkan ke FR.

### Stage 4 — Assembly

- Checksheet GSheet assembly (`gsheet_assembly_url`) + upload dokumen assembly.

### Stage 5 — Test Performance & Painting

- Checksheet GSheet testbench (`gsheet_testbench_url`) + upload foto painting (`painting_images`).

### Stage 6 — Delivery → Stage 7 — RFU

- Serah terima; menyelesaikan stage 6 langsung menaikkan ke stage 7 dan status komponen menjadi **Ready for Use**.

> Validasi checksheet internal **di-skip** di stage 2 (selalu, karena checksheet resmi = GSheet), serta stage 4/5 bila salinan `gsheet_assembly_url` / `gsheet_testbench_url` sudah ada.

---

## 5. Workflow Auto-FR / PR (Stage 2)

Endpoint: `POST /components/{component}/fr/scan` → `FabricationRequestController@scan`. Prinsip: **1 FR = 1 part**.

```mermaid
flowchart TD
    START(["Operator klik 'Scan Spreadsheet'"]) --> PROF{"Kategori komponen?"}
    PROF -->|Engine| PE["Profil: disassembly<br/>Baca gsheet_url +<br/>gsheet_subassy_disassembly_url"]
    PROF -->|Powertrain / non-Engine| PP["Profil: inspection<br/>Baca gsheet_measurement_url<br/>(+ merge disassembly bila ada)"]

    PE & PP --> READ["Panggil Apps Script action 'read'<br/>dengan sheet_keywords<br/>→ SEMUA tab yang cocok dikembalikan"]
    READ --> PARSE["Parser dinamis per tab:<br/>cari baris header via teks kolom<br/>(bukan posisi tetap)"]
    PARSE --> ROWS["Kumpulkan baris part:<br/>NO + PART NAME + P/N +<br/>status centang tiap kolom keputusan<br/>+ nama tab (section)"]

    ROWS --> DEC{"Keputusan<br/>per part?"}
    DEC -->|"SALVAGE / U-R (centang)"| FR["Kandidat FR (needs_repair)"]
    DEC -->|"REPLACE / R-N (centang)"| PR["Kandidat PR (needs_replace)"]
    DEC -->|"REUSE / U-A / kosong"| SKIP["Lewati"]

    FR --> DEDUP1["Dedup: part + section<br/>yang sudah punya FR dilewati"]
    PR --> DEDUP2["Dedup part_requests"]

    DEDUP1 --> MKFR["Buat FR draft:<br/>nomor FR/SIS/RC/{4digit}/{Romawi}/{tahun}/INT<br/>simpan ke fabrication_requests<br/>(kolom section = nama tab)"]
    DEDUP2 --> MKPR["Buat Part Request (MOL)<br/>di part_requests, status Pending"]

    MKFR & MKPR --> RESP["Response JSON: created_fr, created_pr,<br/>skipped, total, warning"]
    RESP --> UI2["Panel FR/MOL di halaman komponen<br/>menampilkan hasil"]
    UI2 --> PDF["Download PDF per FR<br/>(form PLO/09/F-021, A4 landscape)<br/>draft → printed"]
```

Poin penting perilaku:

- **Scan langsung menyimpan** (bukan sekadar preview): `scanCandidates()` → `createFromCandidates()` + `createPartRequestsFromCandidates()`.
- **Dedup per `part_name + section`** — part bernama sama di tab berbeda (mis. valve NO1 vs NO2) tetap jadi FR terpisah, tapi scan ulang tidak menduplikasi.
- Scan masih menyapu sisa baris `inspection_details` lama (data historis, form digital sudah tidak dipakai) berkeputusan Repair/Replace yang belum punya FR/PR.
- Jalur alternatif: **Form FR Kosong** (`components.fr.create` → `storeSingle`, source `manual`) untuk FR di luar hasil scan.
- FR punya field `source`: `gsheet` / `form` (data lama) / `manual`.

---

## 6. Cara Kerja Parser Keputusan (Dinamis)

Lokasi: `app/Services/ChecksheetGsheetService.php`. Prinsip: **cari header berdasarkan teks, bukan posisi kolom** — kolom REUSE di kolom U pada satu file dan kolom W di file lain sama-sama valid.

### Profil Engine — `parseDisassemblyValues()`

1. Telusuri baris demi baris mencari **baris header** yang memuat teks: `NO`, `PART`/`PARTS NAME`, `PART NUMBER`/`P/N`, `REUSE`/`REUSED`, `SALVAGE`/`SALVG`, `REPLACE`.
2. Simpan indeks kolom yang ditemukan.
3. Baris di bawah header yang punya **NO + nama part** dianggap **baris part utama** (sub-baris check point diabaikan — keputusan hanya dibaca di baris part).
4. Nilai sel di kolom SALVAGE/REPLACE dicek dengan **whitelist centang**.

### Profil Powertrain — `parseInspectionValues()`

1. Cari header `NO`, `PARTS NAME`, opsional `PART NUMBER`.
2. Kolom keputusan dari **sub-header** `U/A | U/R | R/N` (boleh di baris terpisah di bawah header utama; variasi `UR`/`RN` diterima).
3. Fallback: kalau hanya ada satu kolom `DECISION`, isinya dibaca sebagai teks (`U/R` → FR, `R/N` → PR).

### Deteksi centang — `isDecisionChecked()`

Whitelist, bukan "sel tidak kosong": boolean `TRUE`, angka `1`, teks `x`, `✓`, `v`, `yes`, `checked`, dll. Teks bebas seperti `N/A`, `OK`, atau catatan **tidak** dianggap centang (mencegah FR palsu). Sel kosong = tidak dicentang.

### Multi-tab

Semua tab yang namanya mengandung keyword dibaca dan diproses:

- Disassembly: `disassy`, `diss`, `disassembly`, `engine`
- Inspection: `inspeksi`, `inspection`, `measurement`

Nama tab dibawa di setiap baris hasil dan disimpan ke kolom **`section`** di FR/PR.

---

## 7. Integrasi Google Sheets — Apps Script

File: `tools/gsheet_copy_webapp.gs`, di-deploy sebagai **Web App** (`doPost`, URL `/exec`). Laravel memanggilnya lewat `ChecksheetGsheetService::postWebapp()` dengan konfigurasi:

```env
GSHEET_COPY_WEBAPP_URL=  # URL /exec hasil deploy
GSHEET_COPY_SECRET=      # harus sama dengan Script Property OCMS_SECRET
```

Keamanan: secret disimpan di **Script Properties** (bukan hardcode), dibandingkan constant-time; Laravel **fail-closed** (menolak jalan bila URL/secret kosong) dan hanya mengizinkan action pada allow-list. Aksi baca dibatasi hanya untuk spreadsheet yang dikelola OCMS (`isManagedSpreadsheetId`).

### Action runtime

| Action | Parameter utama | Fungsi |
|---|---|---|
| `copy` | `template_id`, `name`, `secret` | Salin template master → folder Drive `OCMS Checksheet Copies`, share anyone-with-link (edit), kembalikan `{id, url}` |
| `read` | `spreadsheet_id`, `sheet_keywords` (atau `sheet`), `secret` | Kembalikan **semua tab** yang namanya cocok keyword: `sheets: [{name, values}, …]`; tanpa match → fallback tab pertama dengan flag `matched: false` |
| `upload` | `filename`, `subdir`, `data` (xlsx base64), `secret` | Upload xlsx → dikonversi jadi Google Spreadsheet |
| `ping` | `secret` | Health check akses Drive |

Action admin (nonaktif secara default, flag `OCMS_ADMIN_ACTIONS`): `apply_checkboxes`, `apply_decision_merges`, `apply_decision_boxes`, `list_revisions`, `restore_revision`, `restore_from_xlsx`.

### Sequence: pendaftaran komponen → checksheet siap

```mermaid
sequenceDiagram
    actor U as User (daftar komponen)
    participant App as OCMS (Laravel)
    participant Job as Queue Job
    participant WA as Apps Script Webapp
    participant Drive as Google Drive/Sheets

    U->>App: POST /components (data komponen)
    App->>App: Simpan komponen (stage 1), QR, log
    App->>Job: dispatch DuplicateChecksheetGsheets
    App-->>U: Halaman komponen (URL GSheet belum ada)
    loop tiap kind: disassembly, measurement, subassy, sdr, assembly, testbench
        Job->>App: templateIdFor(kategori, EGI, kind)
        Job->>WA: POST action=copy {template_id, name, secret}
        WA->>Drive: copy file + share link
        WA-->>Job: {ok, id, url}
        Job->>App: simpan url ke kolom gsheet_*_url (MySQL)
    end
    Note over App: Embed HANYA URL salinan.<br/>URL kosong → banner pending,<br/>bukan master template.<br/>Job butuh php artisan queue:work.
```

### Sequence: scan keputusan → FR/PR

```mermaid
sequenceDiagram
    actor M as Operator
    participant App as OCMS (Laravel)
    participant WA as Apps Script Webapp
    participant GS as Google Sheets

    M->>App: POST /components/{comp}/fr/scan
    App->>WA: action=read {spreadsheet_id, sheet_keywords, secret}
    WA->>GS: baca semua tab cocok keyword
    WA-->>App: sheets: [{name, values}, ...]
    App->>App: parser dinamis + whitelist centang
    App->>App: buat FR (nomor baru) + PR, dedup
    App-->>M: JSON created_fr / created_pr / skipped
    M->>App: GET /components/{comp}/fr/{fr}/pdf
    App-->>M: PDF PLO/09/F-021 (status FR → printed)
```

Pemetaan template master per **kategori + EGI** ada di `config/checksheet_gsheets.php` (bisa dioverride dari DB `gsheet_templates`): `disassembly_templates`, `measurement_templates`, `subassy_*_templates`, `sdr_templates`, `assembly_templates`, `testbench_templates`, dengan fallback `default`.

> **Penting operasional:**
> 1. Setiap perubahan `gsheet_copy_webapp.gs` wajib **Deploy → New version**.
> 2. `OCMS_SECRET` (Script Properties) harus **sama** dengan `GSHEET_COPY_SECRET` di `.env`. Deploy versi baru **tidak** menyamakan secret.
> 3. Worker antrian harus jalan (`php artisan queue:work`). Tanpa itu URL salinan tetap kosong.

---

## 8. Penomoran Dokumen

### Fabrication Request

Format: `FR/SIS/RC/{4 digit urut}/{bulan Romawi}/{tahun}/INT` — contoh: `FR/SIS/RC/0475/VII/2026/INT`.

- Nomor urut per **tahun**, dikelola tabel `fr_number_sequences` (`year`, `last_number`) dengan **lock DB** saat alokasi (`allocateNextNumber`) agar tidak ada nomor kembar walau dua user scan bersamaan.
- Nomor manual dari luar sistem bisa disinkronkan (`syncSequenceFromManualNumber`) supaya urutan tidak tabrakan.
- Form PDF: **PLO/09/F-021**, A4 landscape, field default di `FabricationRequest::FORM_DEFAULTS`.

### Part Request (MOL)

Tidak bernomor otomatis; dicatat di `part_requests` dengan status `Pending` / `Available` / `Out of Stock` untuk ditindaklanjuti logistik.

---

## 9. Skema Database

Engine: **MySQL 8** (`sisi_ocms`). Semua service (`StageTransitionService`, `FabricationRequestService`, `ChecksheetGsheetService`) memakai koneksi default Laravel — tidak ada cabang SQLite di kode aplikasi.

```mermaid
erDiagram
    users ||--o{ overhaul_logs : "mengerjakan"
    components ||--o{ overhaul_logs : "punya log per stage"
    components ||--o{ stage_mechanic_logs : "crew per stage"
    components ||--o{ inspection_details : "data lama legacy"
    components ||--o{ fabrication_requests : "FR"
    components ||--o{ part_requests : "PR / MOL"
    components ||--o{ component_checksheets : "checksheet internal"
    fr_number_sequences ||--o{ fabrication_requests : "alokasi nomor per tahun"

    components {
        int comp_id PK
        string serial_number
        string egi
        string unit_code
        string major_category
        int current_stage
        bool is_waiting_approval
        string status
        string gsheet_url "disassembly"
        string gsheet_measurement_url
        string gsheet_subassy_disassembly_url
        string gsheet_subassy_measurement_url
        string gsheet_sdr_url
        string gsheet_assembly_url
        string gsheet_testbench_url
        json painting_images
        json assembly_documents
    }
    overhaul_logs {
        int log_id PK
        int comp_id FK
        int stage_number
        int mechanic_id FK
        datetime start_time
        datetime end_time
        int approval_requested_by
        int approved_by
    }
    fabrication_requests {
        int fr_id PK
        int comp_id FK
        string fr_number "FR/SIS/RC/..."
        string part_name
        string part_number
        string section "nama tab GSheet"
        int qty
        string work_type
        string source "gsheet|form|manual"
        string status "draft|printed|done"
        string form_no "PLO/09/F-021"
        json images
        json signatures
    }
    part_requests {
        int req_id PK
        int comp_id FK
        string part_name
        string section
        int qty
        string status "Pending|Available|OutOfStock"
    }
    inspection_details {
        int insp_id PK
        int comp_id FK
        string part_name
        string standard_value
        string actual_value
        string decision "Reused|Repair|Replace"
    }
    fr_number_sequences {
        int year PK
        int last_number
    }
    users {
        int id PK
        string name
        string nik "login saat ini; nanti diganti SSO"
    }
```

Tabel pendukung lain: `component_checksheets` (checklist internal per stage), `checksheet_templates` & `gsheet_templates` (master), `stage_mechanic_logs` (man-hour crew), `spreadsheet_layouts` + `component_spreadsheet_answers` (checksheet lokal hasil impor xlsx), tabel Spatie roles/permissions.

---

## 10. Peta File Kode (untuk tim konversi)

| Area | File Laravel | Isi |
|---|---|---|
| Workflow stage | `app/Http/Controllers/ComponentController.php` | store, show, updateStage, approveStage, rejectStage (stage 2 = GSheet, tanpa form digital) |
| | `app/Services/StageTransitionService.php` | advance/requestApproval/reject, lock & transaksi |
| FR/PR | `app/Services/FabricationRequestService.php` | penomoran, scanCandidates, create FR/PR |
| | `app/Http/Controllers/FabricationRequestController.php` | scan, store, edit, pdf, updateStatus |
| GSheet | `app/Services/ChecksheetGsheetService.php` | copy via job, read multi-tab, parser dinamis |
| | `app/Jobs/DuplicateChecksheetGsheets.php` | job antrian copy template |
| | `tools/gsheet_copy_webapp.gs` | Apps Script webapp (tetap dipakai di .NET) |
| | `config/checksheet_gsheets.php` | template ID per kategori+EGI |
| Role | `app/Support/OcmsAccess.php` | matriks role (acuan mapping grup AD) |
| UI | `resources/views/overhauls/show.blade.php` + partials | halaman komponen, panel stage, panel FR/MOL |
| PDF | `resources/views/fr/pdf.blade.php` | layout form PLO/09/F-021 |
| Test | `tests/Feature/DecisionScanTest.php` | fixture parser dari template SIAP asli |
| | `tests/Feature/GsheetCopyFallbackTest.php` | tidak embed master; tidak tampil form digital |

---

*Dokumen dibuat otomatis dari penelusuran kode, 15 Agustus 2026. Diagram memakai sintaks Mermaid — dirender otomatis di GitHub/VS Code/Cursor, dan bisa diekspor ke PNG/SVG bila diperlukan.*
