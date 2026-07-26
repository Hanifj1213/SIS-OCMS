# Status Checksheet Powertrain — COMPLETED vs Belum

Sumber kebenaran tahap penuh (Receiving → Disassy → … → Delivery) untuk saat ini
**hanya** folder:

`POWERTRAIN/CHECKSHEET CONTROL VALVE POWERTRAIN ALL UNIT PT 2026`

File `*(COMPLETED).xlsx` di situ = multi-tab siap dipakai. Folder unit lain
(D 155-6, HD 785-7, WA 800-3, …) isinya parsial / campur / belum satu alur
lengkap seperti Engine.

## Sudah COMPLETED (pakai folder CV ALL UNIT)

| File | Kategori app | EGI | Tab tahap |
|------|--------------|-----|-----------|
| cs CONTROL VALVE pc 1250-8(COMPLETED).xlsx | Control Valve | PC1250-8 | RECEIVING / DISASSY / ASSEMBLY / INSPEKSI / TESTBENCH / DELIVERY (× NO 1–3) |
| CS CV PC 2000-8(COMPLETED).xlsx | Control Valve | PC2000-8 | RECEIVING / DISASSY / INSPEKSI / ASSEMBLY / TESTBENCH / DELIVERY (LH + RH) |
| cs cv pm d155-6(COMPLETED).xlsx | Control Valve | D155-6 | receiving, DISASSY, ASEMBLY, inspeksi, DELIVERY |
| cs cv pm d375-6(COMPLETED).xlsx | Control Valve | D375-6 | receiving, DISASSY, ASSEMBLY, inspeksi, DELIVERY |
| cs cv tf hd785-7(COMPLETED).xlsx | Control Valve | HD785-7 | RECEIVING, DISASSY, ASSEMBLY, INSPEKSI, DELIVERY |
| cs cv tm gd825-2(COMPLETED).xlsx | Control Valve | GD825A-2 | RECEIVING, DISSASSY, ASSEMBLY, inspeksi, DELIVERY |
| cs cv tm wa800-3(COMPLETED).xlsx | Control Valve | WA800-3 | RECEIVING, dissassy, assembly, inspeksi, DELIVERY |
| CS CYL HOIST 785(COMPLETED).xlsx | Hydraulic Cylinder | HD785-7 | receiving → DELIVERY |
| CS FSUSP HD785-7 (COMPLETED).xlsx | Suspension (Front) | HD785-7 | receiving → DELIVERY |
| CS REARSP HD785-7(COMPLETED) .xlsx | Suspension (Rear) | HD785-7 | receiving → DELIVERY |

Folder `INK/` = form tinta (Sheet1), **bukan** checksheet proses → diabaikan.

## Belum COMPLETED (ada di tree POWERTRAIN, tapi tidak full-lifecycle)

Komponen/unit berikut punya file di folder unit masing-masing, tapi **belum**
ada workbook COMPLETED multi-tahap seperti di atas — jadi belum diimplementasi
GSheet stage 2 untuk sekarang:

| Unit | Komponen di folder (parsial) |
|------|------------------------------|
| D 155-6 | Final Drive, Power Modul (TC/TM) — CV sudah di COMPLETED |
| DZ 375-5 / DZ 375-6 | Control Valve (versi lama), Final Drive, Power Modul |
| GD 705-4 | Control Valve, Final Drive, Transmission |
| GD 825-2 | Final Drive, Transmission (+ test) — CV sudah di COMPLETED |
| HD 465-7R | Axle, CV, FD LH/RH, Torque Flow (banyak receiving saja) |
| HD 785-5 / HD 785-7 | Axle, Diff, FD, Torque Flow, Gear Pump — CV/Hoist/Susp sudah di COMPLETED |
| HD1500 | Rear Axle, TC, Transmission |
| PC 1250-8 / PC 2000-8 | Final Drive, PTO, Swing Machinery, Piston Pump — CV sudah di COMPLETED |
| PC3000 | hampir kosong / sheet label masih PC1250 |
| WA 800-3 | Axle, Diff, FD, Piston Pump, Transmission — CV sudah di COMPLETED |
| PERFORMANCE TEST ALL UNIT | test stage 5 lintas unit (belum di-wire) |

## Scope implementasi saat ini (disepakati)

1. **Stage 1 Receiving** — quiz `ChecksheetTemplate` (sama Engine), untuk kategori
   Powertrain yang sudah punya template + EGI dilengkapi sesuai COMPLETED.
2. **Stage 2 DIS Assembling** — iframe Google Sheets dari tab DISASSY yang
   sudah dipisah dari file COMPLETED (Control Valve + Hoist + Suspension).
3. Stage 3–7 spreadsheet menyusul setelah upload ID sheet per tahap.
