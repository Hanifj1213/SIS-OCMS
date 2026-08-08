# Gabungkan file measurement mainline per engine model menjadi 1 workbook
# multi-sheet, siap upload ke Google Sheets.
# Hanya sheet yang relevan untuk model tsb yang diambil (file sumber banyak
# berisi sheet engine lain). Original tidak diubah.
$ErrorActionPreference = 'Stop'

$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$outDir = Join-Path $base "_SIAP_UPLOAD_GSHEET"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

# Definisi: file output -> daftar (file sumber, nama sheet sumber, nama tab baru)
$models = @(
    @{
        Out = 'MEASUREMENT ENGINE SA12V140E-1 (WA800-3).xlsx'
        Sheets = @(
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING CRANKSHAFT ENGINE SA12V140E-1.xlsx"; Sheet = 'Crankshaft 12V140-1,2 (REV.4)';   As = 'CRANKSHAFT' },
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING CAMSHAFT ENGINE SA12V140E-1.xlsx";   Sheet = 'cam shaft 12V140 (HD785-5)(RH)';  As = 'CAMSHAFT RH' },
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING CAMSHAFT ENGINE SA12V140E-1.xlsx";   Sheet = 'cam shaft 12V140 (HD785-5) (LH)'; As = 'CAMSHAFT LH' },
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING CON ROD ENGINE SA12V140E-1.xlsx";    Sheet = 'Con-Rod 12V140-1,2(REV)';         As = 'CON ROD' },
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING CYL BLOCK ENGINE SA12V140E-1.xlsx";  Sheet = '12V140-1,3(REV 5)';               As = 'CYL BLOCK' },
            @{ File = "$base\SA12V140E-1\MAINLINE\MEASURING\MEASURING PIN PISTON ENGINE SA12V140E-1.xlsx"; Sheet = 'Piston Pin 12V140 Polish  (Rev)'; As = 'PIN PISTON' }
        )
    },
    @{
        Out = 'MEASUREMENT ENGINE SA6D140E-2 (GD825A).xlsx'
        Sheets = @(
            @{ File = "$base\SA6D140E-2\MAINLINE\MEASURING\MEASURING CRANKSHAFT ENGINE SA6D140E-2.xlsx"; Sheet = 'Crankshaft 6D140-2 Series';       As = 'CRANKSHAFT' },
            @{ File = "$base\SA6D140E-2\MAINLINE\MEASURING\MEASURING CAM SHAFT ENGINE SA6D140E-2.xlsx";  Sheet = 'cam shaft 6D140-2';               As = 'CAMSHAFT' },
            @{ File = "$base\SA6D140E-2\MAINLINE\MEASURING\MEASURING CON ROD ENGINE SA6D140E-2.xlsx";    Sheet = 'Con-Rod 6D140-2';                 As = 'CON ROD' },
            @{ File = "$base\SA6D140E-2\MAINLINE\MEASURING\MEASURING CYL BLOCK ENGINE SA6D140E-2.xlsx";  Sheet = 'Cyl.Block 6D140-2 (rev 0)';       As = 'CYL BLOCK' },
            @{ File = "$base\SA6D140E-2\MAINLINE\MEASURING\MEASURING PIN PISTON ENGINE SA6D140E-2.xlsx"; Sheet = 'Piston Pin 6D140 Polish  (Rev)';  As = 'PIN PISTON' }
        )
    },
    @{
        Out = 'MEASUREMENT ENGINE SAA12V140E-3 (PC2000-8).xlsx'
        Sheets = @(
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASURING CRANKSHAFT.xlsx";           Sheet = 'SALVAGING 12V140-3';               As = 'CRANKSHAFT' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASUREMENT CAMSHAFT.xlsx";           Sheet = 'cam shaft 12V140 (HD785-5)(RH)';   As = 'CAMSHAFT RH' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASUREMENT CAMSHAFT.xlsx";           Sheet = 'cam shaft 12V140 (HD785-5) (LH)';  As = 'CAMSHAFT LH' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASURING CONROD.xlsx";               Sheet = 'Con-Rod 12V140-3(REV) (2)';        As = 'CON ROD' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASURING CYL BLOCK SAA12V140E-3.xlsx"; Sheet = '12V140-1,3(REV 5)';              As = 'CYL BLOCK' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASURING CYL LINER SAA12V140E-3.xls"; Sheet = 'Cylinder Liner 6D140-1 & 2 (2)';  As = 'CYL LINER' },
            @{ File = "$base\SAA12V140E-3\MAIN LINE\MEASUREMENT\MEASURING PIN PISTON.xlsx";           Sheet = 'Piston Pin 12V140 Polish  (Rev)';  As = 'PIN PISTON' }
        )
    },
    @{
        Out = 'MEASUREMENT ENGINE SAA6D140E-5 (D155).xlsx'
        Sheets = @(
            @{ File = "$base\SAA6D140E-5\MAIN LINE\MEASURING\MEASURING CRANKSHAFT ENGINE SAA6D140E-5.xlsx"; Sheet = 'Crankshaft 6D140-5 Series';      As = 'CRANKSHAFT' },
            @{ File = "$base\SAA6D140E-5\MAIN LINE\MEASURING\MEASURING CAMSHAFT ENGINE SAA6D140E-5.xlsx";   Sheet = 'cam shaft 6D140-5';              As = 'CAMSHAFT' },
            @{ File = "$base\SAA6D140E-5\MAIN LINE\MEASURING\MEASURING CON ROD ENGINE SAA6D140E-5.xlsx";    Sheet = 'Con-Rod 6D140-5 (REV-2)';        As = 'CON ROD' },
            @{ File = "$base\SAA6D140E-5\MAIN LINE\MEASURING\MEASURING CYL BLOCK ENGINE SAA6D140E-5.xlsx";  Sheet = 'Cyl.Block 6D140E-5';             As = 'CYL BLOCK' },
            @{ File = "$base\SAA6D140E-5\MAIN LINE\MEASURING\MEASURING PIN PISTON ENGINE SAA6D140E-5.xlsx"; Sheet = 'Piston Pin 6D140 Polish  (Rev)'; As = 'PIN PISTON' }
        )
    },
    @{
        Out = 'MEASUREMENT ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx'
        Sheets = @(
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING CRANKSHAFT SAA6D170E-5.xlsx"; Sheet = 'Crankshaft 6D170-5';              As = 'CRANKSHAFT' },
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING CAM SHAFT SAA6D170E-5.xls";   Sheet = 'cam shaft 6D170-5';               As = 'CAMSHAFT' },
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING CONROD SAA6D170E-5.xlsx";     Sheet = 'Con-Rod 6D170-5(REV.04)';         As = 'CON ROD' },
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING CYL BLOCK SAA6D170E-5.xlsx";  Sheet = 'Cyl.Block 6D170E-5  (REV5)';      As = 'CYL BLOCK' },
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING CYL LINER SAA6D170E-5.xls";   Sheet = 'Cylinder Liner 6D170-1 & 2';      As = 'CYL LINER' },
            @{ File = "$base\SAA6D170E-5\MAIN LINE\MEASUREMENT\MEASURING PIN PISTON SAA6D170E-5.xlsx"; Sheet = 'Piston Pin 6D170 Polish (rev)';   As = 'PIN PISTON' }
        )
    }
)

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($model in $models) {
    Write-Output "=============================================="
    Write-Output ("BUAT: " + $model.Out)

    $dest = $xl.Workbooks.Add()

    # Cache workbook sumber agar file yang sama tidak dibuka dua kali
    $openBooks = @{}
    foreach ($s in $model.Sheets) {
        if (-not $openBooks.ContainsKey($s.File)) {
            $openBooks[$s.File] = $xl.Workbooks.Open($s.File, 0, $true)
        }
        $srcWb = $openBooks[$s.File]
        $srcWs = $srcWb.Worksheets.Item($s.Sheet)

        $srcWs.Copy([System.Reflection.Missing]::Value, $dest.Worksheets.Item($dest.Worksheets.Count))
        $copied = $dest.Worksheets.Item($dest.Worksheets.Count)
        $copied.Name = $s.As
        if ($copied.Visible -ne -1) { $copied.Visible = -1 }
        Write-Output ("  + tab '" + $s.As + "'  <- '" + $s.Sheet + "'")
    }

    foreach ($wb in $openBooks.Values) { $wb.Close($false) }

    # Hapus sheet kosong default bawaan workbook baru
    while ($dest.Worksheets.Item(1).Name -match '^Sheet\d+$') {
        $dest.Worksheets.Item(1).Delete()
    }

    $outPath = Join-Path $outDir $model.Out
    if (Test-Path $outPath) { Remove-Item $outPath -Force }
    $dest.SaveAs($outPath, 51)
    $dest.Close($false)
    Write-Output ("  tersimpan: " + $outPath)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "=============================================="
Write-Output "SEMUA SELESAI"
