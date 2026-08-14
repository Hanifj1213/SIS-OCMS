# PRODUCTION_READINESS.md — Ledger Hardening SISI-OCMS

> Ledger untuk `SUPERPROMPT_PRODUCTION_READY.md`. Diperbarui setiap selesai satu
> kelompok perubahan.

**Status:** CODE-READY — P1 (kecuali runner SKIP) + P2 selesai di kode; quality
gate sebagian; operasi produksi = script + dokumentasi (verifikasi IT belum).

---

## Acceptance gate

| Gate | Status | Bukti |
|------|--------|-------|
| PHPUnit (final) | PASS | 160 test, 150 pass, 10 skip, 1049 assert |
| 10 skip | DIKETAHUI | `LocalChecksheetTest` — template `.xlsx` SIAP tidak ada di mesin CI/dev |
| `php artisan migrate --force` | PASS | termasuk `fr_number_sequences` |
| `php artisan ocms:production-check` | PASS (dev) | 1 error expected: MySQL root tanpa password |
| `composer audit --locked` | ADVISORY | dompdf, guzzle, commonmark — perlu `composer update` terpisah |
| `npm audit` | BELUM dijalankan sesi ini | ditambahkan ke CI (`|| true` sementara) |
| Pint (file baru) | PASS | file P2.8–P2.12 diformat |
| PHPStan level 7 | BELUM dijalankan sesi ini | config ada di `phpstan.neon` |

---

## P1 — blocker keamanan (ringkas)

| # | Item | Status |
|---|------|--------|
| 1 | Transisi tahap atomik | **DONE** |
| 2 | Stored XSS checksheet | **DONE** |
| 3 | Apps Script fail-closed | **DONE** |
| 4 | Self-hosted runner | **SKIP** (keputusan user) |
| 5 | Production check + `/up` | **DONE** |

Detail lengkap P1.1–P1.5 tetap di commit history / sesi sebelumnya.

---

## P2 — kebenaran bisnis, ownership, audit

### 6–8 — DONE (sesi sebelumnya)

Checksheet integrity, FR numbering, FR attachment ownership.

### 9. Validasi spreadsheet checksheet lokal — **DONE** (2026-08-15)

- `LocalChecksheetIntegrityService` — tahap aktif, approval, layout/sheet/cell valid, nilai `1`/kosong
- `LocalChecksheetController` — enforcement server-side + UI `editable` konsisten
- Test: `LocalChecksheetIntegrityTest` (4 test, tanpa fixture xlsx)

### 10. Crew aktif / tahap historis — **DONE** (2026-08-15)

- Penutupan crew saat transisi: P1.1 (`StageTransitionService`)
- `StageCrewIntegrityService` + `StageTimeController` — tolak ubah crew historis & saat approval
- Test: `StageCrewIntegrityTest` (2 test)

### 11. Seeder akun default — **DONE** (2026-08-15)

- `RoleSeeder` — hanya role
- `DemoUserSeeder` — hanya `local`/`testing` atau `OCMS_SEED_DEMO_USERS=true`; tidak reset password user existing
- `ocms:bootstrap-superadmin` — SuperAdmin produksi interaktif, password kuat, tidak dicetak
- Test: `ProductionSeederTest` (2 test)

### 12. Auth NIK tanpa email — **DONE** (2026-08-15)

- Fortify: nonaktif `resetPasswords`, `emailVerification`; username `nik`
- `routes/auth.php`: hapus forgot-password & verify-email
- `routes/settings.php`: hapus middleware `verified`
- Reset password: SuperAdmin via menu Users (existing)
- Test: `NikAuthContractTest` (3 test)

| # | Item | Status |
|---|------|--------|
| 6–12 | P2 lengkap | **DONE** |

---

## Quality gate

| # | Item | Status |
|---|------|--------|
| 13 | Dependency audit | **SEBAGIAN** — CI + `composer audit`; advisory masih ada, butuh update package |
| 14 | Pint & PHPStan | **SEBAGIAN** — Pint file baru OK; full repo belum |
| 15 | Test MySQL integration | **SKIP material** — 10 skip = template SIAP, bukan MySQL |
| 16 | CI diperketat | **SEBAGIAN** — audit steps ditambah di `tests.yml` |

---

## Operasi produksi

| # | Item | Status |
|---|------|--------|
| 17 | Queue worker | **SCRIPT** — `tools/queue-worker.ps1` |
| 18 | Database backup | **SCRIPT** — `tools/backup-database.ps1` (password dari `.env`) |
| 19 | Session/cache/file | **DOK** — `.env.example` + preflight |
| 20 | Error pages | **DONE** — `resources/views/errors/{403,404,419,429,500,503}.blade.php` |
| 21 | HTTPS / web server | **BLOCKER IT** |

---

## Blocker eksternal (IT)

| Item | Status |
|------|--------|
| HTTPS + sertifikat | belum |
| User MySQL khusus OCMS | belum |
| Self-hosted runner | SKIP |
| Template SIAP di mesin dev (10 test skip) | folder checksheet besar tidak di repo |

---

## Bootstrap produksi (checklist singkat)

```bash
php artisan migrate --force
php artisan ocms:bootstrap-superadmin   # buat SuperAdmin — jangan pakai demo seeder
php artisan ocms:production-check
php artisan test
# Queue (Windows): powershell tools/queue-worker.ps1
# Backup: powershell tools/backup-database.ps1
```

**Jangan** jalankan `db:seed` demo di production. Demo user hanya `local`/`testing`.

---

## Catatan SQLite vs MySQL

- **Production/dev:** MySQL (`.env`)
- **PHPUnit:** SQLite `:memory:` (cepat, terisolasi) — normal Laravel, bukan wajib production
