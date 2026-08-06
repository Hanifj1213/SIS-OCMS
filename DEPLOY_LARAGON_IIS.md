# Tutorial Deploy SISI-OCMS di Windows — Laragon & IIS

Panduan langkah demi langkah men-deploy aplikasi ini di server/PC Windows
agar bisa diakses banyak user lewat jaringan (LAN/intranet).

Checklist umum production (APP_DEBUG, backup, keamanan) ada di `DEPLOYMENT.md` —
file ini fokus ke **cara menjalankannya di Laragon atau IIS**.

---

## 0. Prasyarat (dua-duanya)

| Kebutuhan | Keterangan |
|---|---|
| PHP 8.3+ | Ekstensi: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `fileinfo` |
| Composer | https://getcomposer.org |
| Node.js LTS | Hanya untuk build asset (`npm run build`), tidak perlu jalan terus |
| MySQL/MariaDB | Disarankan untuk multi-user (SQLite mengunci file saat write) |
| Koneksi internet dari server | Wajib — aplikasi memanggil Google Apps Script untuk duplikasi checksheet GSheet |

Yang **TIDAK** perlu dibawa ke server:

- Folder `CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/`, `002. CHECKSHEET KOMPONEN/`, `FABRIKASI/`
  (bahan mentah; source of truth checksheet sudah di Google Sheets)
- `database/database.sqlite` milik development
- `.env` development — buat baru di server

---

## 1. Siapkan kode & dependensi (sama untuk Laragon/IIS)

```powershell
git clone https://github.com/MuhammadRezqyRobiansyah/SISI-OCMS.git C:\www\sisi-ocms
cd C:\www\sisi-ocms

composer install --no-dev --optimize-autoloader
npm ci
npm run build

copy .env.example .env
php artisan key:generate
```

Edit `.env` minimal:

```env
APP_NAME="SISI-OCMS"
APP_ENV=production
APP_DEBUG=false            # WAJIB false di production
APP_URL=http://10.0.0.5    # IP/hostname server yang diakses user

# --- Database (pilih salah satu) ---
# MySQL (disarankan multi-user):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisi_ocms
DB_USERNAME=ocms
DB_PASSWORD=password-kuat

# --- Sesi & antrian ---
SESSION_DRIVER=file
QUEUE_CONNECTION=database  # queue worker WAJIB jalan (lihat bagian 4)

# --- Integrasi Google Sheets (WAJIB, salin dari development) ---
GSHEET_COPY_WEBAPP_URL=https://script.google.com/macros/s/....../exec
GSHEET_COPY_SECRET=isi-secret-yang-sama-dengan-apps-script
```

Lalu:

```powershell
php artisan migrate --force
php artisan db:seed --class=ChecksheetTemplateSeeder --force
php artisan db:seed --class=DeliveryChecksheetTemplateSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Buat user pertama (SuperAdmin) lewat seeder user yang ada, atau login dengan
> akun yang sudah dimigrasikan. Registrasi publik sengaja dimatikan.

---

## 2A. Opsi 1 — Laragon (paling cepat, cocok untuk pilot/LAN kecil)

1. Install **Laragon Full** (sudah bawa Apache, MySQL, PHP): https://laragon.org
2. Taruh project di `C:\laragon\www\sisi-ocms` (atau symlink dari folder lain).
3. Laragon otomatis membuat virtual host `http://sisi-ocms.test`.
   Untuk akses dari komputer lain:
   - Menu **Preferences → General** → centang *Don't use hostname*, atau
   - Apache → `sites-enabled` → arahkan `ServerName` ke IP server, dan pastikan
     **DocumentRoot menunjuk ke folder `public/`**, contoh:

     ```apache
     <VirtualHost *:80>
         DocumentRoot "C:/laragon/www/sisi-ocms/public"
         ServerName 10.0.0.5
         <Directory "C:/laragon/www/sisi-ocms/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
4. Start **Apache + MySQL** dari jendela Laragon.
5. Buka firewall Windows untuk port 80:

   ```powershell
   netsh advfirewall firewall add rule name="SISI-OCMS HTTP" dir=in action=allow protocol=TCP localport=80
   ```
6. Tes dari HP/komputer lain: `http://10.0.0.5`

> **Jangan** memakai `php artisan serve` untuk dipakai ramai-ramai —
> single-threaded, satu request GSheet yang lambat memblokir semua user.

---

## 2B. Opsi 2 — IIS (untuk server kantor resmi)

1. **Aktifkan IIS + CGI**: Server Manager → Add Roles → Web Server (IIS) →
   centang **CGI** (ini yang menyediakan FastCGI).
