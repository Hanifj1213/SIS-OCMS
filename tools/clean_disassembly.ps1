# Bersihkan checksheet disassembly mainline untuk diupload ke Google Sheets:
# 1. Hanya menyisakan sheet revisi terakhir (sheet lain dihapus)
# 2. Orientasi teks sel ISIAN kolom REUSE/SALVAGE/REPLACE dinormalkan (horizontal),
#    header kolomnya tetap vertikal sesuai desain
# 3. Disimpan sebagai .xlsx baru di folder _SIAP_UPLOAD_GSHEET (original tidak diubah)
$ErrorActionPreference = 'Stop'

$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$outDir = Join-Path $base "_SIAP_UPLOAD_GSHEET"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

# file sumber => sheet yang dipertahankan => nama file output
$jobs = @(
    @{ File = "$base\SA12V140E-1\MAINLINE\DISASSEMBLY\DISASSEMBLY ENGINE SA12V140E-1.xlsx";        Keep = 'Engine Disassy  12V140 (WA800)'; Out = 'DISASSEMBLY ENGINE SA12V140E-1 (WA800-3).xlsx' },
    @{ File = "$base\SA6D140E-2\MAINLINE\DISASSEMBLY\DISASSEMBLY ENGINE SA6D140E-2.xlsx";          Keep = 'Engine Disassy  140-2';           Out = 'DISASSEMBLY ENGINE SA6D140E-2 (GD825A).xlsx' },
    @{ File = "$base\SAA12V140E-3\MAIN LINE\DISASSEMBLY\DISASSEMBLY ENGINE12V140-3.xls";           Keep = 'Engine Disassy  12V140 (REV 6)'; Out = 'DISASSEMBLY ENGINE SAA12V140E-3 (PC2000-8).xlsx' },
    @{ File = "$base\SAA6D140E-5\MAIN LINE\DISASSEMBLY\CHECKSHEET DISASSEMBLY ENGINE SAA6D140E-5.xls"; Keep = 'Engine Disassy  140-3';       Out = 'DISASSEMBLY ENGINE SAA6D140E-5 (D155).xlsx' },
    @{ File = "$base\SAA6D170E-5\MAIN LINE\DISASSEMBLY\CHECKSHEET DISASSEMBLY SAA6D170E-5.xls";    Keep = 'Engine Disassy 170-5 (REV)';     Out = 'DISASSEMBLY ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx' }
)

$xlHorizontal = -4128
$xlUpward     = -4170

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($job in $jobs) {
    Write-Output "=============================================="
    Write-Output ("PROSES: " + (Split-Path $job.File -Leaf))

    $wb = $xl.Workbooks.Open($job.File, 0, $false)

    # --- 1. Hapus sheet selain revisi terakhir ---
    $names = @()
    foreach ($ws in $wb.Worksheets) { $names += $ws.Name }
    foreach ($name in $names) {
        if ($name -ne $job.Keep) {
            $wb.Worksheets.Item($name).Delete()
            Write-Output ("  hapus sheet: '$name'")
        }
    }

    $ws = $wb.Worksheets.Item($job.Keep)
    $ur = $ws.UsedRange
    $lastRow = $ur.Row + $ur.Rows.Count - 1

    # --- 2. Cari semua header REUSE/SALVAGE/REPLACE (baca nilai secara bulk agar cepat) ---
    $vals = $ur.Value2
    $rOff = $ur.Row - 1
    $cOff = $ur.Column - 1
    $headers = @()   # daftar @{ Row; Col }
    for ($r = 1; $r -le $vals.GetLength(0); $r++) {
        for ($c = 1; $c -le $vals.GetLength(1); $c++) {
            $v = $vals[$r, $c]
            if ($v -is [string] -and $v.Trim() -match '^(REUSE|SALVAGE|REPLACE)$') {
                $headers += @{ Row = $r + $rOff; Col = $c + $cOff }
            }
        }
    }
    $cols = $headers | ForEach-Object { $_.Col } | Sort-Object -Unique
    $firstHeaderRow = ($headers | ForEach-Object { $_.Row } | Measure-Object -Minimum).Minimum
    Write-Output ("  kolom REUSE/SALVAGE/REPLACE: " + ($cols -join ', ') + " | header pertama baris $firstHeaderRow | total header: " + $headers.Count)

    # --- 3. Normalkan orientasi seluruh kolom isian, lalu kembalikan header ke vertikal ---
    foreach ($col in $cols) {
        $rng = $ws.Range($ws.Cells.Item($firstHeaderRow, $col), $ws.Cells.Item($lastRow, $col))
        $rng.Orientation = $xlHorizontal
    }
    foreach ($h in $headers) {
        $cell = $ws.Cells.Item($h.Row, $h.Col)
        $cell.MergeArea.Orientation = $xlUpward
    }
    Write-Output "  orientasi sel isian -> horizontal, header tetap vertikal"

    # --- 4. Simpan sebagai .xlsx baru ---
    $outPath = Join-Path $outDir $job.Out
    if (Test-Path $outPath) { Remove-Item $outPath -Force }
    $wb.SaveAs($outPath, 51)  # 51 = xlOpenXMLWorkbook (.xlsx)
    $wb.Close($false)
    Write-Output ("  tersimpan: " + $outPath)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "=============================================="
Write-Output "SEMUA SELESAI"
