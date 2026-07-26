# Powertrain — Status Semua Tahap

Update: 2026-07-23

## Arti tanda

| Tanda | Arti |
|-------|------|
| **SIAP** | Sudah di `_SIAP_UPLOAD_GSHEET` |
| **Folder** | Ada di folder unit ROBBY, belum di-split |
| **—** | Belum ketemu |

Tahap: **Recv** · **Dis** · **Insp** · **Assy** · **Deliv** · **Test**

> Cek soft copy lama: `tools/powertrain_002_softcopy_check.md`  
> Folder soft copy **tanpa EGI coretan merah**: `POWERTRAIN/_SOFTCOPY_002_FILTERED/` (lihat README di dalamnya)

---

## 1. Sudah di SIAP

| Komponen | EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|----------|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| Control Valve | PC1250-8 | SIAP | SIAP | SIAP | SIAP | SIAP | SIAP |
| Control Valve | PC2000-8 | SIAP | SIAP | SIAP | SIAP | SIAP | SIAP |
| Control Valve | D155-6 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Control Valve | D375-6 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Control Valve | HD785-7 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Control Valve | GD825A-2 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Control Valve | WA800-3 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Cylinder Hoist | HD785-7 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Front Suspension | HD785-7 | SIAP | SIAP | SIAP | SIAP | SIAP | — |
| Rear Suspension | HD785-7 | SIAP | SIAP | SIAP | SIAP | SIAP | — |

**Kurang di SIAP:** hampir semua cuma kurang **Test** (kecuali PC1250 & PC2000).

---

## 2. Belum di SIAP — semua tahap

### Final Drive

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| D155-6 | Ada | — | Ada | Ada | Ada | — |
| D375-6 | Ada | — | — | — | — | — |
| HD785-7 | Ada | Ada | Ada | Ada | — | Ada |
| HD785-5 | Ada | — | Ada | — | Ada | — |
| HD465-7R | Ada | — | — | — | — | — |
| PC1250-8 | Ada | Ada | — | — | Ada | — |
| PC2000-8 | — | — | Ada | — | — | — |
| GD825A-2 | Ada | Ada | Ada | Ada | — | — |
| WA800-3 | — | — | Ada | — | — | — |

### TC / Transmission / Power Module

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| D155-6 | Ada | — | Ada | Ada | Ada | — |
| D375-6 | Ada | — | — | Ada | — | — |
| D375-5 | Ada | Ada | — | Ada | — | Ada |
| HD785-7 | Ada | Ada | — | — | — | — |
| HD785-5 | Ada | Ada | Ada | Ada | — | Ada |
| HD465-7R | Ada | — | — | Ada | — | Ada |
| WA800-3 | Ada | — | — | Ada | Ada | Ada |
| GD825A-2 | — | — | — | Ada | — | Ada |
| GD705-4 | Ada | — | Ada | Ada | Ada | — |
| HD1500 | Ada | — | Ada | — | Ada | — |

### Differential

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| HD785-7 | Ada | Ada | — | Ada | — | — |
| HD785-5 | Ada | — | — | — | Ada | — |
| WA800-3 | Ada | — | Ada | Ada | Ada | — |
| HD465-7R | — | — | — | Ada | — | — |

### Axle

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| HD1500 | Ada | Ada | Ada | Ada | Ada | — |
| HD465-7R | Ada | — | — | Ada | — | — |
| HD785-5 | Ada | — | — | Ada | Ada | — |
| HD785-7 | — | — | — | Ada | — | — |

### PTO

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| PC1250-8 | Ada | Ada | Ada | — | Ada | — |
| PC2000-8 | Ada | — | Ada | — | — | — |

### Swing Machinery

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| PC1250-8 | Ada | — | — | — | Ada | — |
| PC2000-8 | Ada | — | Ada | — | Ada | — |

### Pump

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| Gear Pump HD785-7 | Ada | — | — | — | Ada | — |

### Control Valve (unit lama, bukan paket COMPLETED 2026)

| EGI | Recv | Dis | Insp | Assy | Deliv | Test |
|-----|:----:|:---:|:----:|:----:|:-----:|:----:|
| HD785-5 | — | Ada | — | — | — | — |

---

## 3. Cara baca cepat

```
SUDAH SIAP
  Control Valve + Hoist + Suspension HD785
  → tinggal kurang Test (kecuali PC1250 & PC2000)

BELUM SIAP
  Final Drive / Transmission / Diff / Axle / PTO / Swing / Pump
  → SIAP = belum ada
  → Ada = bahan ada, tinggal dirapikan
  → — = file tahap itu belum ketemu
```

---

## 4. Saran urutan lanjut

1. **Final Drive** — paling banyak `Ada`
2. **TC / Transmission**
3. **PTO & Swing**
4. Diff / Axle / Pump
