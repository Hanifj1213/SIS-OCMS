# Tambahkan sheet yang gagal ke workbook SUBASSY yang sudah ada
$ErrorActionPreference = 'Stop'
$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$outDir = Join-Path $base "_SIAP_UPLOAD_GSHEET"

$patches = @(
    @{
        Dest = Join-Path $outDir 'SUBASSY INSPECTION ENGINE SA12V140E-1 (WA800-3).xlsx'
        Sheets = @(
            @{ File = "$base\SA12V140E-1\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' }
        )
    },
    @{
        Dest = Join-Path $outDir 'SUBASSY INSPECTION ENGINE SA6D140E-2 (GD825A).xlsx'
        Sheets = @(
            @{ File = "$base\SA6D140E-2\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' }
        )
    },
    @{
        Dest = Join-Path $outDir 'SUBASSY INSPECTION ENGINE SAA6D140E-5 (D155).xlsx'
        Sheets = @(
            @{ File = "$base\SAA6D140E-5\SUB ASSY\TURBOCHARGER\DISSASSEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
            @{ File = "$base\SAA6D140E-5\SUB ASSY\CYL HEAD\DELIVERY SALVAGING\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' }
        )
    },
    @{
        Dest = Join-Path $outDir 'SUBASSY INSPECTION ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx'
        Sheets = @(
            @{ File = "$base\SAA6D170E-5\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' }
        )
    }
)

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($p in $patches) {
    Write-Output ("PATCH: " + (Split-Path $p.Dest -Leaf))
    $dest = $xl.Workbooks.Open($p.Dest)
    $existing = @()
    foreach ($ws in $dest.Worksheets) { $existing += $ws.Name }

    foreach ($s in $p.Sheets) {
        if ($existing -contains $s.As) {
            Write-Output ("  skip (sudah ada): " + $s.As)
            continue
        }
        $src = $xl.Workbooks.Open($s.File, 0, $true)
        $srcWs = $null
        foreach ($ws in $src.Worksheets) { if ($ws.Name -eq $s.Sheet) { $srcWs = $ws; break } }
        if (-not $srcWs) {
            Write-Output ("  !! sheet tidak ada: '" + $s.Sheet + "'")
            $src.Close($false)
            continue
        }
        $srcWs.Copy([System.Reflection.Missing]::Value, $dest.Worksheets.Item($dest.Worksheets.Count))
        $copied = $dest.Worksheets.Item($dest.Worksheets.Count)
        $copied.Name = $s.As
        $src.Close($false)
        Write-Output ("  + " + $s.As)
    }
    $dest.Save()
    $dest.Close($false)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "PATCH SELESAI"
