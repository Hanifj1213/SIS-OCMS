# Powertrain — Cek Soft Copy Lama (`002. CHECKSHEET KOMPONEN`)

Update: 2026-07-23

Dicek terhadap yang **belum SIAP / masih kosong** di `powertrain_siap_gap_list.md`.

## Cara baca

Di folder 002, file Powertrain biasanya **1 PDF paket** (bukan dipisah Recv/Dis/Insp).  
Jadi statusnya:

| Status | Arti |
|--------|------|
| **Ada paket** | Soft copy ada di 002 (bisa dipecah / dipakai) |
| **Tidak ada** | Tidak ketemu di 002 |
| **CAT only** | Ada, tapi untuk Caterpillar (bukan Komatsu EGI kita) |

---

## 1. Apa yang ADA di 002 (Powertrain)

| EGI | Final Drive | TC / Transmission | Diff | Axle | PTO | Swing | Control Valve | Cylinder/Susp |
|-----|:-----------:|:-----------------:|:----:|:----:|:---:|:-----:|:-------------:|:-------------:|
| HD785-7 | Ada paket | Ada paket (Torque Flow) | Ada paket | Ada paket + Delivery | — | — | — | Ada (Hoist/Susp/Steering) |
| D155-6 | Ada paket (LH/RH) | Ada paket (Power Module) | — | — | — | — | — | — |
| D375-6 | Ada paket (LH/RH, label D375A-5) | Ada paket (Power Module) | — | — | — | — | — | — |
| GD825A-2 | Ada paket | Ada paket (Transmisi) | — | — | — | — | — | — |
| WA800-3 | **Tidak ada** | Ada paket (banyak: Recv/Dis/Assy/Deliv/VM) | — | — | — | — | Cover saja | — |
| PC1250-8 | Ada paket + Delivery | — | — | — | Ada paket | Ada paket + Delivery | Ada (1/2/3) | — |
| PC2000-8 | Ada paket (LH/RH) | — | — | — | Ada paket | Ada paket (Front/Rear) | Ada (LH/RH) + Delivery | — |
| HD1500 | — | Ada paket (Torque Flow) | — | Ada paket (Rear Axle) | — | — | — | — |
| HD465-7R | **Tidak ada** (folder 002 cuma ENGINE) | **Tidak ada** | **Tidak ada** | **Tidak ada** | — | — | — | — |

### File penting (contoh path)

**HD785-7**
- `1. HD785-7/POWERTRAIN/3. Rear Axle/HD785-7 FINAL DRIVE LH.pdf` (+ RH)
- `…/CHECKSHEET DELIVERY FINAL DRIVE HD785-7.pdf`
- `…/HD785-7 DIFFERENTIAL.pdf`
- `…/CHECKSHEET DELIVERY REAR AXLE HD785-7.pdf`
- `…/2. Torque Flow/HD785-7 TORQUE FLOW.pdf` (+ Delivery)
- `…/ALL CYLINDER/Overhaul Hoist/Front/Rear Suspension.xlsx` + Disassy/Assy update

**D155-6**
- `2. D155-6/POWERTRAIN/D155-6 FINAL DRIVE LH.pdf` (+ RH)
- `…/D155-6 POWER MODULE.pdf`

**D375-6**
- `3. D375-6/POWERTRAIN/D375A-5 FINAL DRIVE LH.pdf` (+ RH)
- `…/D375A-5 POWER MODULE UPDATED.pdf`

**GD825A-2**
- `6. GD825A-2/POWERTRAIN/GD825A-2 FINAL DRIVE.pdf`
- `…/GD825A-2 TRANSMISI UPDATE 26.08.2024.pdf`

**WA800-3** (Transmission lengkap; Final Drive tidak ada)
- `…/TRANSMISI RECIVING PRINT KE 1.pdf`
- `…/diss_TRANSMISSION_WA800-3….pdf`
- `…/vm TF wa 800 - 3….pdf / .xlsx`
- `…/Assembly Check Sheet_TRANSFERTRANSMISSION….pdf`
- `…/DELIVERY TRANSMISI….pdf`

**PC1250-8**
- `…/PC1250-8 FINAL DRIVE.pdf` + Delivery FD
- `…/PC1250-8 PTO.pdf`
- `…/PC1250-8 SWING MACHINERY.pdf` + Delivery Swing
- `…/PC1250-8 CONTROL VALVE 1/2/3.pdf`

**PC2000-8**
- `…/PC2000-8 FINAL DRIVE LH/RH.pdf`
- `…/PC2000-8 PTO.pdf`
- `…/PC2000-8 SWING MACHINERY FRONT/REAR.pdf`
- `…/PC2000-8 CONTROL VALVE LH/RH.pdf`

