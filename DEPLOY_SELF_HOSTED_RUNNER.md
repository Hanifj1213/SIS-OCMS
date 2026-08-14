# Deploy SISI-OCMS mirip laptop sekarang + GitHub Actions Self-Hosted Runner

Panduan untuk magang / deploy internal **tanpa HTTPS dulu** (HTTP di LAN, seperti Laragon sekarang),
plus cara supaya **push ke GitHub otomatis meng-update server**.

Checklist keamanan umum tetap di `DEPLOYMENT.md`. Setup Laragon/IIS detail di `DEPLOY_LARAGON_IIS.md`.

---

## 0. Gambaran alur

```
Laptop kamu (online)  →  git push GitHub  →  Runner di PC/server kantor  →  git pull + deploy
                              ↑                        ↓
                         (internet)           Apache Laragon tetap melayani user LAN
```

- User kantor tetap akses `http://IP-SERVER` (HTTP OK selama belum ada sertifikat IT).
- Runner **tidak** membuka port ke internet — dia yang menelepon keluar ke GitHub (HTTPS outbound).
- `.env`, database, dan file upload (`storage/`) **tidak** ikut git; hidup hanya di server.

---

## 1. Deploy “mirip keadaan sekarang” (HTTP + Laragon)

### 1.1 Yang sama dengan laptop sekarang

| Item | Nilai tipikal |
|------|----------------|
| Web server | Laragon Apache |
| Akses user | `http://172.x.x.x` (IP LAN) |
| HTTPS | Belum — tunggu IT |
| Database | MySQL lokal Laragon |
| Queue | `php artisan queue:work` (idealnya jadi Windows Service / Task Scheduler) |

### 1.2 `.env` production (meski masih HTTP)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://172.28.102.49          # ganti ke IP/hostname server nyata
LOG_LEVEL=warning

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=false           # true HANYA setelah HTTPS hidup

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sisi_ocms
DB_USERNAME=ocms                      # jangan root tanpa password di production
DB_PASSWORD=password-kuat

QUEUE_CONNECTION=database
GSHEET_COPY_WEBAPP_URL=...
GSHEET_COPY_SECRET=...                # wajib isi
```

Setelah edit `.env`:

```powershell
cd C:\laragon\www\sisi-ocms
php artisan config:clear
php artisan config:cache
php artisan view:clear
php artisan storage:link
```

### 1.3 Firewall Windows (LAN only, tanpa HTTPS)

Tujuan: user kantor bisa buka halaman OCMS; dunia luar / port berbahaya **tidak** terbuka.

**Boleh dibuka (inbound):**

| Port | Siapa | Catatan |
|------|--------|---------|
| **80** (HTTP) | Hanya subnet kantor, mis. `172.28.0.0/16` atau `10.0.0.0/8` | Apache Laragon |
| **443** | Nanti, setelah HTTPS | Belum perlu sekarang |

**Jangan dibuka ke LAN/internet (atau tutup kalau sudah terbuka):**

| Port | Alasan |
|------|--------|
| **3306 / 33060** | MySQL — aplikasi connect via `127.0.0.1` saja |
| **3389** | RDP — batasi ke IT saja bila perlu |
| **22** | SSH — biasanya tidak ada di Windows |
| **5173 / 8000** | Vite / `artisan serve` — jangan production |

**Cara cepat (PowerShell Admin) — contoh izinkan HTTP hanya dari subnet kantor:**

```powershell
# Hapus rule "Allow any" untuk Apache/PHP yang terlalu longgar (cek dulu di Windows Defender Firewall)
# Buat rule baru: port 80 hanya dari subnet kantor (sesuaikan CIDR!)
New-NetFirewallRule -DisplayName "OCMS HTTP LAN only" `
  -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80 `
  -RemoteAddress 172.28.0.0/16

