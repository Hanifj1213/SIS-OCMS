# Checklist Deployment Production — SISI-OCMS

Panduan menyiapkan aplikasi ini untuk dipakai beneran di perusahaan.
Kebutuhan: PHP 8.3+, Composer, Node.js (untuk build asset), MySQL/MariaDB 8+ (disarankan).

---

## 1. Server & web server

- [ ] **Jangan pakai `php artisan serve`** di production (single-threaded, gampang macet).
- [ ] Pakai salah satu:
  - **Linux (disarankan):** Nginx + PHP-FPM. Document root diarahkan ke folder `public/`.
  - **Windows Server:** IIS + PHP FastCGI, atau Laragon full version.
- [ ] Pastikan hanya folder `public/` yang bisa diakses dari luar (jangan expose root project).
- [ ] Pasang HTTPS (Let's Encrypt gratis) kalau diakses lewat jaringan kantor/internet.

## 2. Database: pindah dari SQLite ke MySQL

SQLite mengunci file saat write — kalau beberapa mekanik submit bersamaan bisa error `database is locked`.

- [ ] Buat database + user MySQL:

```sql
CREATE DATABASE sisi_ocms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ocms'@'localhost' IDENTIFIED BY 'password-kuat-disini';
GRANT ALL PRIVILEGES ON sisi_ocms.* TO 'ocms'@'localhost';
```

- [ ] Ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisi_ocms
DB_USERNAME=ocms
DB_PASSWORD=password-kuat-disini
```

- [ ] Jalankan `php artisan migrate --force` lalu seed user awal (atau migrasikan data dari SQLite — lihat bagian 8).

## 3. Konfigurasi `.env` production

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`  ← **wajib**, kalau `true` stack trace + isi config bocor ke user saat error
- [ ] `APP_URL=https://ocms.perusahaan.co.id` (URL asli)
- [ ] `APP_NAME="SISI-OCMS"`
- [ ] `LOG_LEVEL=warning` (jangan `debug`)
- [ ] `GSHEET_COPY_WEBAPP_URL=...` (sama seperti sekarang)
- [ ] Disarankan: isi `GSHEET_COPY_SECRET` dan set variabel `SECRET` yang sama di Apps Script
      (`tools/gsheet_copy_webapp.gs`) supaya web app duplikasi sheet tidak bisa dipakai orang luar.
- [ ] Generate key baru untuk production: `php artisan key:generate`
      (jangan pakai APP_KEY dari repo/development).

## 4. Langkah deploy (tiap rilis)

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link   # sekali saja di awal
```

Kalau ada perubahan `.env` atau config, ulangi `php artisan config:cache`.

## 5. Queue worker

`QUEUE_CONNECTION=database` — job (kalau ada) butuh worker yang jalan terus:

- [ ] Linux: buat service systemd / Supervisor untuk `php artisan queue:work --tries=3`.
- [ ] Windows: NSSM atau Task Scheduler yang menjalankan perintah yang sama.
- [ ] Alternatif paling simpel kalau tidak ada job berat: ganti `QUEUE_CONNECTION=sync`.

## 6. Yang TIDAK ikut di-deploy

- [ ] Folder `CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/` — ratusan MB Excel bahan mentah.
      Source of truth sudah di Google Sheets. Kalau deploy via git, folder ini memang ada di repo;
      hapus di server atau deploy pakai `git archive` / rsync exclude.
- [ ] `database/database.sqlite` — data development, jangan tertimpa/terbawa.
- [ ] `.env` development — buat `.env` baru di server, jangan copy yang lokal.

## 7. Keamanan minimum

- [ ] Semua user default/testing dihapus, password admin diganti.
- [ ] `APP_DEBUG=false` (sekali lagi — ini kebocoran paling umum).
- [ ] Backup database otomatis harian (mysqldump + task scheduler/cron), simpan minimal 7 hari.
- [ ] File `public/qrcodes/` ikut di-backup (atau regenerate ulang bisa juga).
- [ ] Google Apps Script: pakai secret (lihat bagian 3) + akses spreadsheet template dibatasi
      ke akun Google perusahaan, bukan "anyone with the link" kalau memungkinkan.

## 8. Migrasi data dari SQLite (opsional)

Kalau data testing di SQLite mau dibawa ke MySQL:

```bash
# export-import paling gampang via artisan tinker atau package seperti
# "sqlite3 database/database.sqlite .dump" lalu konversi, ATAU
# mulai bersih di MySQL dan daftarkan ulang komponen (disarankan kalau datanya masih sedikit)
```

Mulai bersih lebih aman — komponen yang didaftarkan ulang otomatis dapat salinan
GSheet baru (disassembly, measurement, sub assy).

## 9. Cek setelah deploy

- [ ] Login, buka dashboard — metrik muncul dan auto-refresh.
- [ ] Daftarkan 1 komponen Engine test → cek 4 URL GSheet terisi (disassembly, measurement,
      subassy disassembly, subassy measurement) — bisa lewat halaman detail stage 2.
- [ ] Toggle Mainline | Sub Assy berfungsi di panel Disassembly dan Measurement.
- [ ] Scan QR code dari HP → halaman komponen kebuka.
- [ ] Submit part request dari role Mechanic → muncul di Warehouse tanpa refresh.
- [ ] Matikan `APP_DEBUG` terbukti: buka URL ngawur → error page biasa, bukan stack trace.

---

**Ringkasan prioritas:** MySQL + Nginx/PHP-FPM + `APP_DEBUG=false` + cache config/route/view.
Sisanya menyusul tanpa risiko.