**HD1500-7**
- `11. HD1500-7/HD1500-7 REAR AXLE.pdf`
- `…/HD1500-7 TORQUE FLOW.pdf`

**Caterpillar (bukan EGI utama kita)** — di `CHECK SHEET ALL CATERPILLAR/POWERTRAIN/`: Diff, FD, TC, Receiving Transmission CAT777.

---

## 2. Yang sebelumnya KOSONG — bisakah diisi dari 002?

Fokus tahap yang di gap list masih **—** (belum ketemu di folder ROBBY).

| Komponen | EGI | Tahap kosong di ROBBY | Di 002? | Catatan |
|----------|-----|----------------------|---------|---------|
| Final Drive | D155-6 | Dis, Test | **Ada paket** | PDF FD LH/RH — perlu dicek/pecah isinya |
| Final Drive | D375-6 | Dis, Insp, Assy, Deliv, Test | **Ada paket** | PDF FD (label D375A-5) |
| Final Drive | HD785-7 | Deliv | **Ada** | `CHECKSHEET DELIVERY FINAL DRIVE…` |
| Final Drive | PC1250-8 | Insp, Assy, Test | **Ada paket** | PDF FD (+ Delivery ada) |
| Final Drive | PC2000-8 | Recv, Dis, Assy, Deliv, Test | **Ada paket** | PDF FD LH/RH |
| Final Drive | GD825A-2 | Deliv, Test | **Ada paket** | PDF FD (Deliv/Test belum jelas terpisah) |
| Final Drive | WA800-3 | Recv, Dis, Assy, Deliv, Test | **Tidak ada** | — |
| Final Drive | HD465-7R | Dis, Insp, Assy, Deliv, Test | **Tidak ada** | 002 HD465 cuma ENGINE |
| Final Drive | HD785-5 | Dis, Assy, Test | **Tidak ada** | Tidak ada folder HD785-5 di 002 |
| TC/Transmission | D155-6 | Dis, Test | **Ada paket** | Power Module PDF |
| TC/Transmission | D375-6 | Dis, Insp, Deliv, Test | **Ada paket** | Power Module PDF |
| TC/Transmission | HD785-7 | Insp, Assy, Deliv, Test | **Ada paket** | Torque Flow PDF + Delivery TF |
| TC/Transmission | WA800-3 | Dis, Insp | **Ada** | `diss_…` + `vm TF…` |
| TC/Transmission | GD825A-2 | Recv, Dis, Insp, Deliv | **Ada paket** | Transmisi PDF update |
| TC/Transmission | HD1500 | Dis, Assy, Test | **Ada paket** | Torque Flow PDF |
| Differential | HD785-7 | Insp, Deliv, Test | **Ada paket** | `HD785-7 DIFFERENTIAL.pdf` (Deliv Diff belum terpisah) |
| Differential | WA800-3 | Dis, Test | **Tidak ada** | — |
| Axle | HD785-7 | Recv, Dis, Insp, Deliv, Test | **Sebagian** | Ada Delivery Rear Axle + paket di Rear Axle folder |
| Axle | HD1500 | — (banyak sudah Folder) | **Ada paket** | Rear Axle PDF |
| Axle | HD465-7R | Dis, Insp, … | **Tidak ada** | — |
| PTO | PC1250-8 | Assy, Test | **Ada paket** | `PC1250-8 PTO.pdf` |
| PTO | PC2000-8 | Dis, Assy, Deliv, Test | **Ada paket** | `PC2000-8 PTO.pdf` |
| Swing | PC1250-8 | Dis, Insp, Assy, Test | **Ada paket** | Swing PDF + Delivery |
| Swing | PC2000-8 | Dis, Assy, Test | **Ada paket** | Swing Front/Rear PDF |

---

## 3. Ringkas

### Bisa ditolong dari 002 (prioritas)
1. **Final Drive** — HD785, D155, D375, GD825, PC1250, PC2000  
2. **TC / Power Module / Torque Flow** — HD785, D155, D375, WA800, GD825, HD1500  
3. **PTO + Swing** — PC1250, PC2000  
4. **Differential + Delivery FD/Axle** — HD785  

### Tetap tidak ada di 002
- Final Drive **WA800-3**, **HD465-7R**, **HD785-5**
- Differential **WA800-3**
- Hampir semua Powertrain **HD465-7R** (002-nya ENGINE only)

### Catatan
PDF paket di 002 sering **multi-tahap dalam 1 file** → untuk jadi SIAP masih perlu dicek halaman / dipecah (Recv, Disassy, Insp, …) seperti yang sudah dilakukan untuk Control Valve COMPLETED.

---

Lihat juga: `tools/powertrain_siap_gap_list.md` (status SIAP vs folder ROBBY).