# Blok MySQL dari jaringan (kalau rule Allow 3306 masih ada)
Disable-NetFirewallRule -DisplayName "*MySQL*" -ErrorAction SilentlyContinue
# Atau buat blok eksplisit:
New-NetFirewallRule -DisplayName "Block MySQL from LAN" `
  -Direction Inbound -Action Block -Protocol TCP -LocalPort 3306,33060
```

**Cek rule yang ada:**

```powershell
Get-NetFirewallRule | Where-Object { $_.Enabled -and $_.Direction -eq 'Inbound' } |
  Get-NetFirewallPortFilter | Where-Object { $_.LocalPort -in 80,443,3306 } |
  Format-Table
```

Catatan: runner GitHub **tidak butuh** inbound firewall baru — cukup PC server bisa keluar ke `https://github.com`.

### 1.4 Queue worker (supaya GSheet tidak tergantung terminal)

Tanpa terminal terbuka terus, pakai Task Scheduler atau NSSM:

```powershell
# Contoh Task Scheduler (jalan tiap login / on startup):
# Program: C:\laragon\bin\php\php-8.x\php.exe
# Arguments: artisan queue:work --tries=3 --sleep=3
# Start in: C:\laragon\www\sisi-ocms
```

---

## 2. Persiapan GitHub (sekali)

1. Pastikan kode sudah di repo **private** (disarankan):  
   `https://github.com/<org-atau-user>/SISI-OCMS`
2. Di server: clone sekali ke folder yang dilayani Apache, mis. `C:\laragon\www\sisi-ocms`
3. Buat `.env` production di server (lihat §1.2) — **jangan pernah di-commit**
4. Di GitHub → **Settings → Secrets and variables → Actions**, tambahkan bila perlu:
   - Tidak wajib untuk deploy sederhana (runner jalan di mesin yang sama dengan app)
   - Jangan taruh password DB di workflow; biarkan di `.env` lokal server

---

## 3. Pasang Self-Hosted Runner di PC/server kantor

### 3.1 Buat runner di GitHub

1. Buka repo → **Settings → Actions → Runners → New self-hosted runner**
2. Pilih **Windows** + arsitektur **x64**
3. Ikuti perintah yang ditampilkan GitHub (kurang lebih contoh di bawah — **pakai token dari halaman itu**, jangan copy token orang lain)

```powershell
mkdir C:\actions-runner
cd C:\actions-runner

# Download runner (versi bisa berbeda — ikuti perintah di UI GitHub)
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/v2.321.0/actions-runner-win-x64-2.321.0.zip -OutFile actions-runner-win-x64.zip
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory("$PWD\actions-runner-win-x64.zip", "$PWD")

.\config.cmd --url https://github.com/<ORG>/<REPO> --token <TOKEN_DARI_GITHUB>
```

Saat konfigurasi:

| Prompt | Saran |
|--------|--------|
| Runner group | Default |
| Runner name | `ocms-laragon` (bebas) |
| Labels | tekan Enter (default `self-hosted`, `Windows`, `X64`) **atau** tambah label `ocms` |
| Work folder | `_work` (default) |

### 3.2 Jalankan sebagai Windows Service (penting)

Jangan biarkan runner hanya di jendela PowerShell:

```powershell
cd C:\actions-runner
.\svc.cmd install
.\svc.cmd start
```

Cek di GitHub → Runners: status harus **Idle** (hijau).

### 3.3 Keamanan runner

- Runner ini punya akses ke disk server — **hanya** install di mesin trusted, repo **private**.
- Jangan expose folder `C:\actions-runner` ke Apache/public.
- Token konfigurasi sekali pakai; setelah `config.cmd` selesai, token UI tidak dipakai lagi.
- User Windows yang menjalankan service harus bisa: `git pull`, `php`, `composer` (PATH Laragon).

Tambahkan PATH permanen (System Environment) agar service menemukan PHP:

```
C:\laragon\bin\php\php-8.4.24-Win32-vs17-x64
C:\laragon\bin\composer
C:\Program Files\Git\cmd
```

