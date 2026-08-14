# Queue worker SISI-OCMS — jalankan sebagai Scheduled Task / NSSM service
# Contoh manual (development):
#   php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot)
)

Set-Location $ProjectRoot

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Error 'php tidak ada di PATH'
    exit 1
}

Write-Host 'Memulai queue worker OCMS (Ctrl+C untuk stop)...'
& php artisan queue:work database --sleep=3 --tries=3 --timeout=120 --max-time=3600
