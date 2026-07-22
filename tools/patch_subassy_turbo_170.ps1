# Patch TURBO DISASSY ke SAA6D170E-5 (file sempat miss saat merge)
$ErrorActionPreference = 'Stop'
$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$outDir = Join-Path $base "_SIAP_UPLOAD_GSHEET"
$logoPath = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\20250325160734-AlamTri-gede.png"

$destPath = Join-Path $outDir 'SUBASSY DISASSEMBLY ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx'
$srcFile = "$base\SAA6D170E-5\SUB ASSY\TURBOCHARGER\DISSASEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"
$sheetName = 'Turbocharger Dissaasy'
$asName = 'TURBO DISASSY'

if (-not (Test-Path -LiteralPath $srcFile)) { throw "Source missing: $srcFile" }

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

$dest = $xl.Workbooks.Open($destPath)
$existing = @()
foreach ($ws in $dest.Worksheets) { $existing += $ws.Name }
if ($existing -contains $asName) {
    Write-Output "sudah ada $asName"
} else {
    $src = $xl.Workbooks.Open($srcFile, 0, $true)
    $srcWs = $null
    foreach ($ws in $src.Worksheets) { if ($ws.Name -eq $sheetName) { $srcWs = $ws; break } }
    if (-not $srcWs) { throw "Sheet '$sheetName' not found" }
    $srcWs.Copy([System.Reflection.Missing]::Value, $dest.Worksheets.Item($dest.Worksheets.Count))
    $copied = $dest.Worksheets.Item($dest.Worksheets.Count)
    $copied.Name = $asName
    $src.Close($false)
    Write-Output "+ $asName ditambahkan"

    # ganti logo header
    $msoPicture = 13
    $cands = @()
    for ($i = 1; $i -le $copied.Shapes.Count; $i++) {
        $sh = $copied.Shapes.Item($i)
        if ($sh.Type -ne $msoPicture) { continue }
        if ($sh.Top -ge 120 -or $sh.Left -ge 280) { continue }
        if ($sh.Height -lt 8 -or $sh.Width -lt 8) { continue }
        $cands += $sh
    }
    if ($cands.Count -gt 0) {
        $target = $cands | Sort-Object -Property @{Expression = { $_.Width * $_.Height }; Descending = $true} | Select-Object -First 1
        $left = [double]$target.Left; $top = [double]$target.Top
        $oldH = [double]$target.Height; $oldW = [double]$target.Width
        foreach ($c in $cands) { $c.Delete() }
        Add-Type -AssemblyName System.Drawing
        $img = [System.Drawing.Image]::FromFile($logoPath)
        $aspect = [double]$img.Width / [double]$img.Height
        $img.Dispose()
        $newH = $oldH; $newW = $newH * $aspect
        if ($newW -gt [Math]::Max($oldW * 1.35, 160)) {
            $newW = [Math]::Max($oldW, 120); $newH = $newW / $aspect
        }
        $pic = $copied.Shapes.AddPicture($logoPath, 0, 1, $left, $top, $newW, $newH)
        $pic.Name = 'AlamTri Logo'
        Write-Output "  logo AlamTri diganti"
    }
}

$dest.Save()
$dest.Close($false)
$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "PATCH TURBO SELESAI"
