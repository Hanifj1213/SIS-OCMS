<#
.SYNOPSIS
    Seragamkan label kolom keputusan di sheet Disassembly lewat Excel COM.

.DESCRIPTION
    Menggantikan tools/normalize_decision_headers.py yang memakai openpyxl.
    openpyxl menulis ulang seluruh workbook dan MEMBUANG gambar (file 14 MB
    jadi 85 KB), sedangkan Excel menyimpan formatnya apa adanya.

    Hanya 2-3 sel header per sheet yang disentuh:
        REUSE | SALVG  | REPAIR   ->  REUSE | SALVAGE | REPLACE
        REPAIR| SALVG  | REPAIR   ->  REUSE | SALVAGE | REPLACE   (outlier D375-6)

    Sel keputusan pada baris part TIDAK diisi FALSE. Sel kosong sudah dibaca
    sebagai "tidak dicentang" oleh parser OCMS, dan Google Sheets mengubah sel
    kosong jadi checkbox tak tercentang saat Insert > Checkbox. Mengisi ribuan
    sel + data validation per baris adalah yang dulu membuat file membengkak.

.EXAMPLE
    pwsh -File tools/normalize_headers_excel.ps1 -DryRun
    pwsh -File tools/normalize_headers_excel.ps1
#>
[CmdletBinding()]
param(
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$checksheet = Join-Path $repoRoot 'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY'
$targetDirs = @(
    (Join-Path $checksheet 'ENGINE\_SIAP_UPLOAD_GSHEET'),
    (Join-Path $checksheet 'POWERTRAIN\_SIAP_UPLOAD_GSHEET')
)

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupRoot = Join-Path $checksheet "_SIAP_BACKUP\${stamp}_headers_excel"

# Ukuran & jumlah gambar sebelum/sesudah, untuk membuktikan format tidak rusak.
function Get-MediaCount {
    param([string]$Path)
    try {
        Add-Type -AssemblyName System.IO.Compression.FileSystem -ErrorAction SilentlyContinue
        $zip = [System.IO.Compression.ZipFile]::OpenRead($Path)
        $n = ($zip.Entries | Where-Object { $_.FullName -like 'xl/media/*' }).Count
        $zip.Dispose()
        return $n
    } catch {
        return -1
    }
}

function Normalize-Text {
    param($Value)
    if ($null -eq $Value) { return '' }
    return ($Value.ToString().Trim() -replace '\s+', ' ').ToUpper()
}

$files = @()
foreach ($dir in $targetDirs) {
    if (Test-Path $dir) {
        $files += Get-ChildItem -Path $dir -Recurse -Filter '*.xlsx' |
            Where-Object { $_.Name -notlike '~$*' -and $_.Name -match 'DISASSEMBLY' }
    }
}

if (-not $files) { Write-Host 'Tidak ada file Disassembly ditemukan.'; return }

Write-Host ("Memeriksa {0} file Disassembly..." -f $files.Count)

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false
$excel.ScreenUpdating = $false
$excel.AskToUpdateLinks = $false

$changedFiles = 0
$changedCells = 0

try {
    foreach ($file in $files) {
        $rel = $file.FullName.Substring($checksheet.Length + 1)
        $edits = @()   # daftar @{Sheet; Row; Col; From; To}

        $wb = $excel.Workbooks.Open($file.FullName, 0, $false)
        try {
            foreach ($ws in $wb.Worksheets) {
                # Cari baris header: ada sel SALV* dan tetangganya label keputusan.
                for ($r = 1; $r -le 40; $r++) {
                    $salvCol = $null
                    for ($c = 1; $c -le 40; $c++) {
                        if ((Normalize-Text $ws.Cells.Item($r, $c).Value2).StartsWith('SALV')) {
                            $salvCol = $c
                            break
                        }
                    }
                    if (-not $salvCol) { continue }

                    $left  = Normalize-Text $ws.Cells.Item($r, $salvCol - 1).Value2
                    $mid   = Normalize-Text $ws.Cells.Item($r, $salvCol).Value2
                    $right = Normalize-Text $ws.Cells.Item($r, $salvCol + 1).Value2

                    # Hanya blok "3 kolom keputusan berurutan" yang ditangani.
                    $isDecisionBlock = ($left -in @('REUSE', 'REUSED', 'REPAIR')) -and
                                       ($right -in @('REPAIR', 'REPLACE'))
                    if (-not $isDecisionBlock) { continue }

                    if ($left -ne 'REUSE') {
                        $edits += @{ Sheet = $ws.Name; Row = $r; Col = $salvCol - 1; From = $left; To = 'REUSE' }
                    }
                    if ($mid -ne 'SALVAGE') {
                        $edits += @{ Sheet = $ws.Name; Row = $r; Col = $salvCol; From = $mid; To = 'SALVAGE' }
                    }
                    if ($right -ne 'REPLACE') {
                        $edits += @{ Sheet = $ws.Name; Row = $r; Col = $salvCol + 1; From = $right; To = 'REPLACE' }
                    }
                }
            }

            if ($edits.Count -eq 0) {
                $wb.Close($false)
                continue
            }

            Write-Host ''
            Write-Host $rel -ForegroundColor Yellow
            foreach ($e in $edits) {
                Write-Host ("    [{0}] r{1}c{2}: '{3}' -> '{4}'" -f $e.Sheet, $e.Row, $e.Col, $e.From, $e.To)
            }

            if ($DryRun) {
                $wb.Close($false)
            }
            else {
                $mediaBefore = Get-MediaCount $file.FullName

                $dest = Join-Path $backupRoot $rel
                New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null
                Copy-Item $file.FullName $dest

                foreach ($e in $edits) {
                    $wb.Worksheets.Item($e.Sheet).Cells.Item($e.Row, $e.Col).Value2 = $e.To
                }
                $wb.Save()
                $wb.Close($true)

                $mediaAfter = Get-MediaCount $file.FullName
                $flag = if ($mediaAfter -lt $mediaBefore) { '  !! GAMBAR HILANG' } else { '' }
                Write-Host ("    gambar {0} -> {1}{2}" -f $mediaBefore, $mediaAfter, $flag) -ForegroundColor DarkGray
            }

            $changedFiles++
            $changedCells += $edits.Count
        }
        catch {
            Write-Warning ("Gagal memproses {0}: {1}" -f $rel, $_.Exception.Message)
            try { $wb.Close($false) } catch { }
        }
    }
}
finally {
    $excel.Quit()
    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel) | Out-Null
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}

Write-Host ''
Write-Host ('-' * 72)
$prefix = if ($DryRun) { 'DRY RUN - ' } else { '' }
Write-Host ("{0}File diubah: {1} | Sel header: {2}" -f $prefix, $changedFiles, $changedCells)
if (-not $DryRun -and $changedFiles -gt 0) {
    Write-Host ("Backup: {0}" -f $backupRoot.Substring($repoRoot.Length + 1))
}