2. **Install PHP (Non-Thread-Safe)**: unduh zip NTS dari https://windows.php.net,
   ekstrak ke `C:\PHP`, salin `php.ini-production` → `php.ini`, aktifkan ekstensi
   yang dibutuhkan (bagian 0), set `extension_dir="C:\PHP\ext"`.
3. **Daftarkan PHP ke IIS**: IIS Manager → server → *Handler Mappings* →
   *Add Module Mapping*:
   - Request path: `*.php`
   - Module: `FastCgiModule`
   - Executable: `C:\PHP\php-cgi.exe`
4. **Install URL Rewrite Module**: https://www.iis.net/downloads/microsoft/url-rewrite
5. **Buat site**: IIS Manager → *Add Website*:
   - Physical path: `C:\www\sisi-ocms\public`  ← **harus folder `public`**, bukan root project
   - Binding: port 80 (atau 443 + sertifikat)
6. **web.config** di folder `public/` (pengganti `.htaccess`):

   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <configuration>
     <system.webServer>
       <rewrite>
         <rules>
           <rule name="Laravel" stopProcessing="true">
             <match url="^(.*)$" />
             <conditions logicalGrouping="MatchAll">
               <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
               <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
             </conditions>
             <action type="Rewrite" url="index.php" />
           </rule>
         </rules>
       </rewrite>
     </system.webServer>
   </configuration>
   ```
7. **Izin folder**: beri user `IIS_IUSRS` hak *Modify* pada folder
   `storage\` dan `bootstrap\cache\`:

   ```powershell
   icacls "C:\www\sisi-ocms\storage" /grant "IIS_IUSRS:(OI)(CI)M"
   icacls "C:\www\sisi-ocms\bootstrap\cache" /grant "IIS_IUSRS:(OI)(CI)M"
   ```
8. Naikkan batas upload di `php.ini` (foto painting & dokumen MOL):
   `upload_max_filesize=20M`, `post_max_size=25M`, `max_execution_time=180`.
9. Restart IIS: `iisreset`, lalu tes dari browser.

---

## 3. Apakah queue worker wajib? **YA.**

Job `DuplicateChecksheetGsheets` (menyalin template GSheet per komponen baru)
berjalan lewat antrian database. **Tanpa worker, komponen baru tidak pernah
mendapat checksheet GSheet** — halaman stage 2/4/5 akan jatuh ke tampilan
fallback dan terlihat "fiturnya hilang".

Perintahnya:

```powershell
php artisan queue:work --tries=3 --timeout=300
```

### Cara membuatnya jalan terus di Windows (pilih salah satu)

**A. NSSM (disarankan — jadi Windows Service, auto-start & auto-restart):**

```powershell
# unduh https://nssm.cc, lalu:
nssm install SISI-OCMS-Worker "C:\PHP\php.exe" "artisan queue:work --tries=3 --timeout=300"
nssm set SISI-OCMS-Worker AppDirectory "C:\www\sisi-ocms"
nssm set SISI-OCMS-Worker AppStdout "C:\www\sisi-ocms\storage\logs\worker.log"
nssm set SISI-OCMS-Worker AppStderr "C:\www\sisi-ocms\storage\logs\worker.log"
nssm start SISI-OCMS-Worker
```

**B. Task Scheduler:**

- *Create Task* → Trigger: **At startup** → Action:
  Program `C:\PHP\php.exe`, arguments `artisan queue:work --tries=3 --timeout=300`,
  *Start in* `C:\www\sisi-ocms`
- Settings: centang *If the task fails, restart every 1 minute*.

**Catatan penting:** setiap selesai `git pull`/update kode, restart worker
(`nssm restart SISI-OCMS-Worker`) supaya memuat kode terbaru, lalu jalankan
ulang `php artisan config:cache`.

---

## 4. Verifikasi setelah deploy (5 menit)

1. Login sebagai SuperAdmin, buka Dashboard — metrik tampil.
2. Daftarkan 1 komponen Engine (mis. EGI PC2000-8) → tunggu ±30-60 detik →
   buka detailnya: checksheet Disassembly & Measurement (GSheet) harus muncul.
   Kalau tidak muncul → worker tidak jalan (cek bagian 3).
3. Tambah nama di panel Crew → Man Hour mulai berjalan.
4. Cetak PDF Berita Acara dari satu komponen — DomPDF berfungsi.
5. Akses dari HP di jaringan yang sama — layout mobile aktif.

## 5. Update rilis berikutnya

```powershell
cd C:\www\sisi-ocms
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
nssm restart SISI-OCMS-Worker   # atau restart task scheduler worker
```
