# Tutorial — Mengamankan Apps Script Google Sheets (GSHEET_COPY_SECRET)

Panduan ini untuk mengaktifkan kembali integrasi Google Sheets setelah
pengamanan **fail-closed**. Ikuti berurutan; sekitar 15 menit.

---

## Kenapa harus dilakukan

Sebelumnya Apps Script memakai `var SECRET = ''` dan pengecekannya:

```javascript
if (SECRET && body.secret !== SECRET) { tolak }
```

Karena `SECRET` kosong, syarat itu **tidak pernah aktif** — artinya siapa pun
yang mengetahui URL `/exec` dapat memanggil endpoint, termasuk aksi
`restore_from_xlsx` yang **menimpa seluruh isi master spreadsheet**.

Sekarang kebalikannya: **tanpa secret, semua ditolak**. Fitur GSheet
(duplikasi template, scan FR/PR) mati sampai langkah di bawah selesai.

---

## Langkah 1 — Buat nilai secret

Jalankan di PowerShell:

```powershell
php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
```

Hasilnya 48 karakter acak, contoh bentuknya:

```
a3f9c1... (48 karakter)
```

Simpan sementara di tempat aman (password manager / catatan yang segera
dihapus). **Jangan** ketik nilai ini ke chat, commit, atau kirim lewat
WhatsApp/email biasa.

> **Secret bocor?** Bila nilai secret sempat tampil di terminal yang dibagikan,
> di-screenshot, atau terkirim ke chat, anggap sudah bocor. Ulangi Langkah 1
> untuk membuat nilai baru, lalu ikuti bagian **Rotasi secret** di bawah.
> Rotasi hanya butuh 3 menit — jauh lebih murah daripada master template
> ditimpa orang lain.

---

## Langkah 2 — Pasang secret di Apps Script

1. Buka project Apps Script (yang berisi `Code.gs`).
2. Ikon gerigi kiri → **Project Settings**.
3. Scroll ke **Script Properties** → **Add script property**.
4. Isi:

   | Property | Value |
   |----------|-------|
   | `OCMS_SECRET` | nilai dari Langkah 1 |

5. **Save script properties**.

> Secret disimpan di Script Properties, bukan di dalam kode. Jadi file
> `Code.gs` boleh ikut ter-commit tanpa membocorkan apa pun.

---

## Langkah 3 — Update kode Apps Script

1. Buka `tools/gsheet_copy_webapp.gs` di project ini.
2. Salin **seluruh isinya**.
3. Di Apps Script, buka `Code.gs` → pilih semua (Ctrl+A) → tempel (Ctrl+V).
4. Simpan (Ctrl+S).

---

## Langkah 4 — Deploy versi baru

Ini wajib. Tanpa deploy, URL `/exec` masih menjalankan kode lama.

1. Tombol **Deploy** (kanan atas) → **Manage deployments**.
2. Klik ikon pensil (Edit) pada deployment yang aktif.
3. **Version** → pilih **New version**.
4. Pastikan:
   - Execute as: **Me**
   - Who has access: **Anyone**
5. **Deploy**.

URL `/exec` tidak berubah, jadi `.env` tidak perlu diubah untuk bagian URL.

---

## Langkah 5 — Isi secret di .env

Buka `.env` (bukan `.env.example`), isi baris ini dengan nilai **yang sama
persis** dengan Langkah 1:

```dotenv
GSHEET_COPY_SECRET=
```

Pastikan tidak ada spasi sebelum/sesudah tanda `=` dan tidak ada tanda kutip.

Lalu bersihkan cache konfigurasi:

```powershell
php artisan config:clear
```

Di produksi, gunakan:

```powershell
php artisan config:cache
```

---

## Langkah 6 — Verifikasi

Cek apakah aplikasi bisa berbicara dengan Apps Script:

```powershell
php tools/ping_gsheet_webapp.php
```

Hasil yang benar berisi `"ok": true`.

Kalau gagal, lihat tabel Troubleshooting di bawah.

Verifikasi akhir lewat UI:

1. Buka komponen dengan Stage ≥ 2.
2. Klik **Scan Spreadsheet**.
3. Daftar kandidat FR/PR harus muncul (bukan pesan error konfigurasi).

---

## Operasi maintenance template (aksi admin)

Tiga tools berikut mengubah/menimpa **master template**, bukan salinan
komponen — karena itu default-nya **dimatikan**:

| Tool | Aksi | Efek |
|------|------|------|
| `tools/format_master_gsheets.php` | `apply_decision_boxes` | merge + checkbox kolom keputusan |
| `tools/apply_master_checkboxes.php` | `apply_checkboxes` | pasang checkbox |
| `tools/restore_master_gsheets.php` | `restore_from_xlsx` | **menimpa seluruh isi master** |

Kalau dijalankan sekarang, Apps Script menjawab:

```
action administratif dinonaktifkan pada deployment ini
```

