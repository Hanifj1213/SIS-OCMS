# Deploy SISI-OCMS di server Windows/Laragon (dipanggil GitHub Actions self-hosted runner
# atau dijalankan manual). Tidak menyentuh .env / storage upload / database.
#
# Usage:
#   powershell -ExecutionPolicy Bypass -File tools\deploy.ps1
#   powershell -ExecutionPolicy Bypass -File tools\deploy.ps1 -DeployPath "D:\www\sisi-ocms"

param(
    [string]$DeployPath = $env:OCMS_DEPLOY_PATH
)

$ErrorActionPreference = "Stop"

if (-not $DeployPath -or $DeployPath.Trim() -eq "") {
    # Default: folder parent dari tools\
    $DeployPath = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
}

Write-Host "==> Deploy path: $DeployPath"
Set-Location $DeployPath

if (-not (Test-Path "artisan")) {
    throw "artisan tidak ditemukan di $DeployPath — salah folder?"
}

if (-not (Test-Path ".env")) {
    throw ".env tidak ada. Buat sekali di server dari .env.example (jangan di-commit)."
}

Write-Host "==> git fetch + reset ke origin (main/master)"
git fetch --prune origin
$branch = (git rev-parse --abbrev-ref HEAD).Trim()
if ($branch -eq "HEAD") {
    # Detached HEAD dari Actions checkout — sync ke main/master bila ada
    if (git show-ref --verify --quiet refs/remotes/origin/main) {
        git checkout -B main origin/main
        $branch = "main"
    } elseif (git show-ref --verify --quiet refs/remotes/origin/master) {
        git checkout -B master origin/master
        $branch = "master"
    }
}
git reset --hard "origin/$branch"
git clean -fd -e ".env" -e "storage/" -e "node_modules/" -e "vendor/"

Write-Host "==> composer install --no-dev"
composer install --no-dev --optimize-autoloader --no-interaction

if (Test-Path "package.json") {
    Write-Host "==> npm ci && npm run build"
    npm ci
    npm run build
}

Write-Host "==> migrate + cache"
php artisan migrate --force
php artisan storage:link 2>$null
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "==> Deploy selesai. Branch=$branch Commit=$(git rev-parse --short HEAD)"
