# Gabungkan checksheet inspeksi SUB ASSY menjadi 2 workbook per EGI:
#   - SUBASSY DISASSEMBLY ...xlsx  (tab * DISASSY)
#   - SUBASSY MEASUREMENT ...xlsx  (tab * MEASURE / SALVAGE / POLISH)
# Original tidak diubah. Output ke _SIAP_UPLOAD_GSHEET.
# Setelah merge, jalankan tools/replace_logo_subassy.ps1 untuk ganti logo.
$ErrorActionPreference = 'Stop'

$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$outDir = Join-Path $base "_SIAP_UPLOAD_GSHEET"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

function New-Model([string]$Tag, [array]$Sheets) {
    return @{ Tag = $Tag; Sheets = $Sheets }
}

# File + sheet sumber + nama tab tujuan (max 31 karakter Excel)
$models = @(
    (New-Model 'SA12V140E-1 (WA800-3)' @(
        @{ File = "$base\SA12V140E-1\SUB ASSY\COMPRESSOR\DISSASEMBLY\006_AIR COMPRESSOR Disassembly check sheet.xls"; Sheet = 'Air Comp. Dissaasy checksheet'; As = 'COMPRESSOR DISASSY' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\CYL HEAD\DISSASEMBLY\001.CYL. HEAD_disassembly check sheet REV 4.ALL ENGINE.xls"; Sheet = 'Cyl.Head'; As = 'CYL HEAD DISASSY' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\TURBOCHARGER\DISSASEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\ACC DRIVE\MEASURING\Alternator Drive Shaft and Gear 12V140-1,2.xls"; Sheet = 'Alt.Drive Shaft 12V140 (Rev)'; As = 'ACC DRIVE MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\CAM FOLLOWER\MEASUREMENT\Proses & Measuring CAM FOLLOWER ALL ENGINE.xlsx"; Sheet = 'CAM FOLLOWER Polish_12V140-1'; As = 'CAM FOLLOWER MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\CROSSHEAD\SALVAGING\Salvage surface grinding Cross Head ALL ENGINE.xlsx"; Sheet = 'Cross head Salvage (REv)'; As = 'CROSSHEAD MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\CYL HEAD\MEASUREMENT\CYL HEAD MEASUREMENT NEW REVISI.xls"; Sheet = 'Cyl.Head12V140-1(AFTER REVISI)'; As = 'CYL HEAD MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\ROCKER ARM\MEASUREMENT\Proses & Measuring ROCKER ARM_and_ SHAFT ALL ENGINE.xlsx"; Sheet = 'Rocker Arm Polish_12V140'; As = 'ROCKER ARM MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\TIMING GEAR\MEASURING\process IDLER GEAR AND SHAFT Process Checksheet.ALL ENGINE.xlsx"; Sheet = 'Idler Gear & Shaft 12V140-1E'; As = 'TIMING GEAR MEASURE' },
        @{ File = "$base\SA12V140E-1\SUB ASSY\WATERPUMP\MEASURING\Water Pump Shaft12V-3.xls"; Sheet = 'WATER PUMP SHAFT 1'; As = 'WATERPUMP MEASURE' }
    )),
    (New-Model 'SA6D140E-2 (GD825A)' @(
        @{ File = "$base\SA6D140E-2\SUB ASSY\COMPRESSOR\DISSASSEMBLY\006_AIR COMPRESSOR Disassembly check sheet.xls"; Sheet = 'Air Comp. Dissaasy checksheet'; As = 'COMPRESSOR DISASSY' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\CYL HEAD\DISSASSEMBLY\001.CYL. HEAD_disassembly check sheet REV 4.ALL ENGINE.xls"; Sheet = 'Cyl.Head'; As = 'CYL HEAD DISASSY' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\TURBOCHARGER\DISSASSEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\CAM FOLLOWER\MEASUREMENT\Proses & Measuring CAM FOLLOWER ALL ENGINE.xlsx"; Sheet = 'CAM FOLLOWER Polish_6D140-2'; As = 'CAM FOLLOWER MEASURE' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\CROSSHEAD\SALVAGE\Salvage surface grinding Cross Head ALL ENGINE.xlsx"; Sheet = 'Cross head Salvage (REv)'; As = 'CROSSHEAD MEASURE' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\CYL HEAD\MEASUREMENT\New Rev_ CYL. HEAD measurement _6d140-2(new revisi).xls"; Sheet = 'Cyl.Head 6D140-2 measuremen'; As = 'CYL HEAD MEASURE' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' },
        @{ File = "$base\SA6D140E-2\SUB ASSY\ROCKER ARM\MEASUREMENT\Proses & Measuring ROCKER ARM_and_ SHAFT ALL ENGINE.xlsx"; Sheet = 'Rocker Arm Polish_6D140E-2'; As = 'ROCKER ARM MEASURE' }
    )),
    (New-Model 'SAA12V140E-3 (PC2000-8)' @(
        @{ File = "$base\SAA12V140E-3\SUB ASSY\CYL HEAD\dissasembly\001.CYL. HEAD_disassembly check sheet REV 4..xls"; Sheet = 'Cyl.Head(REV. NEW)'; As = 'CYL HEAD DISASSY' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\SUPLLY PUMP\DISSASEMBLY\Disassy CRI.xls"; Sheet = 'Dis assy CRI'; As = 'SUPPLY PUMP DISASSY' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\TURBOCHARGER\DISSASEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\CAM FOLLOWER\MEASURING\Proses & Measuring CAM FOLLOWER ALL ENGINE.xlsx"; Sheet = 'CAM FOLLOWER Polish_12V140-3'; As = 'CAM FOLLOWER MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\CROSSHEAD\salvaging\Salvage surface grinding Cross Head ALL ENGINE.xlsx"; Sheet = 'Cross head Salvage (REv)'; As = 'CROSSHEAD MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\CYL HEAD\measuring\CYL HEAD MEASUREMENT NEW REVISI(12V-1&3).xls"; Sheet = 'Cyl.Head12V140-3(REV2)after mac'; As = 'CYL HEAD MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\ROCKER ARM\MEASURING\Polishing_ ROCKER ARM_and_ SHAFT_12V140-3.xls"; Sheet = 'Rocker Arm Polish_12V140 -3'; As = 'ROCKER ARM MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\ROCKER ARM\MEASURING\ROCKER ARM SHAFT_12V140-3.xls"; Sheet = 'Rocker Arm Shaft (12V140-3'; As = 'ROCKER SHAFT MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\TIMING GEAR\MEASURING\IDLER GEAR AND SHAFT_12v140-3..xls"; Sheet = 'Idler Gear & Shaft 12V140-3'; As = 'TIMING GEAR MEASURE' },
        @{ File = "$base\SAA12V140E-3\SUB ASSY\WATERPUMP\measuring\Water Pump Shaft12V-3.xls"; Sheet = 'WATER PUMP SHAFT 1'; As = 'WATERPUMP MEASURE' }
    )),
    (New-Model 'SAA6D140E-5 (D155)' @(
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CYL HEAD\DISSASSEMBLY\001.CYL. HEAD_disassembly check sheet REV 4.ALL ENGINE.xls"; Sheet = 'Cyl.Head'; As = 'CYL HEAD DISASSY' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\SUPPLY PUMP\DISSASSEMBLY\Disassy CRI.xls"; Sheet = 'Dis assy CRI'; As = 'SUPPLY PUMP DISASSY' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\TURBOCHARGER\DISSASSEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CAM FOLLOWER\MEASURING\002_CAM FOLLOWER AND PUSH ROD_REV05(6D140-5).xls"; Sheet = 'Cam Follower (Rev)'; As = 'CAM FOLLOWER MEASURE' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CAM FOLLOWER\MEASURING\Proses & Measuring CAM FOLLOWER ALL ENGINE.xlsx"; Sheet = 'CAM FOLLOWER Polish_6D140E-5'; As = 'CAM FOLLOWER POLISH' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CROSSHEAD\SALVAGING\Salvage surface grinding Cross Head ALL ENGINE.xlsx"; Sheet = 'Cross head Salvage (REv)'; As = 'CROSSHEAD MEASURE' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CYL HEAD\MEASURING\001_ CYL. HEAD measurement _REV140-5(NEW REVISION).xls"; Sheet = 'Cyl.Head 6D140-5 measuremen'; As = 'CYL HEAD MEASURE' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\CYL HEAD\DELIVERY SALVAGING\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\ROCKER ARM\MEASUREMENT\ROCKER ARM SHAFT_6D140-5.xls"; Sheet = 'Rocker Arm Shaft (6D140E-5)'; As = 'ROCKER SHAFT MEASURE' },
        @{ File = "$base\SAA6D140E-5\SUB ASSY\ROCKER ARM\MEASUREMENT\Proses & Measuring ROCKER ARM_ ALL ENGINE.xlsx"; Sheet = 'Rocker Arm Polish_6D140E-5'; As = 'ROCKER ARM MEASURE' }
    )),
    (New-Model 'SAA6D170E-5 (D375-6 PC1250-8)' @(
        @{ File = "$base\SAA6D170E-5\SUB ASSY\COMPRESSOR\DISSASSEMBLY\006_AIR COMPRESSOR Disassembly check sheet ALL ENGINE.xls"; Sheet = 'Air Comp. Dissaasy checksheet'; As = 'COMPRESSOR DISASSY' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\CYL HEAD\DISSASSEMBLY\001.CYL. HEAD_disassembly check sheet REV 4.ALL ENGINE.xls"; Sheet = 'Cyl.Head'; As = 'CYL HEAD DISASSY' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\SUPPLY PUMP\DISSASEMBLY\Disassy CRI.xls"; Sheet = 'Dis assy CRI'; As = 'SUPPLY PUMP DISASSY' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\TURBOCHARGER\DISSASSEMBLY\003.TURBO CHARGER_Disassembly check sheet REV 4.xls"; Sheet = 'Turbocharger Dissaasy'; As = 'TURBO DISASSY' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\ACC DRIVE\MEASUREMENT\007_ACC_DRIVE_SHAFT_AND_GEAR_6D170_Measurement.xls"; Sheet = 'Acc Drive Shaft 6D170Measu(Rev)'; As = 'ACC DRIVE MEASURE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\ACC DRIVE\MEASUREMENT\005_ACC. DRIVE HOUSING Measurement.xlsx"; Sheet = 'Acc Drive Housing measure (Rev)'; As = 'ACC HOUSING MEASURE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\CROSSHEAD\MEASURING\Salvage surface grinding Cross Head ALL ENGINE.xlsx"; Sheet = 'Cross head Salvage (REv)'; As = 'CROSSHEAD MEASURE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\CYL HEAD\MEASUREMENT\Measuring cyl head SAA6D170E-5(HD 465-7R,PC1250-8,D375-6).xlsx"; Sheet = 'CYL HEAD 6D170-5'; As = 'CYL HEAD MEASURE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\CYL HEAD\MEASUREMENT\Salvage surface grinding CYLINDER HEAD ALL ENGINE.xlsx"; Sheet = 'Cylinder head  Salvage '; As = 'CYL HEAD SALVAGE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\ROCKER ARM\MEASURING\Proses & Measuring ROCKER ARM SHAFT_6D140-5.xlsx"; Sheet = 'Rocker Arm Shaft (6D170E-5)'; As = 'ROCKER SHAFT MEASURE' },
        @{ File = "$base\SAA6D170E-5\SUB ASSY\TIMING GEAR\MEASURING\process IDLER GEAR AND SHAFT Process Checksheet.6D170-5.xlsx"; Sheet = 'Idler Gear & Shaft 6D170E-5'; As = 'TIMING GEAR MEASURE' }
    ))
)

function Test-IsDisassy([string]$As) {
    return $As -match 'DISASSY$'
}

function Save-Workbook($xl, $sheets, [string]$outPath) {
    if ($sheets.Count -eq 0) {
        Write-Output ("  (skip kosong) " + (Split-Path $outPath -Leaf))
        return
    }

    $dest = $xl.Workbooks.Add()
    $openBooks = @{}

    foreach ($s in $sheets) {
        if (-not (Test-Path -LiteralPath $s.File)) {
            Write-Output ("  !! FILE TIDAK ADA: " + $s.File)
            continue
        }
        if (-not $openBooks.ContainsKey($s.File)) {
            $openBooks[$s.File] = $xl.Workbooks.Open($s.File, 0, $true)
        }
        $srcWb = $openBooks[$s.File]

        $srcWs = $null
        foreach ($ws in $srcWb.Worksheets) {
            if ($ws.Name -eq $s.Sheet) { $srcWs = $ws; break }
        }
        if (-not $srcWs) {
            Write-Output ("  !! SHEET TIDAK ADA '" + $s.Sheet + "' di " + (Split-Path $s.File -Leaf))
            Write-Output ("     tersedia: " + (($srcWb.Worksheets | ForEach-Object { "'" + $_.Name + "'" }) -join ', '))
            continue
        }

        $srcWs.Copy([System.Reflection.Missing]::Value, $dest.Worksheets.Item($dest.Worksheets.Count))
        $copied = $dest.Worksheets.Item($dest.Worksheets.Count)
        $copied.Name = $s.As.Substring(0, [Math]::Min(31, $s.As.Length))
        if ($copied.Visible -ne -1) { $copied.Visible = -1 }
        Write-Output ("  + tab '" + $copied.Name + "'")
    }

    foreach ($wb in $openBooks.Values) { $wb.Close($false) }

    while ($dest.Worksheets.Count -gt 0 -and $dest.Worksheets.Item(1).Name -match '^Sheet\d+$') {
        if ($dest.Worksheets.Count -eq 1) { break }
        $dest.Worksheets.Item(1).Delete()
    }

    if (Test-Path $outPath) { Remove-Item $outPath -Force }
    $dest.SaveAs($outPath, 51)
    $dest.Close($false)
    Write-Output ("  tersimpan: " + $outPath)
}

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

# Hapus file gabungan lama (kalau terkunci, rename/skip)
Get-ChildItem $outDir -Filter 'SUBASSY INSPECTION ENGINE *.xlsx' -ErrorAction SilentlyContinue | ForEach-Object {
    try {
        Remove-Item $_.FullName -Force -ErrorAction Stop
        Write-Output ("hapus lama: " + $_.Name)
    } catch {
        $bak = $_.FullName + '.old'
        try {
            if (Test-Path $bak) { Remove-Item $bak -Force -ErrorAction SilentlyContinue }
            Rename-Item $_.FullName ($_.Name + '.old') -ErrorAction Stop
            Write-Output ("rename lama (locked): " + $_.Name + " -> .old")
        } catch {
            Write-Output ("skip hapus (masih locked): " + $_.Name)
        }
    }
}

foreach ($model in $models) {
    Write-Output "=============================================="
    Write-Output ("MODEL: " + $model.Tag)

    $disassy = @($model.Sheets | Where-Object { Test-IsDisassy $_.As })
    $measure = @($model.Sheets | Where-Object { -not (Test-IsDisassy $_.As) })

    Save-Workbook $xl $disassy (Join-Path $outDir ("SUBASSY DISASSEMBLY ENGINE " + $model.Tag + '.xlsx'))
    Save-Workbook $xl $measure (Join-Path $outDir ("SUBASSY MEASUREMENT ENGINE " + $model.Tag + '.xlsx'))
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "=============================================="
Write-Output "MERGE SELESAI - lanjut replace_logo_subassy.ps1"