(sesuaikan versi PHP Laragon-mu)

---

## 4. Workflow deploy (sudah disiapkan di repo)

File: `.github/workflows/deploy.yml`  
Skrip: `tools/deploy.ps1`

Alur saat push ke `main` / `master`:

1. Checkout kode di runner
2. Sync ke folder aplikasi (`C:\laragon\www\sisi-ocms` — bisa diubah lewat env)
3. `composer install --no-dev`
4. `npm ci && npm run build` (kalau `package.json` ada)
5. `php artisan migrate --force`
6. `php artisan config:cache` + `route:cache` + `view:cache`

### 4.1 Aktifkan / sesuaikan path

Di `.github/workflows/deploy.yml`, pastikan:

```yaml
runs-on: self-hosted
# atau: runs-on: [self-hosted, Windows, X64]
```

Dan di job, env folder deploy:

```yaml
env:
  OCMS_DEPLOY_PATH: C:\laragon\www\sisi-ocms
```

Ubah path itu kalau folder server berbeda.

### 4.2 Uji deploy

```powershell
# Dari laptop
git add .
git commit -m "chore: enable self-hosted deploy"
git push origin main
```

Lalu buka GitHub → **Actions** → workflow **Deploy to OCMS server**.  
Harus jalan di runner `ocms-laragon`, bukan `ubuntu-latest`.

### 4.3 Manual deploy (kalau Actions gagal)

Di server:

```powershell
cd C:\laragon\www\sisi-ocms
powershell -ExecutionPolicy Bypass -File tools\deploy.ps1
```

---

## 5. Branch strategy singkat untuk magang

| Branch | Fungsi |
|--------|--------|
| `main` / `master` | Deploy otomatis ke server kantor |
| `develop` atau fitur | Kerja harian; merge ke `main` kalau sudah siap dipakai user |

Workflow `tests.yml` tetap jalan di GitHub-hosted (ubuntu) — bagus sebagai gerbang.  
Deploy self-hosted hanya trigger dari `main` supaya eksperimen tidak merusak server.

---

## 6. Checklist sebelum bilang “siap dipakai kantor”

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] MySQL user bukan `root` kosong
- [ ] Firewall: port 80 hanya LAN; 3306 tidak dari LAN
- [ ] Queue worker auto-start
- [ ] Runner service Idle di GitHub
- [ ] Satu kali `git push` berhasil menjalankan Deploy (hijau)
- [ ] User lain di Wi‑Fi kantor bisa buka `http://IP-SERVER/login`
- [ ] Backup: database + `storage\app\public` + `public\qrcodes`

---

## 7. Nanti kalau HTTPS sudah siap (IT)

1. Pasang sertifikat internal / reverse proxy
2. Ganti `APP_URL=https://ocms....`
3. Set `SESSION_SECURE_COOKIE=true`
4. Firewall: buka **443** (LAN), boleh tutup 80 atau redirect ke HTTPS
5. Tidak perlu ubah logika runner — deploy tetap sama

---

## 8. Troubleshooting

| Gejala | Cek |
|--------|-----|
| Actions stuck “Waiting for a runner” | `.\svc.cmd status` di `C:\actions-runner`; runner online di GitHub Settings |
| Deploy gagal `php` not found | PATH service Windows ≠ PATH user — set System PATH + restart service |
| Halaman putih setelah deploy | `storage/logs/laravel.log`; permission `storage` & `bootstrap/cache` |
| User lain tidak bisa buka IP | Firewall port 80; Laragon Start All; `APP_URL` / IP DHCP berubah |
| GSheet tidak terduplikasi | Queue worker tidak jalan; `GSHEET_COPY_*` di `.env` |

---

*Dibuat: 2026-08-11 — untuk deploy HTTP + Laragon + self-hosted runner tanpa menunggu HTTPS IT.*
