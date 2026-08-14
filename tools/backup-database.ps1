# Backup database SISI-OCMS (MySQL)
# Password dibaca dari .env — jangan hardcode di script ini.
param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot)
)

Set-Location $ProjectRoot

if (-not (Test-Path ".env")) {
    Write-Error ".env tidak ditemukan di $ProjectRoot"
    exit 1
}

function Get-EnvValue([string]$Key) {
    $line = Select-String -Path ".env" -Pattern "^$Key=" -SimpleMatch | Select-Object -First 1
    if (-not $line) { return $null }
    return ($line.Line -split '=', 2)[1].Trim().Trim('"')
}

$db = Get-EnvValue 'DB_DATABASE'
$user = Get-EnvValue 'DB_USERNAME'
$pass = Get-EnvValue 'DB_PASSWORD'
$host = Get-EnvValue 'DB_HOST'
if (-not $host) { $host = '127.0.0.1' }

if (-not $db -or -not $user) {
    Write-Error 'DB_DATABASE / DB_USERNAME kosong di .env'
    exit 1
}

$backupDir = Join-Path $ProjectRoot 'storage\backups'
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$outFile = Join-Path $backupDir "sisi_ocms_${stamp}.sql"

$mysqldump = Get-Command mysqldump -ErrorAction SilentlyContinue
if (-not $mysqldump) {
    Write-Error 'mysqldump tidak ada di PATH. Pastikan MySQL client Laragon terpasang.'
    exit 1
}

$env:MYSQL_PWD = $pass
& mysqldump.exe -h $host -u $user --single-transaction --routines --triggers $db | Set-Content -Encoding utf8 $outFile
Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue

if ($LASTEXITCODE -ne 0) {
    Write-Error "Backup gagal (exit $LASTEXITCODE)"
    exit $LASTEXITCODE
}

Write-Host "Backup OK: $outFile"
