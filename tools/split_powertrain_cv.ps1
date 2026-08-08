# Pisah workbook COMPLETED Control Valve Powertrain menjadi 1 file per tahap.
# Output: POWERTRAIN/_SIAP_UPLOAD_GSHEET/<KATEGORI>/<EGI>/<TAHAP> ....xlsx
# Logo header diganti AlamTri bila ada picture di zona header.
$ErrorActionPreference = 'Stop'

$repo = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS"
$srcDir = Join-Path $repo "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\POWERTRAIN\CHECKSHEET CONTROL VALVE POWERTRAIN ALL UNIT PT 2026"
$outRoot = Join-Path $repo "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\POWERTRAIN\_SIAP_UPLOAD_GSHEET"
$logoPath = Join-Path $repo "20250325160734-AlamTri-gede.png"

# Sumber COMPLETED → kategori + EGI + pola nama sheet per tahap
$jobs = @(
    @{
        File = 'cs CONTROL VALVE pc 1250-8(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'PC1250-8'
        Stages = @{
            'RECEIVING'  = @('RECEIVING NO 1', 'RECEIVING NO 2', 'RECEIVING NO 3')
            'DISASSEMBLY'= @('DISASSY NO1', 'DISASSY NO2', 'DISASSY NO3')
            'INSPECTION' = @('INSPEKSI NO1', 'INSPEKSI NO2', 'INSPEKSI NO3')
            'ASSEMBLY'   = @('ASSEMBLY NO1', 'ASSEMBLY NO2', 'ASSEMBLY NO3')
            'TEST'       = @('TESTBENCH NO 1', 'TESTBENCH NO 2', 'TESTBENCH NO 3')
            'DELIVERY'   = @('DELIVERY NO 1', 'DELIVERY NO 2', 'DELIVERY NO 3')
        }
    },
    @{
        File = 'CS CV PC 2000-8(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'PC2000-8'
        Stages = @{
            'RECEIVING'  = @('RECEIVING LH', 'RECEIVING RH')
            'DISASSEMBLY'= @('DISSASSY LH', 'DISASSY RH')
            'INSPECTION' = @('INSPEKSI LH', 'INSPEKSI RH')
            'ASSEMBLY'   = @('assembly LH', 'ASEMBLY RH')
            'TEST'       = @('TESTBENCH LH', 'TESTBENCH RH')
            'DELIVERY'   = @('DELIVERY LH', 'DELIVERY RH')
        }
    },
    @{
        File = 'cs cv pm d155-6(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'D155-6'
        Stages = @{
            'RECEIVING'  = @('receiving')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'cs cv pm d375-6(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'D375-6'
        Stages = @{
            'RECEIVING'  = @('receiving')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASSEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'cs cv tf hd785-7(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'HD785-7'
        Stages = @{
            'RECEIVING'  = @('RECEIVING')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('INSPEKSI')
            'ASSEMBLY'   = @('ASSEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'cs cv tm gd825-2(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'GD825A-2'
        Stages = @{
            'RECEIVING'  = @('RECEIVING')
            'DISASSEMBLY'= @('DISSASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASSEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'cs cv tm wa800-3(COMPLETED).xlsx'
        Category = 'Control Valve'; Egi = 'WA800-3'
        Stages = @{
            'RECEIVING'  = @('RECEIVING')
            'DISASSEMBLY'= @('dissassy')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('assembly')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'CS CYL HOIST 785(COMPLETED).xlsx'
        Category = 'Hydraulic Cylinder'; Egi = 'HD785-7'
        Stages = @{
            'RECEIVING'  = @('receiving')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'CS FSUSP HD785-7 (COMPLETED).xlsx'
        Category = 'Front Suspension'; Egi = 'HD785-7'
        Tag = ''
        Stages = @{
            'RECEIVING'  = @('receiving')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    },
    @{
        File = 'CS REARSP HD785-7(COMPLETED) .xlsx'
        Category = 'Rear Suspension'; Egi = 'HD785-7'
        Tag = ''
        Stages = @{
            'RECEIVING'  = @('receiving')
            'DISASSEMBLY'= @('DISASSY')
            'INSPECTION' = @('inspeksi')
            'ASSEMBLY'   = @('ASEMBLY')
            'DELIVERY'   = @('DELIVERY')
        }
    }
)

function Replace-HeaderLogo($ws, [string]$logo) {
    if (-not (Test-Path -LiteralPath $logo)) { return }
    $msoPicture = 13
    $cands = @()
    for ($i = 1; $i -le $ws.Shapes.Count; $i++) {
        $sh = $ws.Shapes.Item($i)
        if ($sh.Type -ne $msoPicture) { continue }
        if ($sh.Top -ge 120 -or $sh.Left -ge 280) { continue }
        if ($sh.Height -lt 8 -or $sh.Width -lt 8) { continue }
        $cands += $sh
    }
    if ($cands.Count -eq 0) { return }
    $target = $cands | Sort-Object -Property @{ Expression = { $_.Width * $_.Height }; Descending = $true } | Select-Object -First 1
    $left = [double]$target.Left; $top = [double]$target.Top
    $oldH = [double]$target.Height; $oldW = [double]$target.Width
    foreach ($c in $cands) { $c.Delete() }
    Add-Type -AssemblyName System.Drawing
    $img = [System.Drawing.Image]::FromFile($logo)
    $aspect = [double]$img.Width / [double]$img.Height
    $img.Dispose()
    $newH = $oldH; $newW = $newH * $aspect
    if ($newW -gt [Math]::Max($oldW * 1.35, 160)) {
        $newW = [Math]::Max($oldW, 120); $newH = $newW / $aspect
    }
    $pic = $ws.Shapes.AddPicture($logo, 0, 1, $left, $top, $newW, $newH)
    $pic.Name = 'AlamTri Logo'
}

if (-not (Test-Path $outRoot)) { New-Item -ItemType Directory -Path $outRoot | Out-Null }

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($job in $jobs) {
    $srcPath = Join-Path $srcDir $job.File
    if (-not (Test-Path -LiteralPath $srcPath)) {
        Write-Output ("!! FILE TIDAK ADA: " + $job.File)
        continue
    }

    Write-Output ("==== " + $job.File + " → " + $job.Category + " / " + $job.Egi)
    $src = $xl.Workbooks.Open($srcPath, 0, $true)
    $byName = @{}
    foreach ($ws in $src.Worksheets) { $byName[$ws.Name] = $ws }

    $catDir = Join-Path $outRoot $job.Category
    $egiDir = Join-Path $catDir $job.Egi
    if (-not (Test-Path $egiDir)) { New-Item -ItemType Directory -Path $egiDir -Force | Out-Null }

    foreach ($stage in $job.Stages.Keys) {
        $sheetNames = @($job.Stages[$stage])
        $found = @()
        foreach ($n in $sheetNames) {
            if ($byName.ContainsKey($n)) { $found += $n }
            else { Write-Output ("  !! sheet hilang: '" + $n + "'") }
        }
        if ($found.Count -eq 0) { continue }

        $dest = $xl.Workbooks.Add()
        foreach ($n in $found) {
            $byName[$n].Copy([System.Reflection.Missing]::Value, $dest.Worksheets.Item($dest.Worksheets.Count))
            $copied = $dest.Worksheets.Item($dest.Worksheets.Count)
            # nama tab pendek & jelas
            $short = $n
            if ($short.Length -gt 31) { $short = $short.Substring(0, 31) }
            try { $copied.Name = $short } catch {}
            Replace-HeaderLogo $copied $logoPath
            Write-Output ("  + " + $stage + " ← " + $n)
        }

        while ($dest.Worksheets.Count -gt 1 -and $dest.Worksheets.Item(1).Name -match '^Sheet\d+$') {
            $dest.Worksheets.Item(1).Delete()
        }

        $tag = if ($job.Tag) { ' ' + $job.Tag } else { '' }
        $outName = "$stage $($job.Category)$tag $($job.Egi).xlsx"
        $outPath = Join-Path $egiDir $outName
        if (Test-Path $outPath) { Remove-Item $outPath -Force }
        $dest.SaveAs($outPath, 51)
        $dest.Close($false)
        Write-Output ("  tersimpan: " + $outPath)
    }

    $src.Close($false)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "SELESAI"