Itu perilaku yang benar. Untuk menjalankannya, pilih salah satu cara berikut.

### Cara A — Aktifkan sementara (disarankan)

Untuk pemakaian sesekali saat setup template.

**Sebelum maintenance:**

1. Apps Script → Project Settings → Script Properties → Add:

   | Property | Value |
   |----------|-------|
   | `OCMS_ADMIN_ACTIONS` | `enabled` |

2. Save.

**Jalankan tools** (selalu `--dry-run` dulu):

```powershell
php tools/format_master_gsheets.php --dry-run
php tools/format_master_gsheets.php --apply
```

**SESUDAH SELESAI — jangan dilewat:**

1. Kembali ke Script Properties.
2. **Hapus** property `OCMS_ADMIN_ACTIONS` (atau ubah nilainya jadi kosong).
3. Save.

> Selama `OCMS_ADMIN_ACTIONS = enabled`, endpoint publik Anda menerima
> perintah yang dapat menimpa master template. Jangan tinggalkan menyala.
> Tidak perlu Deploy ulang — Script Property dibaca setiap permintaan.

### Cara B — Dua deployment terpisah

Untuk yang mau aksi admin tidak pernah menempel pada URL produksi.

1. Buat project Apps Script **kedua**, tempel `gsheet_copy_webapp.gs` yang sama.
2. Script Properties project kedua:

   | Property | Value |
   |----------|-------|
   | `OCMS_SECRET` | secret **berbeda** dari produksi |
   | `OCMS_ADMIN_ACTIONS` | `enabled` |

3. Deploy → Who has access: **Only myself** (bukan Anyone).
4. Saat menjalankan tools maintenance, sementara arahkan `.env` ke URL dan
   secret deployment admin ini, lalu kembalikan ke nilai produksi setelah selesai.

Kelebihan: URL produksi **tidak pernah** menerima aksi admin.
Kekurangan: harus menukar `.env` bolak-balik.

---

## Rotasi secret

Lakukan bila secret bocor, ada pergantian personel, atau minimal 1× setahun.

1. Buat nilai baru (Langkah 1).
2. Apps Script → Script Properties → ubah `OCMS_SECRET` → Save.
3. `.env` → ubah `GSHEET_COPY_SECRET` ke nilai yang sama.
4. `php artisan config:cache`
5. Verifikasi: `php tools/ping_gsheet_webapp.php`

Ada jeda beberapa detik antara langkah 2 dan 4 di mana permintaan akan
ditolak. Lakukan di luar jam sibuk.

**Bila URL `/exec` yang bocor** (bukan hanya secret): buat deployment baru
(Deploy → New deployment), lalu perbarui `GSHEET_COPY_WEBAPP_URL` di `.env`.

---

## Troubleshooting

| Pesan | Penyebab | Solusi |
|-------|----------|--------|
| `GSHEET_COPY_SECRET belum dikonfigurasi` | `.env` belum diisi / masih ter-cache | Langkah 5, lalu `php artisan config:clear` |
| `unauthorized` | Nilai `.env` ≠ `OCMS_SECRET`, atau belum Deploy New version | Samakan nilai; ulangi Langkah 4 |
| `action administratif dinonaktifkan` | Menjalankan tools maintenance | Lihat bagian Operasi maintenance |
| `action tidak dikenal` | Salah nama action | Hanya `copy`, `upload`, `read`, `ping` yang boleh dari aplikasi |
| `Spreadsheet tidak terdaftar pada OCMS` | ID di luar mapping OCMS | Spreadsheet harus salinan komponen atau template terdaftar |
| `GSHEET_COPY_WEBAPP_URL belum dikonfigurasi` | URL `/exec` kosong | Isi di `.env` |

Kegagalan koneksi tercatat di `storage/logs/laravel.log` dengan
`correlation_id`. Log **tidak pernah** memuat secret atau isi spreadsheet.

---

## Aturan yang tidak boleh dilanggar

1. Jangan menaruh secret di `Code.gs`, `.env.example`, dokumentasi, atau commit.
2. Jangan menyamakan secret produksi dengan secret deployment admin.
3. Jangan meninggalkan `OCMS_ADMIN_ACTIONS = enabled` setelah maintenance.
4. Jangan menjalankan tools maintenance tanpa `--dry-run` lebih dulu.
5. Jangan mematikan validasi hanya supaya fitur jalan — kalau ada yang
   tertolak, cari sebabnya di tabel Troubleshooting.

---

## Ringkasan cepat

```
Langkah 1  php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
Langkah 2  Apps Script → Project Settings → Script Properties → OCMS_SECRET
Langkah 3  Salin tools/gsheet_copy_webapp.gs → Code.gs
Langkah 4  Deploy → Manage deployments → Edit → New version → Deploy
Langkah 5  .env → GSHEET_COPY_SECRET=... → php artisan config:clear
Langkah 6  php tools/ping_gsheet_webapp.php  → harus "ok": true
```
