# Ganti logo header (SIS) -> AlamTri di workbook SUBASSY DISASSEMBLY/MEASUREMENT.
# Heuristik: picture di zona header (Top < 120, Left < 280), ganti posisi/ukuran setara.
$ErrorActionPreference = 'Stop'

$repo = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS"
$logoPath = Join-Path $repo "20250325160734-AlamTri-gede.png"
$outDir = Join-Path $repo "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE\_SIAP_UPLOAD_GSHEET"

if (-not (Test-Path $logoPath)) { throw "Logo tidak ada: $logoPath" }

Add-Type -AssemblyName System.Drawing
$logoImg = [System.Drawing.Image]::FromFile($logoPath)
$logoAspect = [double]$logoImg.Width / [double]$logoImg.Height
$logoImg.Dispose()
Write-Output ("Logo AlamTri aspect={0:N2}" -f $logoAspect)

$files = @(Get-ChildItem $outDir -Filter 'SUBASSY DISASSEMBLY ENGINE *.xlsx') +
         @(Get-ChildItem $outDir -Filter 'SUBASSY MEASUREMENT ENGINE *.xlsx')

if ($files.Count -eq 0) { throw "Tidak ada file SUBASSY DISASSEMBLY/MEASUREMENT di $outDir" }

$msoPicture = 13  # msoPicture

function Get-HeaderLogoCandidates($ws) {
    $cands = @()
    for ($i = 1; $i -le $ws.Shapes.Count; $i++) {
        $sh = $ws.Shapes.Item($i)
        if ($sh.Type -ne $msoPicture) { continue }
        if ($sh.Top -ge 120) { continue }
        if ($sh.Left -ge 280) { continue }
        if ($sh.Height -lt 8 -or $sh.Width -lt 8) { continue }
        $cands += $sh
    }
    return $cands
}

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

$totalReplaced = 0
$totalSheets = 0
$totalSkip = 0

foreach ($f in $files) {
    Write-Output "=============================================="
    Write-Output ("FILE: " + $f.Name)
    $wb = $xl.Workbooks.Open($f.FullName)

    foreach ($ws in $wb.Worksheets) {
        $totalSheets++
        $cands = @(Get-HeaderLogoCandidates $ws)
        if ($cands.Count -eq 0) {
            Write-Output ("  - " + $ws.Name + ": tidak ada logo header")
            $totalSkip++
            continue
        }

        # Ambil kandidat terluas di header (biasanya logo SIS)
        $target = $cands | Sort-Object -Property @{Expression = { $_.Width * $_.Height }; Descending = $true} | Select-Object -First 1
        $left = [double]$target.Left
        $top = [double]$target.Top
        $oldW = [double]$target.Width
        $oldH = [double]$target.Height

        # Hapus semua kandidat header (kadang logo + watermark kecil)
        foreach ($c in $cands) { $c.Delete() }

        # Fit: jaga tinggi lama, lebar mengikuti aspect AlamTri; kalau terlalu lebar, fit ke box lama
        $newH = $oldH
        $newW = $newH * $logoAspect
        if ($newW -gt [Math]::Max($oldW * 1.35, 160)) {
            $newW = [Math]::Max($oldW, 120)
            $newH = $newW / $logoAspect
        }

        $pic = $ws.Shapes.AddPicture($logoPath, 0, 1, $left, $top, $newW, $newH)
        $pic.Name = 'AlamTri Logo'
        Write-Output ("  + " + $ws.Name + (": logo diganti L={0:N0} T={1:N0} {2:N0}x{3:N0}" -f $left, $top, $newW, $newH))
        $totalReplaced++
    }

    $wb.Save()
    $wb.Close($false)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "=============================================="
Write-Output ("SELESAI: diganti={0} skip={1} sheet={2} file={3}" -f $totalReplaced, $totalSkip, $totalSheets, $files.Count)
