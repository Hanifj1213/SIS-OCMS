# Inspeksi sheet & orientasi teks pada checksheet disassembly mainline
$ErrorActionPreference = 'Stop'
$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"

$files = Get-ChildItem -Path $base -Recurse -File -Include *.xls,*.xlsx |
    Where-Object { $_.DirectoryName -match 'MAIN ?LINE\\DISASSEMBLY$' }

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($f in $files) {
    Write-Output "=============================================="
    Write-Output ("FILE: " + $f.FullName.Replace($base + '\', ''))
    $wb = $xl.Workbooks.Open($f.FullName, 0, $true)  # read-only
    foreach ($ws in $wb.Worksheets) {
        $used = $ws.UsedRange
        Write-Output ("  SHEET: '" + $ws.Name + "' | visible=" + $ws.Visible + " | used=" + $used.Rows.Count + "x" + $used.Columns.Count)

        # Cari header REUSE/SALVAGE/REPLACE di 120 baris pertama dan catat orientasinya
        $maxRow = [Math]::Min(120, $used.Rows.Count)
        $maxCol = [Math]::Min(40, $used.Columns.Count)
        for ($r = 1; $r -le $maxRow; $r++) {
            for ($c = 1; $c -le $maxCol; $c++) {
                $val = $ws.Cells.Item($r, $c).Text
                if ($val -match '^(REUSE|SALVAGE|REPLACE)$') {
                    $ori = $ws.Cells.Item($r, $c).Orientation
                    # Orientasi sel data 5 baris di bawah header sebagai sampel
                    $dOri = $ws.Cells.Item($r + 5, $c).Orientation
                    Write-Output ("    HEADER '$val' di R${r}C${c} orient=$ori | sel data +5 orient=$dOri")
                }
            }
        }
    }
    $wb.Close($false)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "SELESAI"
